<?php
/**
 * REQUESTS MANAGEMENT PAGE
 * Staff view and manage community requests
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in and is staff
if (!isLoggedIn() || isCommunity()) {
    header("Location: dashboard.php");
    exit();
}

$conn = getConnection();
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Determine which requests to show based on role
$role_condition = "";
switch ($user_role) {
    case 'System_Administrator':
        // Admin sees all requests
        $role_condition = "1=1";
        break;
    case 'Kebele_Manager':
        // Manager sees ID, clearance, court requests
        $role_condition = "rt.type_category IN ('id', 'clearance', 'court')";
        break;
    case 'Record_Officer':
        // Officer sees certificate, registration requests
        $role_condition = "rt.type_category IN ('certificate', 'registration')";
        break;
    default:
        $role_condition = "1=0"; // No requests
}

// Handle request assignment
if (isset($_POST['assign_request'])) {
    $request_id = (int)$_POST['request_id'];
    $update = $conn->prepare("UPDATE requests SET assigned_to = ?, status = 'processing' WHERE request_id = ?");
    $update->bind_param("ii", $user_id, $request_id);
    $update->execute();
    header("Location: requests.php");
    exit();
}

// Handle status update
if (isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    
    $update = $conn->prepare("UPDATE requests SET status = ?, notes = ? WHERE request_id = ?");
    $update->bind_param("ssi", $status, $notes, $request_id);
    
    if ($update->execute()) {
        // If completed, send notification
        if ($status == 'completed') {
            $request_query = $conn->prepare("SELECT * FROM requests WHERE request_id = ?");
            $request_query->bind_param("i", $request_id);
            $request_query->execute();
            $request = $request_query->get_result()->fetch_assoc();
            
            // Add to notifications
            $notify = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) 
                                     VALUES (?, ?, ?, ?)");
            $notify_user = $request['requested_by'] ?? 0;
            if ($notify_user > 0) {
                $title = "Request Completed: " . $request['request_number'];
                $message = "Your request has been completed. Please visit the kebele office.";
                $link = "view-request.php?id=$request_id";
                $notify->bind_param("isss", $notify_user, $title, $message, $link);
                $notify->execute();
            }
        }
    }
    
    header("Location: requests.php");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$type_filter = isset($_GET['type']) ? (int)$_GET['type'] : 0;

// Build query
$query = "SELECT r.*, rt.type_name, rt.type_category 
          FROM requests r
          JOIN request_types rt ON r.request_type_id = rt.type_id
          WHERE $role_condition";

if ($status_filter != 'all') {
    $query .= " AND r.status = '$status_filter'";
}

if ($type_filter > 0) {
    $query .= " AND r.request_type_id = $type_filter";
}

$query .= " ORDER BY 
            CASE r.priority 
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'normal' THEN 3
                WHEN 'low' THEN 4
            END,
            r.request_date DESC";

$requests = $conn->query($query);

// Get counts for dashboard
$counts = [];
$statuses = ['pending', 'processing', 'completed', 'rejected'];
foreach ($statuses as $status) {
    $count_query = "SELECT COUNT(*) as count FROM requests r 
                    JOIN request_types rt ON r.request_type_id = rt.type_id
                    WHERE $role_condition AND r.status = '$status'";
    $result = $conn->query($count_query);
    $counts[$status] = $result->fetch_assoc()['count'];
}

// Get request types for filter
$types = $conn->query("SELECT * FROM request_types");

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-tasks"></i> Service Requests
            </h2>
            <p class="text-muted">Manage community service requests</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card pending-card" onclick="filterByStatus('pending')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Pending</div>
                        <div class="stat-value"><?php echo $counts['pending']; ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-clock fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card processing-card" onclick="filterByStatus('processing')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Processing</div>
                        <div class="stat-value"><?php echo $counts['processing']; ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-spinner fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card completed-card" onclick="filterByStatus('completed')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Completed</div>
                        <div class="stat-value"><?php echo $counts['completed']; ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card rejected-card" onclick="filterByStatus('rejected')">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-label">Rejected</div>
                        <div class="stat-value"><?php echo $counts['rejected']; ?></div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-filter me-2"></i> Filter Requests
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Request Type</label>
                    <select class="form-select" name="type">
                        <option value="0">All Types</option>
                        <?php while($type = $types->fetch_assoc()): ?>
                        <option value="<?php echo $type['type_id']; ?>" 
                                <?php echo $type_filter == $type['type_id'] ? 'selected' : ''; ?>>
                            <?php echo $type['type_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="requests.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-list me-2"></i> Service Requests
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
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($requests && $requests->num_rows > 0): ?>
                            <?php while($row = $requests->fetch_assoc()): ?>
                            <tr class="request-row" data-id="<?php echo $row['request_id']; ?>">
                                <td>
                                    <strong><?php echo $row['request_number']; ?></strong>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['request_date'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['resident_name']); ?>
                                    <br>
                                    <small><?php echo $row['resident_phone']; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $row['type_name']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'pending' => 'warning',
                                        'processing' => 'info',
                                        'completed' => 'success',
                                        'rejected' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $status_class[$row['status']]; ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $priority_class = [
                                        'low' => 'secondary',
                                        'normal' => 'primary',
                                        'high' => 'warning',
                                        'urgent' => 'danger'
                                    ];
                                    ?>
                                    <span class="badge bg-<?php echo $priority_class[$row['priority']]; ?>">
                                        <?php echo ucfirst($row['priority']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="viewRequest(<?php echo $row['request_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($row['status'] == 'pending'): ?>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="assignToMe(<?php echo $row['request_id']; ?>)">
                                        <i class="fas fa-hand-pointer"></i> Assign
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5>No Requests Found</h5>
                                    <p class="text-muted">There are no requests matching your criteria.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Request View Modal -->
<div class="modal fade" id="requestModal" tabindex="-1" aria-labelledby="requestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="requestModalLabel">
                    <i class="fas fa-file-alt me-2"></i> Request Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="requestDetails">
                Loading...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="completeRequestBtn" style="display: none;">
                    <i class="fas fa-check"></i> Mark Complete
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    padding: 20px;
    border-radius: 15px;
    color: white;
    cursor: pointer;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.stat-card.pending-card { background: linear-gradient(135deg, #f39c12, #e67e22); }
.stat-card.processing-card { background: linear-gradient(135deg, #3498db, #2980b9); }
.stat-card.completed-card { background: linear-gradient(135deg, #27ae60, #229954); }
.stat-card.rejected-card { background: linear-gradient(135deg, #e74c3c, #c0392b); }

.stat-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
}

.request-row {
    cursor: pointer;
}

.request-row:hover {
    background-color: rgba(102, 126, 234, 0.1);
}
</style>

<script>
function filterByStatus(status) {
    window.location.href = 'requests.php?status=' + status;
}

function viewRequest(id) {
    $('#requestDetails').html('<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>');
    $('#requestModal').modal('show');
    
    // Load request details via AJAX
    $.ajax({
        url: 'get-request.php',
        method: 'POST',
        data: {request_id: id},
        success: function(response) {
            $('#requestDetails').html(response);
            
            // Show complete button if request is pending or processing
            if (response.includes('pending') || response.includes('processing')) {
                $('#completeRequestBtn').show();
                $('#completeRequestBtn').data('id', id);
            } else {
                $('#completeRequestBtn').hide();
            }
        }
    });
}

function assignToMe(id) {
    var form = $('<form method="post" style="display: none;">' +
                 '<input type="hidden" name="assign_request" value="1">' +
                 '<input type="hidden" name="request_id" value="' + id + '">' +
                 '</form>');
    $('body').append(form);
    form.submit();
}

function markComplete(id) {
    var notes = prompt('Enter completion notes (optional):');
    var form = $('<form method="post" style="display: none;">' +
                 '<input type="hidden" name="update_status" value="1">' +
                 '<input type="hidden" name="request_id" value="' + id + '">' +
                 '<input type="hidden" name="status" value="completed">' +
                 '<input type="hidden" name="notes" value="' + (notes || '') + '">' +
                 '</form>');
    $('body').append(form);
    form.submit();
}

$(document).ready(function() {
    $('#completeRequestBtn').click(function() {
        var id = $(this).data('id');
        markComplete(id);
    });
});
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>