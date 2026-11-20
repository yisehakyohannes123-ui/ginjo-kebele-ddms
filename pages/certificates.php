<?php
/**
 * CERTIFICATES MANAGEMENT PAGE
 * Display and manage certificates
 */

// Start session
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
$is_community = isCommunity();

// Get filter parameters
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "SELECT cert.*, c.first_name, c.last_name, c.customer_id,
                 ct.type_name, u.full_name as issued_by_name
          FROM certificates cert
          JOIN customers c ON cert.customer_id = c.customer_id
          JOIN certificate_types ct ON cert.type_id = ct.type_id
          JOIN users u ON cert.issued_by = u.user_id
          WHERE 1=1";

if ($type_filter) {
    $query .= " AND cert.type_id = " . (int)$type_filter;
}

if ($date_from) {
    $query .= " AND cert.issue_date >= '" . $conn->real_escape_string($date_from) . "'";
}

if ($date_to) {
    $query .= " AND cert.issue_date <= '" . $conn->real_escape_string($date_to) . "'";
}

$query .= " ORDER BY cert.issue_date DESC";

$certificates = $conn->query($query);
$cert_types = $conn->query("SELECT * FROM certificate_types");

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-certificate"></i> Certificates Management
            </h2>
            <p class="text-muted">
                <?php if ($is_community): ?>
                    View all issued certificates
                <?php else: ?>
                    Issue new certificates and manage certificate records
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <?php if (!$is_community && (hasRole('Record_Officer') || hasRole('System_Administrator'))): ?>
            <a href="issue-certificate.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Issue New Certificate
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-filter me-2"></i> Filter Certificates
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Certificate Type</label>
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <?php 
                        if ($cert_types) {
                            while($type = $cert_types->fetch_assoc()): 
                        ?>
                        <option value="<?php echo $type['type_id']; ?>" 
                            <?php echo $type_filter == $type['type_id'] ? 'selected' : ''; ?>>
                            <?php echo $type['type_name']; ?>
                        </option>
                        <?php 
                            endwhile;
                        } 
                        ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Apply
                    </button>
                    <a href="certificates.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Certificates Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-list me-2"></i> Issued Certificates
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="dataTable">
                    <thead>
                        <tr>
                            <th>Certificate #</th>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Issue Date</th>
                            <th>Issued By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($certificates && $certificates->num_rows > 0): ?>
                            <?php while($row = $certificates->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $row['certificate_number']; ?></strong></td>
                                <td>
                                    <a href="view-resident.php?id=<?php echo $row['customer_id']; ?>">
                                        <?php echo htmlspecialchars($row['first_name'] . ' ' . ($row['last_name'] ?? '')); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $row['type_name']; ?></span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['issue_date'])); ?></td>
                                <td><?php echo $row['issued_by_name']; ?></td>
                                <td>
                                    <a href="view-certificate.php?id=<?php echo $row['certificate_id']; ?>" 
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (!$is_community): ?>
                                    <a href="print-certificate.php?id=<?php echo $row['certificate_id']; ?>" 
                                       class="btn btn-sm btn-secondary" title="Print" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i> No certificates found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>