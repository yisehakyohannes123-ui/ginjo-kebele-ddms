<?php
/**
 * ID MANAGEMENT PAGE
 * Display and manage ID cards
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

// Get all IDs
$query = "SELECT i.*, c.first_name, c.last_name, c.customer_id,
                 u.full_name as issued_by_name
          FROM ids i
          JOIN customers c ON i.customer_id = c.customer_id
          JOIN users u ON i.issued_by = u.user_id
          ORDER BY i.issued_date DESC";

$ids = $conn->query($query);

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-id-card"></i> ID Management
            </h2>
            <p class="text-muted">
                <?php if ($is_community): ?>
                    View all issued ID cards
                <?php else: ?>
                    Issue new IDs, renew existing ones, and manage ID records
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <?php if (!$is_community && (hasRole('Kebele_Manager') || hasRole('System_Administrator'))): ?>
            <a href="issue-id.php" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Issue New ID
            </a>
            <a href="renew-id.php" class="btn btn-warning">
                <i class="fas fa-sync-alt"></i> Renew ID
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- IDs Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-list me-2"></i> Issued ID Cards
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="dataTable">
                    <thead>
                        <tr>
                            <th>ID Number</th>
                            <th>Resident</th>
                            <th>Issue Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Issued By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($ids && $ids->num_rows > 0): ?>
                            <?php while($row = $ids->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo $row['id_number']; ?></strong></td>
                                <td>
                                    <a href="view-resident.php?id=<?php echo $row['customer_id']; ?>">
                                        <?php echo htmlspecialchars($row['first_name'] . ' ' . ($row['last_name'] ?? '')); ?>
                                    </a>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['issued_date'])); ?></td>
                                <td>
                                    <?php 
                                    if ($row['expiry_date']) {
                                        echo date('d/m/Y', strtotime($row['expiry_date']));
                                    } else {
                                        echo 'Lifetime';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($row['is_renewed']) {
                                        echo '<span class="badge bg-warning">Renewed</span>';
                                    } else {
                                        $expiry = $row['expiry_date'] ? strtotime($row['expiry_date']) : null;
                                        $now = time();
                                        if ($expiry && $expiry < $now) {
                                            echo '<span class="badge bg-danger">Expired</span>';
                                        } else {
                                            echo '<span class="badge bg-success">Active</span>';
                                        }
                                    }
                                    ?>
                                </td>
                                <td><?php echo $row['issued_by_name']; ?></td>
                                <td>
                                    <a href="view-id.php?id=<?php echo $row['id_number']; ?>" 
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (!$is_community && (hasRole('Kebele_Manager') || hasRole('System_Administrator'))): ?>
                                    <a href="print-id.php?id=<?php echo $row['id_number']; ?>" 
                                       class="btn btn-sm btn-secondary" title="Print" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-info-circle me-2"></i> No IDs issued yet
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