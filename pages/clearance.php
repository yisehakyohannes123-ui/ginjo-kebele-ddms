<?php
require_once '../config/database.php';
requireRole('Kebele_Manager');

$conn = getConnection();
$error = '';
$success = '';

// Handle clearance issuance
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'];
    $clearance_type = $_POST['clearance_type'];
    $source_kebele = $_POST['source_kebele'] ?? 'Ginjo Guduru';
    $destination_kebele = $_POST['destination_kebele'] ?? '';
    $issue_date = $_POST['issue_date'];
    
    // Generate clearance number
    $clearance_number = 'CLR-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $stmt = $conn->prepare("INSERT INTO clearances 
                            (clearance_number, customer_id, clearance_type, source_kebele, destination_kebele, issue_date, issued_by) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissssi", $clearance_number, $customer_id, $clearance_type, $source_kebele, $destination_kebele, $issue_date, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $success = "Clearance issued successfully! Clearance Number: " . $clearance_number;
    } else {
        $error = "Error issuing clearance: " . $conn->error;
    }
}

// Get all clearances
$clearances = $conn->query("
    SELECT cl.*, c.first_name, c.last_name, c.customer_id,
           u.full_name as issued_by_name
    FROM clearances cl
    JOIN customers c ON cl.customer_id = c.customer_id
    JOIN users u ON cl.issued_by = u.user_id
    ORDER BY cl.issue_date DESC
");

// Get residents
$residents = $conn->query("SELECT customer_id, first_name, last_name FROM customers WHERE is_active = 1");
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-exchange-alt"></i> Clearance Management</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#issueClearanceModal">
                <i class="fas fa-plus"></i> Issue Clearance
            </button>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Clearance Table -->
    <div class="table-container">
        <table id="dataTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Clearance #</th>
                    <th>Resident</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Destination</th>
                    <th>Issue Date</th>
                    <th>Issued By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $clearances->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo $row['clearance_number']; ?></strong></td>
                    <td>
                        <a href="view-resident.php?id=<?php echo $row['customer_id']; ?>">
                            <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($row['clearance_type'] == 'Give'): ?>
                        <span class="badge bg-warning">Give</span>
                        <?php else: ?>
                        <span class="badge bg-success">Receive</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['source_kebele']; ?></td>
                    <td><?php echo $row['destination_kebele'] ?: 'N/A'; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['issue_date'])); ?></td>
                    <td><?php echo $row['issued_by_name']; ?></td>
                    <td>
                        <a href="print-clearance.php?id=<?php echo $row['clearance_id']; ?>" 
                           class="btn btn-sm btn-secondary" target="_blank">
                            <i class="fas fa-print"></i> Print
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Issue Clearance Modal -->
<div class="modal fade" id="issueClearanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-exchange-alt"></i> Issue Clearance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Resident *</label>
                        <select class="form-select" id="customer_id" name="customer_id" required>
                            <option value="">Select Resident</option>
                            <?php while($row = $residents->fetch_assoc()): ?>
                            <option value="<?php echo $row['customer_id']; ?>">
                                <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="clearance_type" class="form-label">Clearance Type *</label>
                        <select class="form-select" id="clearance_type" name="clearance_type" required>
                            <option value="">Select Type</option>
                            <option value="Give">Give Clearance (Resident leaving)</option>
                            <option value="Receive">Receive Clearance (Resident arriving)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="source_field">
                        <label for="source_kebele" class="form-label">Source Kebele</label>
                        <input type="text" class="form-control" id="source_kebele" 
                               name="source_kebele" value="Ginjo Guduru" readonly>
                    </div>
                    
                    <div class="mb-3" id="destination_field">
                        <label for="destination_kebele" class="form-label">Destination Kebele *</label>
                        <input type="text" class="form-control" id="destination_kebele" 
                               name="destination_kebele" placeholder="Enter destination kebele" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="issue_date" class="form-label">Issue Date *</label>
                        <input type="date" class="form-control" id="issue_date" 
                               name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Issue Clearance</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#clearance_type').change(function() {
        let type = $(this).val();
        if (type == 'Give') {
            $('#source_field').show();
            $('#destination_field').show();
            $('#destination_kebele').prop('required', true);
        } else if (type == 'Receive') {
            $('#source_field').show();
            $('#destination_field').hide();
            $('#destination_kebele').prop('required', false);
        } else {
            $('#source_field').hide();
            $('#destination_field').hide();
        }
    });
    
    $('#clearance_type').trigger('change');
});
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>