<?php
/**
 * AJAX HANDLER for Request Details
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn() || !isset($_POST['request_id'])) {
    exit();
}

$conn = getConnection();
$request_id = (int)$_POST['request_id'];

$query = "SELECT r.*, rt.type_name, rt.type_category 
          FROM requests r
          JOIN request_types rt ON r.request_type_id = rt.type_id
          WHERE r.request_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    echo "<p class='text-danger'>Request not found.</p>";
    exit();
}

$status_class = [
    'pending' => 'warning',
    'processing' => 'info',
    'completed' => 'success',
    'rejected' => 'danger'
];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <h6 class="fw-bold">Request Information</h6>
            <table class="table table-sm">
                <tr>
                    <th>Request #:</th>
                    <td><?php echo $request['request_number']; ?></td>
                </tr>
                <tr>
                    <th>Date:</th>
                    <td><?php echo date('F j, Y H:i', strtotime($request['request_date'])); ?></td>
                </tr>
                <tr>
                    <th>Type:</th>
                    <td>
                        <span class="badge bg-info"><?php echo $request['type_name']; ?></span>
                        <small class="text-muted">(<?php echo ucfirst($request['type_category']); ?>)</small>
                    </td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <span class="badge bg-<?php echo $status_class[$request['status']]; ?>">
                            <?php echo ucfirst($request['status']); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Priority:</th>
                    <td>
                        <span class="badge bg-<?php 
                            echo $request['priority'] == 'urgent' ? 'danger' : 
                                ($request['priority'] == 'high' ? 'warning' : 
                                ($request['priority'] == 'normal' ? 'primary' : 'secondary')); 
                        ?>">
                            <?php echo ucfirst($request['priority']); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="col-md-6">
            <h6 class="fw-bold">Resident Information</h6>
            <table class="table table-sm">
                <tr>
                    <th>Name:</th>
                    <td><?php echo htmlspecialchars($request['resident_name']); ?></td>
                </tr>
                <tr>
                    <th>Phone:</th>
                    <td><?php echo htmlspecialchars($request['resident_phone']); ?></td>
                </tr>
                <?php if (!empty($request['resident_email'])): ?>
                <tr>
                    <th>Email:</th>
                    <td><?php echo htmlspecialchars($request['resident_email']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    
    <?php if (!empty($request['request_details'])): ?>
    <div class="mt-3">
        <h6 class="fw-bold">Request Details</h6>
        <div class="p-3 bg-light rounded">
            <?php echo nl2br(htmlspecialchars($request['request_details'])); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($request['notes'])): ?>
    <div class="mt-3">
        <h6 class="fw-bold">Staff Notes</h6>
        <div class="p-3 bg-info bg-opacity-10 rounded">
            <?php echo nl2br(htmlspecialchars($request['notes'])); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($request['completion_date']): ?>
    <div class="mt-3">
        <small class="text-muted">
            Completed on: <?php echo date('F j, Y H:i', strtotime($request['completion_date'])); ?>
        </small>
    </div>
    <?php endif; ?>
    
    <!-- Update Status Form -->
    <?php if (in_array($request['status'], ['pending', 'processing'])): ?>
    <hr>
    <form method="post" action="requests.php" class="mt-3">
        <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
        <div class="row">
            <div class="col-md-8">
                <label class="form-label">Update Status</label>
                <select class="form-select" name="status" required>
                    <option value="processing" <?php echo $request['status'] == 'processing' ? 'selected' : ''; ?>>
                        Processing
                    </option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" name="update_status" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update
                </button>
            </div>
        </div>
        <div class="mt-2">
            <textarea class="form-control" name="notes" rows="2" 
                      placeholder="Add notes about this request..."><?php echo htmlspecialchars($request['notes']); ?></textarea>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php
$stmt->close();
$conn->close();
?>