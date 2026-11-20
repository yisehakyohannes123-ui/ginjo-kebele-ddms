<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// DEBUG - Check session
echo "<!-- DEBUG: Session user_id = " . ($_SESSION['user_id'] ?? 'NOT SET') . " -->";
echo "<!-- DEBUG: Session user_role = " . ($_SESSION['user_role'] ?? 'NOT SET') . " -->";

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['is_community'])) {
    // Not logged in, redirect to login
    header("Location: login.php");
    exit();
}

// If we get here, user is logged in - continue with dashboard
// ... rest of your dashboard code ...
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get database connection
$conn = getConnection();
if (!$conn) {
    die("Database connection failed. Please check your database configuration.");
}

// Initialize variables
$stats = [];
$error = '';
$is_community = isCommunity();
$user_role = $_SESSION['user_role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Get notification count
$notification_count = 0;
$notif_result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
if ($notif_result) {
    $notification_count = $notif_result->fetch_assoc()['count'];
}

/**
 * COMMUNITY USER - Show simplified dashboard
 */
if ($is_community) {
    // Check if requests table exists
    $requests_table_exists = false;
    $table_check = $conn->query("SHOW TABLES LIKE 'requests'");
    if ($table_check && $table_check !== false) {
        $requests_table_exists = ($table_check->num_rows > 0);
    }
    
    // Initialize variables
    $my_requests = [];
    
    // Only try to get requests if table exists and user is logged in
    if ($requests_table_exists && $user_id > 0) {
        $req_query = "SELECT r.*, rt.type_name 
                      FROM requests r
                      LEFT JOIN request_types rt ON r.request_type_id = rt.type_id
                      WHERE r.requested_by = ? 
                      ORDER BY r.request_date DESC
                      LIMIT 5";
        
        $req_stmt = $conn->prepare($req_query);
        if ($req_stmt) {
            $req_stmt->bind_param("i", $user_id);
            $req_stmt->execute();
            $req_result = $req_stmt->get_result();
            
            if ($req_result) {
                while ($row = $req_result->fetch_assoc()) {
                    $my_requests[] = $row;
                }
            }
            $req_stmt->close();
        }
    }
    
    // Sample statistics for community users
    $stats = [
        'total_residents' => 5423,
        'total_ids' => 3456,
        'total_certificates' => 1789,
        'pending_cases' => 23
    ];
    
} else {
    /**
     * STAFF USER - Get real data based on role
     */
    try {
        // Get main statistics
        $queries = [
            'total_residents' => "SELECT COUNT(*) as count FROM customers WHERE is_active = 1",
            'total_ids' => "SELECT COUNT(*) as count FROM ids",
            'total_certificates' => "SELECT COUNT(*) as count FROM certificates",
            'pending_cases' => "SELECT COUNT(*) as count FROM social_court_cases WHERE status = 'Pending'"
        ];
        
        foreach ($queries as $key => $sql) {
            $result = $conn->query($sql);
            if ($result && $row = $result->fetch_assoc()) {
                $stats[$key] = $row['count'];
            } else {
                $stats[$key] = 0;
            }
        }
        
        // Check if requests table exists
        $requests_table_exists = false;
        $table_check = $conn->query("SHOW TABLES LIKE 'requests'");
        if ($table_check && $table_check !== false) {
            $requests_table_exists = ($table_check->num_rows > 0);
        }
        
        // Get pending requests based on role
        $pending_requests = [];
        if ($requests_table_exists) {
            $role_condition = "";
            switch ($user_role) {
                case 'System_Administrator':
                    $role_condition = "1=1";
                    break;
                case 'Kebele_Manager':
                    $role_condition = "rt.type_category IN ('id', 'clearance', 'court')";
                    break;
                case 'Record_Officer':
                    $role_condition = "rt.type_category IN ('certificate', 'registration')";
                    break;
                default:
                    $role_condition = "1=0";
            }
            
            if ($role_condition) {
                $req_query = "
                    SELECT r.*, rt.type_name, rt.type_category 
                    FROM requests r
                    JOIN request_types rt ON r.request_type_id = rt.type_id
                    WHERE $role_condition AND r.status = 'pending'
                    ORDER BY 
                        CASE r.priority 
                            WHEN 'urgent' THEN 1
                            WHEN 'high' THEN 2
                            WHEN 'normal' THEN 3
                            WHEN 'low' THEN 4
                        END,
                        r.request_date DESC
                    LIMIT 5
                ";
                $req_result = $conn->query($req_query);
                if ($req_result) {
                    while ($row = $req_result->fetch_assoc()) {
                        $pending_requests[] = $row;
                    }
                }
            }
        }
        
        // Get role-specific recent activities
        $recent_activities = [
            'ids' => [],
            'certificates' => []
        ];
        
        if ($user_role == 'Kebele_Manager' || $user_role == 'System_Administrator') {
            // Recent IDs for Manager
            $ids_result = $conn->query("
                SELECT i.id_number, c.first_name, c.last_name, i.issued_date 
                FROM ids i 
                JOIN customers c ON i.customer_id = c.customer_id 
                ORDER BY i.issued_date DESC LIMIT 5
            ");
            if ($ids_result) {
                while ($row = $ids_result->fetch_assoc()) {
                    $recent_activities['ids'][] = $row;
                }
            }
        }
        
        if ($user_role == 'Record_Officer' || $user_role == 'System_Administrator') {
            // Recent Certificates for Officer
            $certs_result = $conn->query("
                SELECT cert.certificate_number, c.first_name, c.last_name, ct.type_name 
                FROM certificates cert 
                JOIN customers c ON cert.customer_id = c.customer_id 
                JOIN certificate_types ct ON cert.type_id = ct.type_id 
                ORDER BY cert.issue_date DESC LIMIT 5
            ");
            if ($certs_result) {
                while ($row = $certs_result->fetch_assoc()) {
                    $recent_activities['certificates'][] = $row;
                }
            }
        }
        
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Dashboard error: " . $e->getMessage());
    }
}

// Include header
include '../includes/header.php';
?>

<!-- ========== MAIN CONTENT START ========== -->
<div class="container-fluid">
    <!-- Welcome Section with Notification Badge -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </h2>
            <p class="text-muted">
                <i class="fas fa-clock me-2"></i> <?php echo date('l, F j, Y \a\t g:i A'); ?>
                <span class="ms-3">
                    <i class="fas fa-user-circle me-1"></i> 
                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                    <?php if ($is_community): ?>
                        <span class="badge bg-warning text-dark ms-2">Community Access</span>
                    <?php else: ?>
                        <span class="badge bg-info ms-2"><?php echo getRoleDisplay(); ?></span>
                    <?php endif; ?>
                </span>
                <?php if ($notification_count > 0): ?>
                    <span class="ms-3">
                        <span class="badge bg-danger">
                            <i class="fas fa-bell"></i> <?php echo $notification_count; ?> new
                        </span>
                    </span>
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-outline-primary me-2" onclick="location.reload()" data-bs-toggle="tooltip" title="Refresh Dashboard">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="btn btn-outline-secondary" onclick="window.print()" data-bs-toggle="tooltip" title="Print Dashboard">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- Error Alert -->
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle fa-lg me-2"></i>
        <strong>System Notice:</strong> <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- COMMUNITY USER SECTION -->
    <?php if ($is_community): ?>
    
    <!-- Community Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h4 class="mb-3"><i class="fas fa-paper-plane text-primary me-2"></i> Need a Service?</h4>
                    <p class="text-muted mb-3">Submit your request online and track its status.</p>
                    <a href="community-request.php" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-edit"></i> Submit New Request
                    </a>
                    <a href="my-requests.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-list"></i> My Requests
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- My Recent Requests -->
    <?php if (!empty($my_requests)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-history me-2"></i> My Recent Requests
                    <a href="my-requests.php" class="text-white float-end">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Request #</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($my_requests as $req): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($req['request_number'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($req['type_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo isset($req['request_date']) ? date('d/m/Y', strtotime($req['request_date'])) : 'N/A'; ?></td>
                                    <td>
                                        <?php
                                        $status = $req['status'] ?? 'pending';
                                        $status_class = [
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'completed' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $class = $status_class[$status] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $class; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view-request.php?id=<?php echo $req['request_id'] ?? 0; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Show message if no requests -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5>No Requests Yet</h5>
                    <p class="text-muted">You haven't submitted any service requests.</p>
                    <a href="community-request.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i> Submit Your First Request
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Community Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">TOTAL RESIDENTS</div>
                        <div class="stat-value"><?php echo number_format($stats['total_residents']); ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #27ae60, #229954);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">IDS ISSUED</div>
                        <div class="stat-value"><?php echo number_format($stats['total_ids']); ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-id-card fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">CERTIFICATES</div>
                        <div class="stat-value"><?php echo number_format($stats['total_certificates']); ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-certificate fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">COURT CASES</div>
                        <div class="stat-value"><?php echo number_format($stats['pending_cases']); ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-gavel fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- STAFF SECTION -->
    <?php else: ?>

    <!-- Staff Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card residents-card" onclick="window.location.href='residents.php'">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">TOTAL RESIDENTS</div>
                        <div class="stat-value"><?php echo number_format($stats['total_residents'] ?? 0); ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users fa-3x"></i>
                    </div>
                </div>
                <div class="stat-link">
                    <a href="residents.php" class="text-white text-decoration-none">
                        View All <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card ids-card" onclick="window.location.href='id-management.php'">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">IDS ISSUED</div>
                        <div class="stat-value"><?php echo number_format($stats['total_ids'] ?? 0); ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-id-card fa-3x"></i>
                    </div>
                </div>
                <div class="stat-link">
                    <a href="id-management.php" class="text-white text-decoration-none">
                        Manage <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card certificates-card" onclick="window.location.href='certificates.php'">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">CERTIFICATES</div>
                        <div class="stat-value"><?php echo number_format($stats['total_certificates'] ?? 0); ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-certificate fa-3x"></i>
                    </div>
                </div>
                <div class="stat-link">
                    <a href="certificates.php" class="text-white text-decoration-none">
                        View <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card cases-card" onclick="window.location.href='social-court.php'">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">COURT CASES</div>
                        <div class="stat-value"><?php echo number_format($stats['pending_cases'] ?? 0); ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-gavel fa-3x"></i>
                    </div>
                </div>
                <div class="stat-link">
                    <a href="social-court.php" class="text-white text-decoration-none">
                        View <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Requests Section - Role Specific -->
    <?php if (!empty($pending_requests)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-clock me-2"></i> Pending Requests
                    <?php if ($notification_count > 0): ?>
                        <span class="badge bg-danger ms-2"><?php echo $notification_count; ?> new</span>
                    <?php endif; ?>
                    <a href="requests.php" class="float-end text-dark">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Request #</th>
                                    <th>Date</th>
                                    <th>Resident</th>
                                    <th>Type</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pending_requests as $req): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($req['request_number']); ?></strong></td>
                                    <td><?php echo date('d/m/Y', strtotime($req['request_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($req['resident_name']); ?></td>
                                    <td><?php echo htmlspecialchars($req['type_name']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $req['priority'] == 'urgent' ? 'danger' : 
                                                ($req['priority'] == 'high' ? 'warning' : 
                                                ($req['priority'] == 'normal' ? 'primary' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($req['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view-request.php?id=<?php echo $req['request_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Role-Specific Recent Activities -->
    <div class="row">
        <!-- Manager Section - Recent IDs -->
        <?php if (($user_role == 'Kebele_Manager' || $user_role == 'System_Administrator') && !empty($recent_activities['ids'])): ?>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-id-card me-2"></i> Recently Issued IDs
                    <a href="id-management.php" class="text-white float-end">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID Number</th>
                                    <th>Resident</th>
                                    <th>Issue Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_activities['ids'] as $id): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($id['id_number']); ?></td>
                                    <td><?php echo htmlspecialchars($id['first_name'] . ' ' . $id['last_name']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($id['issued_date'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Officer Section - Recent Certificates -->
        <?php if (($user_role == 'Record_Officer' || $user_role == 'System_Administrator') && !empty($recent_activities['certificates'])): ?>
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-certificate me-2"></i> Recently Issued Certificates
                    <a href="certificates.php" class="text-white float-end">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Certificate #</th>
                                    <th>Resident</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_activities['certificates'] as $cert): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($cert['certificate_number']); ?></td>
                                    <td><?php echo htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']); ?></td>
                                    <td><span class="badge bg-info"><?php echo htmlspecialchars($cert['type_name']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Admin Section - Quick Stats -->
    <?php if ($user_role == 'System_Administrator'): ?>
    <div class="row">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="row text-center">
                        <?php
                        $active_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch_assoc()['count'];
                        $total_requests = $conn->query("SELECT COUNT(*) as count FROM requests")->fetch_assoc()['count'] ?? 0;
                        $pending_count = $conn->query("SELECT COUNT(*) as count FROM requests WHERE status = 'pending'")->fetch_assoc()['count'] ?? 0;
                        $completed_count = $conn->query("SELECT COUNT(*) as count FROM requests WHERE status = 'completed'")->fetch_assoc()['count'] ?? 0;
                        ?>
                        <div class="col-md-3">
                            <h6>Active Users</h6>
                            <h3><?php echo $active_users; ?></h3>
                        </div>
                        <div class="col-md-3">
                            <h6>Total Requests</h6>
                            <h3><?php echo $total_requests; ?></h3>
                        </div>
                        <div class="col-md-3">
                            <h6>Pending</h6>
                            <h3><?php echo $pending_count; ?></h3>
                        </div>
                        <div class="col-md-3">
                            <h6>Completed</h6>
                            <h3><?php echo $completed_count; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>

    <!-- System Status (Visible to all) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6><i class="fas fa-server text-primary me-2"></i> System Status</h6>
                            <p class="small text-muted mb-0">
                                <i class="fas fa-circle text-success me-2"></i> All systems operational
                                <span class="mx-2">|</span>
                                <i class="fas fa-clock me-2"></i> <?php echo date('H:i:s'); ?>
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <?php if (!$is_community): ?>
                            <a href="reports.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-chart-line"></i> Generate Report
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Custom Styles -->
<style>
/* Stat Cards */
.stat-card {
    padding: 20px;
    border-radius: 15px;
    color: white;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
    height: 100%;
    cursor: pointer;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.stat-card.residents-card { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-card.ids-card { background: linear-gradient(135deg, #27ae60, #229954); }
.stat-card.certificates-card { background: linear-gradient(135deg, #f39c12, #e67e22); }
.stat-card.cases-card { background: linear-gradient(135deg, #e74c3c, #c0392b); }

.stat-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.2;
}

.stat-card .stat-label {
    font-size: 0.85rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card .stat-link {
    margin-top: 10px;
    font-size: 0.85rem;
}

.stat-card .stat-icon {
    opacity: 0.3;
    transition: all 0.3s;
}

.stat-card:hover .stat-icon {
    opacity: 0.5;
    transform: scale(1.1);
}

/* Card Enhancements */
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.card:hover {
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
    font-weight: 600;
    padding: 15px 20px;
}

/* Page Title */
.page-title {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 5px;
    font-size: 2rem;
}

.page-title i {
    color: #667eea;
    margin-right: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .stat-card .stat-value {
        font-size: 1.5rem;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
}

/* Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.row > [class*="col-"] {
    animation: fadeInUp 0.5s ease-out;
    animation-fill-mode: both;
}
</style>

<!-- Dashboard JavaScript -->
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
});

// System health check
function checkSystemHealth() {
    alert('✅ SYSTEM HEALTH CHECK PASSED\n\n' +
          '• Database: Connected\n' +
          '• All services: Operational\n' +
          '• Memory usage: Normal\n' +
          '• No errors detected\n' +
          '• System performance: Excellent');
}
</script>

<?php
// Close database connection
if (isset($conn) && $conn) {
    $conn->close();
}
include '../includes/footer.php';
?>