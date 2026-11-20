<?php
require_once '../config/database.php';
requireLogin();

// Check if user has permission (only Kebele Manager and Admin can issue IDs)
if (!hasRole('Kebele_Manager') && !hasRole('System_Administrator')) {
    header("Location: dashboard.php?error=permission");
    exit();
}

$conn = getConnection();
$error = '';
$success = '';

// Get active residents for dropdown
$residents = $conn->query("
    SELECT c.*, h.house_number, k.kebele_name 
    FROM customers c
    LEFT JOIN houses h ON c.house_id = h.house_id
    LEFT JOIN kebeles k ON h.kebele_id = k.kebele_id
    WHERE c.is_active = 1
    ORDER BY c.first_name
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'];
    $issue_date = $_POST['issue_date'];
    $expiry_date = $_POST['expiry_date'] ?? null;
    
    if (empty($customer_id) || empty($issue_date)) {
        $error = "Please select a resident and issue date!";
    } else {
        // Check if resident already has an active ID
        $check = $conn->prepare("SELECT id_number FROM ids 
                                WHERE customer_id = ? AND is_renewed = 0 
                                AND (expiry_date IS NULL OR expiry_date >= CURDATE())");
        $check->bind_param("i", $customer_id);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "This resident already has an active ID!";
        } else {
            // Issue new ID
            $stmt = $conn->prepare("INSERT INTO ids (customer_id, issued_date, expiry_date, issued_by) 
                                    VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $customer_id, $issue_date, $expiry_date, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                header("Location: id-management.php?msg=issued");
                exit();
            } else {
                $error = "Error issuing ID: " . $conn->error;
            }
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-id-card"></i> Issue New ID Card</h2>
        </div>
        <div class="col text-end">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <a href="id-management.php" class="btn btn-info">
                <i class="fas fa-list"></i> View All IDs
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-id-card"></i> Issue New ID
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="issueIdForm">
                        <div class="mb-3">
                            <label for="customer_id" class="form-label">Select Resident *</label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">-- Choose Resident --</option>
                                <?php while($row = $residents->fetch_assoc()): ?>
                                <option value="<?php echo $row['customer_id']; ?>">
                                    <?php echo $row['first_name'] . ' ' . ($row['last_name'] ?? '') . 
                                             ' - House: ' . ($row['house_number'] ?? 'N/A') . 
                                             ' (' . ($row['kebele_name'] ?? 'Ginjo Guduru') . ')'; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="issue_date" class="form-label">Issue Date *</label>
                                <input type="date" class="form-control" id="issue_date" 
                                       name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="expiry_date" class="form-label">Expiry Date</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date">
                                <small class="text-muted">Leave empty for lifetime validity</small>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Requirements:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Resident must be registered in the system</li>
                                <li>Resident must be 18 years or older</li>
                                <li>Resident must not have an active ID</li>
                            </ul>
                        </div>
                        
                        <hr>
                        
                        <div class="text-end">
                            <button type="reset" class="btn btn-secondary">Reset</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-id-card"></i> Issue ID
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Auto-calculate expiry date (5 years from issue date)
    $('#issue_date').change(function() {
        let issueDate = new Date($(this).val());
        if (issueDate) {
            let expiryDate = new Date(issueDate);
            expiryDate.setFullYear(expiryDate.getFullYear() + 5);
            
            let year = expiryDate.getFullYear();
            let month = String(expiryDate.getMonth() + 1).padStart(2, '0');
            let day = String(expiryDate.getDate()).padStart(2, '0');
            
            // Set default expiry date
            if (!$('#expiry_date').val()) {
                $('#expiry_date').val(year + '-' + month + '-' + day);
            }
        }
    });
    
    // Trigger change to set default expiry
    $('#issue_date').trigger('change');
});
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>