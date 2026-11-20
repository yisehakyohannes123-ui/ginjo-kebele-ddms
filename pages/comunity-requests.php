<?php
/**
 * COMMUNITY REQUEST FORM
 * Allows community residents to submit service requests
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in (community or staff)
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$conn = getConnection();
$is_community = isCommunity();
$success_message = '';
$error_message = '';

// Get request types
$request_types = $conn->query("SELECT * FROM request_types ORDER BY type_category, type_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resident_name = trim($_POST['resident_name']);
    $resident_phone = trim($_POST['resident_phone']);
    $resident_email = trim($_POST['resident_email']);
    $request_type_id = (int)$_POST['request_type_id'];
    $request_details = trim($_POST['request_details']);
    
    // Validate
    if (empty($resident_name) || empty($resident_phone) || empty($request_type_id)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Generate request number
        $year = date('Y');
        $month = date('m');
        $prefix = 'REQ';
        
        $result = $conn->query("SELECT COUNT(*) as count FROM requests WHERE YEAR(request_date) = $year");
        $count = $result->fetch_assoc()['count'] + 1;
        $request_number = $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
        
        // Insert request
        $stmt = $conn->prepare("INSERT INTO requests 
                                (request_number, resident_name, resident_phone, resident_email, 
                                 request_type_id, request_details, requested_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $community_id = $is_community ? ($_SESSION['user_id'] ?? 0) : null;
        $stmt->bind_param("ssssisi", $request_number, $resident_name, $resident_phone, 
                         $resident_email, $request_type_id, $request_details, $community_id);
        
        if ($stmt->execute()) {
            $success_message = "Your request has been submitted successfully! Request Number: $request_number";
            
            // Create notifications for relevant staff
            $request_id = $stmt->insert_id;
            
            // Get request type category
            $type_query = $conn->query("SELECT type_category FROM request_types WHERE type_id = $request_type_id");
            $type_data = $type_query->fetch_assoc();
            $category = $type_data['type_category'];
            
            // Determine which staff to notify based on category
            $role_map = [
                'id' => 'Kebele_Manager',
                'certificate' => 'Record_Officer',
                'registration' => 'Record_Officer',
                'clearance' => 'Kebele_Manager',
                'court' => 'Kebele_Manager'
            ];
            
            $target_role = $role_map[$category] ?? 'System_Administrator';
            
            // Notify all staff with that role
            $staff_query = $conn->prepare("SELECT user_id FROM users WHERE role_id = 
                                          (SELECT role_id FROM roles WHERE role_name = ?) AND is_active = 1");
            $staff_query->bind_param("s", $target_role);
            $staff_query->execute();
            $staff_result = $staff_query->get_result();
            
            while ($staff = $staff_result->fetch_assoc()) {
                $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, link) 
                                              VALUES (?, ?, ?, ?)");
                $title = "New Request: $request_number";
                $message = "A new $category request has been submitted by $resident_name.";
                $link = "view-request.php?id=$request_id";
                $notify_stmt->bind_param("isss", $staff['user_id'], $title, $message, $link);
                $notify_stmt->execute();
            }
        } else {
            $error_message = "Error submitting request: " . $conn->error;
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-paper-plane"></i> Request Service
            </h2>
            <p class="text-muted">Submit your service request online</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-edit me-2"></i> Service Request Form
                </div>
                <div class="card-body">
                    <form method="POST" id="requestForm" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="resident_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" name="resident_phone" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="resident_email">
                                <small class="text-muted">For notifications</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Request Type *</label>
                                <select class="form-select" name="request_type_id" id="requestType" required>
                                    <option value="">Select Request Type</option>
                                    <?php 
                                    $current_category = '';
                                    while($type = $request_types->fetch_assoc()): 
                                        if ($current_category != $type['type_category']):
                                            if ($current_category != '') echo '</optgroup>';
                                            $current_category = $type['type_category'];
                                            $category_display = ucfirst($current_category);
                                            echo "<optgroup label='$category_display Services'>";
                                        endif;
                                    ?>
                                    <option value="<?php echo $type['type_id']; ?>" 
                                            data-category="<?php echo $type['type_category']; ?>">
                                        <?php echo $type['type_name']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                    <?php if ($current_category != '') echo '</optgroup>'; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Request Details</label>
                            <textarea class="form-control" name="request_details" rows="5" 
                                      placeholder="Please provide any additional information..."></textarea>
                        </div>

                        <!-- Dynamic requirements based on request type -->
                        <div class="requirements-panel mb-3" id="requirementsPanel" style="display: none;">
                            <div class="card bg-light">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-clipboard-list me-2"></i> Required Documents
                                </div>
                                <div class="card-body" id="requirementsList">
                                    <!-- Requirements will be loaded here -->
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="text-end">
                            <button type="reset" class="btn btn-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Request Status Guide -->
            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-info-circle me-2"></i> What Happens Next?
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="status-step">
                                <span class="badge bg-primary rounded-circle p-3 mb-2">1</span>
                                <h6>Submit Request</h6>
                                <small>Fill and submit the form</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="status-step">
                                <span class="badge bg-warning rounded-circle p-3 mb-2">2</span>
                                <h6>Under Review</h6>
                                <small>Staff reviews your request</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="status-step">
                                <span class="badge bg-info rounded-circle p-3 mb-2">3</span>
                                <h6>Processing</h6>
                                <small>Your request is being processed</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="status-step">
                                <span class="badge bg-success rounded-circle p-3 mb-2">4</span>
                                <h6>Completed</h6>
                                <small>Notification via email/phone</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.status-step {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    height: 100%;
    transition: all 0.3s;
}

.status-step:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.status-step .badge {
    width: 50px;
    height: 50px;
    font-size: 1.2rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
$(document).ready(function() {
    // Show requirements based on selected request type
    $('#requestType').change(function() {
        var typeId = $(this).val();
        var typeText = $(this).find('option:selected').text();
        var category = $(this).find('option:selected').data('category');
        
        if (typeId) {
            var requirements = '';
            
            if (typeText.includes('ID')) {
                requirements = `
                    <ul class="mb-0">
                        <li>2 passport size photos</li>
                        <li>Birth certificate (original)</li>
                        <li>Kebele residence confirmation</li>
                        <li>Application fee: 50 Birr</li>
                        <li>For renewal: Old ID card</li>
                    </ul>
                `;
            } else if (typeText.includes('Birth')) {
                requirements = `
                    <ul class="mb-0">
                        <li>Health facility birth notification</li>
                        <li>Parents' ID cards</li>
                        <li>Marriage certificate (if available)</li>
                        <li>Two witnesses</li>
                        <li>Fee: 20 Birr</li>
                    </ul>
                `;
            } else if (typeText.includes('Death')) {
                requirements = `
                    <ul class="mb-0">
                        <li>Medical death notification</li>
                        <li>Deceased person's ID</li>
                        <li>Informant's ID</li>
                        <li>Witness statement</li>
                        <li>Fee: 20 Birr</li>
                    </ul>
                `;
            } else if (typeText.includes('Marriage')) {
                requirements = `
                    <ul class="mb-0">
                        <li>Both parties' ID cards</li>
                        <li>Birth certificates</li>
                        <li>Two witnesses per party</li>
                        <li>Photos (2 each)</li>
                        <li>Fee: 100 Birr</li>
                    </ul>
                `;
            } else if (typeText.includes('Divorce')) {
                requirements = `
                    <ul class="mb-0">
                        <li>Court decision document</li>
                        <li>Marriage certificate</li>
                        <li>Both parties' IDs</li>
                        <li>Witness statements</li>
                        <li>Fee: 150 Birr</li>
                    </ul>
                `;
            } else if (typeText.includes('Clearance')) {
                requirements = `
                    <ul class="mb-0">
                        <li>ID card</li>
                        <li>Reason for clearance letter</li>
                        <li>Destination address</li>
                        <li>Fee: 30 Birr</li>
                    </ul>
                `;
            }
            
            if (requirements) {
                $('#requirementsList').html(requirements);
                $('#requirementsPanel').slideDown();
            } else {
                $('#requirementsPanel').slideUp();
            }
        } else {
            $('#requirementsPanel').slideUp();
        }
    });
});
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>