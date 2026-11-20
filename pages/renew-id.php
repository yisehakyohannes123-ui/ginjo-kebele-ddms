<?php
require_once '../config/database.php';
requireRole('Kebele_Manager');

$conn = getConnection();
$error = '';
$success = '';

// Get resident info if ID is specified
$resident_info = null;
$id_number = $_GET['id'] ?? null;

if ($id_number) {
    $stmt = $conn->prepare("
        SELECT i.*, c.first_name, c.last_name, c.customer_id,
               h.house_number, k.kebele_name
        FROM ids i
        JOIN customers c ON i.customer_id = c.customer_id
        LEFT JOIN houses h ON c.house_id = h.house_id
        LEFT JOIN kebeles k ON h.kebele_id = k.kebele_id
        WHERE i.id_number = ? AND i.is_renewed = 0
    ");
    $stmt->bind_param("i", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $resident_info = $result->fetch_assoc();
    } else {
        $error = "ID not found or already renewed!";
    }
}

// Get all IDs eligible for renewal
$ids_for_renewal = $conn->query("
    SELECT i.id_number, i.issued_date, i.expiry_date,
           c.first_name, c.last_name, c.customer_id
    FROM ids i
    JOIN customers c ON i.customer_id = c.customer_id
    WHERE i.is_renewed = 0 
    AND (i.expiry_date IS NOT NULL AND i.expiry_date < DATE_ADD(CURDATE(), INTERVAL 3 MONTH))
    ORDER BY i.expiry_date ASC
");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $original_id = $_POST['original_id'];
    $customer_id = $_POST['customer_id'];
    $renew_date = $_POST['renew_date'];
    
    if (empty($original_id) || empty($customer_id) || empty($renew_date)) {
        $error = "All fields are required!";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Mark old ID as renewed
            $update = $conn->prepare("UPDATE ids SET is_renewed = 1 WHERE id_number = ?");
            $update->bind_param("i", $original_id);
            $update->execute();
            
            // Insert renewed ID record
            $insert = $conn->prepare("INSERT INTO renewed_ids 
                                      (original_id_number, customer_id, renew_date, renewed_by) 
                                      VALUES (?, ?, ?, ?)");
            $insert->bind_param("iisi", $original_id, $customer_id, $renew_date, $_SESSION['user_id']);
            $insert->execute();
            
            // Issue new ID
            $new_issue = $conn->prepare("INSERT INTO ids 
                                        (customer_id, issued_date, expiry_date, issued_by) 
                                        VALUES (?, ?, DATE_ADD(?, INTERVAL 5 YEAR), ?)");
            $new_issue->bind_param("issi", $customer_id, $renew_date, $renew_date, $_SESSION['user_id']);
            $new_issue->execute();
            
            $conn->commit();
            header("Location: id-management.php?msg=renewed");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error renewing ID: " . $e->getMessage();
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-sync-alt"></i> Renew ID Card</h2>
        </div>
        <div class="col text-end">
            <a href="id-management.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to ID Management
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="form-container">
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($ids_for_renewal->num_rows > 0 && !$resident_info): ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> IDs Due for Renewal</h5>
                    <p>The following IDs are expiring soon and need renewal:</p>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Resident</th>
                                <th>Expiry Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($id = $ids_for_renewal->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $id['id_number']; ?></td>
                                <td><?php echo $id['first_name'] . ' ' . $id['last_name']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($id['expiry_date'])); ?></td>
                                <td>
                                    <a href="renew-id.php?id=<?php echo $id['id_number']; ?>" 
                                       class="btn btn-sm btn-warning">Renew Now</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" data-validate="true">
                    <?php if ($resident_info): ?>
                    <input type="hidden" name="original_id" value="<?php echo $resident_info['id_number']; ?>">
                    <input type="hidden" name="customer_id" value="<?php echo $resident_info['customer_id']; ?>">
                    
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <i class="fas fa-id-card"></i> ID Information
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>ID Number:</strong> <?php echo $resident_info['id_number']; ?></p>
                                    <p><strong>Resident:</strong> <?php echo $resident_info['first_name'] . ' ' . $resident_info['last_name']; ?></p>
                                    <p><strong>House:</strong> <?php echo $resident_info['house_number'] ?? 'N/A'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Issue Date:</strong> <?php echo date('d/m/Y', strtotime($resident_info['issued_date'])); ?></p>
                                    <p><strong>Expiry Date:</strong> <?php echo date('d/m/Y', strtotime($resident_info['expiry_date'])); ?></p>
                                    <p><strong>Kebele:</strong> <?php echo $resident_info['kebele_name'] ?? 'Ginjo Guduru'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="renew_date" class="form-label">Renewal Date *</label>
                        <input type="date" class="form-control" id="renew_date" 
                               name="renew_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="alert alert-success">
                        <i class="fas fa-info-circle"></i>
                        Upon renewal, a new ID will be issued with a new expiry date (5 years from renewal date).
                        The old ID will be marked as renewed.
                    </div>
                    
                    <hr>
                    
                    <div class="text-end">
                        <a href="id-management.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-sync-alt"></i> Process Renewal
                        </button>
                    </div>
                    
                    <?php else: ?>
                    <div class="alert alert-info">
                        <p>Please select an ID to renew from the list above or enter the ID number manually:</p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="id_search" class="form-label">Enter ID Number</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="id_search" 
                                   placeholder="Enter ID number">
                            <button class="btn btn-primary" type="button" id="search_id">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#search_id').click(function() {
        let idNumber = $('#id_search').val();
        if (idNumber) {
            window.location.href = 'renew-id.php?id=' + idNumber;
        }
    });
    
    $('#id_search').keypress(function(e) {
        if (e.which == 13) {
            $('#search_id').click();
        }
    });
});
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>