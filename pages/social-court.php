<?php
require_once '../config/database.php';
requireRole('Kebele_Manager');

$conn = getConnection();
$error = '';
$success = '';

// Handle case creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'add_case') {
        $customer_id = $_POST['customer_id'];
        $case_type = $_POST['case_type'];
        $description = $_POST['description'];
        $hearing_date = $_POST['hearing_date'];
        $case_number = 'CASE-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $stmt = $conn->prepare("INSERT INTO social_court_cases 
                                (case_number, customer_id, case_type, description, hearing_date, recorded_by) 
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssi", $case_number, $customer_id, $case_type, $description, $hearing_date, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "Case created successfully! Case Number: " . $case_number;
        } else {
            $error = "Error creating case: " . $conn->error;
        }
    } elseif ($_POST['action'] == 'update_verdict') {
        $case_id = $_POST['case_id'];
        $verdict = $_POST['verdict'];
        $status = $_POST['status'];
        $decision_date = date('Y-m-d');
        
        $stmt = $conn->prepare("UPDATE social_court_cases 
                                SET verdict = ?, status = ?, decision_date = ? 
                                WHERE case_id = ?");
        $stmt->bind_param("sssi", $verdict, $status, $decision_date, $case_id);
        
        if ($stmt->execute()) {
            $success = "Case updated successfully!";
        } else {
            $error = "Error updating case: " . $conn->error;
        }
    }
}

// Get all cases
$cases = $conn->query("
    SELECT s.*, c.first_name, c.last_name, c.customer_id,
           u.full_name as recorded_by_name
    FROM social_court_cases s
    JOIN customers c ON s.customer_id = c.customer_id
    JOIN users u ON s.recorded_by = u.user_id
    ORDER BY s.hearing_date DESC
");

// Get residents for dropdown
$residents = $conn->query("SELECT customer_id, first_name, last_name FROM customers WHERE is_active = 1");
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-gavel"></i> Social Court Management</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCaseModal">
                <i class="fas fa-plus"></i> New Case
            </button>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- Cases Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
                <div class="stat-value">
                    <?php
                    $pending = $conn->query("SELECT COUNT(*) as count FROM social_court_cases WHERE status = 'Pending'");
                    echo $pending->fetch_assoc()['count'];
                    ?>
                </div>
                <div class="stat-label">Pending Cases</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #27ae60, #229954);">
                <div class="stat-value">
                    <?php
                    $decided = $conn->query("SELECT COUNT(*) as count FROM social_court_cases WHERE status = 'Decided'");
                    echo $decided->fetch_assoc()['count'];
                    ?>
                </div>
                <div class="stat-label">Decided Cases</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <div class="stat-value">
                    <?php
                    $this_month = $conn->query("SELECT COUNT(*) as count FROM social_court_cases WHERE MONTH(hearing_date) = MONTH(CURDATE())");
                    echo $this_month->fetch_assoc()['count'];
                    ?>
                </div>
                <div class="stat-label">This Month</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <div class="stat-value">
                    <?php
                    $today = $conn->query("SELECT COUNT(*) as count FROM social_court_cases WHERE hearing_date = CURDATE()");
                    echo $today->fetch_assoc()['count'];
                    ?>
                </div>
                <div class="stat-label">Today's Hearings</div>
            </div>
        </div>
    </div>

    <!-- Cases Table -->
    <div class="table-container">
        <table id="dataTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Case #</th>
                    <th>Resident</th>
                    <th>Case Type</th>
                    <th>Hearing Date</th>
                    <th>Status</th>
                    <th>Recorded By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $cases->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo $row['case_number']; ?></strong></td>
                    <td>
                        <a href="view-resident.php?id=<?php echo $row['customer_id']; ?>">
                            <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                        </a>
                    </td>
                    <td><?php echo $row['case_type']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($row['hearing_date'])); ?></td>
                    <td>
                        <?php
                        $status_class = '';
                        if ($row['status'] == 'Pending') $status_class = 'warning';
                        elseif ($row['status'] == 'Decided') $status_class = 'success';
                        elseif ($row['status'] == 'Closed') $status_class = 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </td>
                    <td><?php echo $row['recorded_by_name']; ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info" 
                                onclick="viewCase(<?php echo $row['case_id']; ?>)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if ($row['status'] == 'Pending'): ?>
                        <button type="button" class="btn btn-sm btn-success" 
                                onclick="addVerdict(<?php echo $row['case_id']; ?>)">
                            <i class="fas fa-gavel"></i>
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Case Modal -->
<div class="modal fade" id="addCaseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-gavel"></i> New Social Court Case</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_case">
                    
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
                        <label for="case_type" class="form-label">Case Type *</label>
                        <input type="text" class="form-control" id="case_type" name="case_type" 
                               placeholder="e.g., Land Dispute, Family Matter, etc." required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Case Description *</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="hearing_date" class="form-label">Hearing Date *</label>
                        <input type="datetime-local" class="form-control" id="hearing_date" 
                               name="hearing_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Case</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Verdict Modal -->
<div class="modal fade" id="verdictModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-gavel"></i> Add Verdict</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_verdict">
                    <input type="hidden" name="case_id" id="verdict_case_id">
                    
                    <div class="mb-3">
                        <label for="verdict" class="form-label">Verdict *</label>
                        <textarea class="form-control" id="verdict" name="verdict" 
                                  rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Decided">Decided</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Verdict</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewCase(caseId) {
    // AJAX call to get case details
    $.ajax({
        url: 'get-case-details.php',
        method: 'POST',
        data: {case_id: caseId},
        success: function(response) {
            // Show case details in a modal or expand row
            alert('Case details: ' + response);
        }
    });
}

function addVerdict(caseId) {
    $('#verdict_case_id').val(caseId);
    $('#verdictModal').modal('show');
}
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>