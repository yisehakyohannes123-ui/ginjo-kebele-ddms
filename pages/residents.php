<?php
/**
 * RESIDENTS MANAGEMENT PAGE
 * Display and manage all residents
 */

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
if (!$conn) {
    die("Database connection failed. Please check your database configuration.");
}

$is_community = isCommunity();

// Handle search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$limit = 10;
$offset = ($page - 1) * $limit;

// Initialize variables
$residents = false;
$total_rows = 0;
$total_pages = 1;
$error_message = '';

try {
    // Build query conditions
    $where_conditions = ["1=1"];
    $params = [];
    $types = "";

    if (!empty($search)) {
        $where_conditions[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR c.customer_id LIKE ? OR k.kebele_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ssss";
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Get total count
    $count_sql = "SELECT COUNT(*) as total 
                  FROM customers c
                  LEFT JOIN houses h ON c.house_id = h.house_id
                  LEFT JOIN kebeles k ON h.kebele_id = k.kebele_id
                  WHERE $where_clause";
    
    if (!empty($params)) {
        $count_stmt = $conn->prepare($count_sql);
        if ($count_stmt) {
            $count_stmt->bind_param($types, ...$params);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $total_rows = $count_result->fetch_assoc()['total'];
            $count_stmt->close();
        } else {
            // Fallback if prepare fails
            error_log("Count prepare failed: " . $conn->error);
            // Try with a simpler query
            $simple_count = $conn->query("SELECT COUNT(*) as total FROM customers");
            if ($simple_count) {
                $total_rows = $simple_count->fetch_assoc()['total'];
            }
        }
    } else {
        $count_result = $conn->query($count_sql);
        if ($count_result) {
            $total_rows = $count_result->fetch_assoc()['total'];
        } else {
            error_log("Count query failed: " . $conn->error);
        }
    }
    
    $total_pages = ceil($total_rows / $limit);

    // Get residents with pagination
    $query = "SELECT c.*, h.house_number, k.kebele_name, j.job_title 
              FROM customers c
              LEFT JOIN houses h ON c.house_id = h.house_id
              LEFT JOIN kebeles k ON h.kebele_id = k.kebele_id
              LEFT JOIN jobs j ON c.job_id = j.job_id
              WHERE $where_clause
              ORDER BY c.customer_id DESC
              LIMIT ? OFFSET ?";

    if (!empty($params)) {
        // Add pagination parameters
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $residents = $stmt->get_result();
            // Don't close $stmt yet - we'll close it at the end
        } else {
            error_log("Residents prepare failed: " . $conn->error);
            // Fallback to simple query without search
            $fallback_query = "SELECT c.*, h.house_number, k.kebele_name, j.job_title 
                              FROM customers c
                              LEFT JOIN houses h ON c.house_id = h.house_id
                              LEFT JOIN kebeles k ON h.kebele_id = k.kebele_id
                              LEFT JOIN jobs j ON c.job_id = j.job_id
                              ORDER BY c.customer_id DESC
                              LIMIT ? OFFSET ?";
            $fallback_stmt = $conn->prepare($fallback_query);
            if ($fallback_stmt) {
                $fallback_stmt->bind_param("ii", $limit, $offset);
                $fallback_stmt->execute();
                $residents = $fallback_stmt->get_result();
            }
        }
    } else {
        // No search params - simple query with limit
        $stmt = $conn->prepare("SELECT c.*, h.house_number, k.kebele_name, j.job_title 
                                FROM customers c
                                LEFT JOIN houses h ON c.house_id = h.house_id
                                LEFT JOIN kebeles k ON h.kebele_id = k.kebele_id
                                LEFT JOIN jobs j ON c.job_id = j.job_id
                                ORDER BY c.customer_id DESC
                                LIMIT ? OFFSET ?");
        if ($stmt) {
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $residents = $stmt->get_result();
        } else {
            error_log("Residents prepare failed (no search): " . $conn->error);
            // Ultimate fallback - try a simple query without prepared statement
            $simple_query = "SELECT c.*, h.house_number, k.kebele_name, j.job_title 
                            FROM customers c
                            LEFT JOIN houses h ON c.house_id = h.house_id
                            LEFT JOIN kebeles k ON h.kebele_id = k.kebele_id
                            LEFT JOIN jobs j ON c.job_id = j.job_id
                            ORDER BY c.customer_id DESC
                            LIMIT $limit OFFSET $offset";
            $residents = $conn->query($simple_query);
        }
    }

} catch (Exception $e) {
    error_log("Residents page error: " . $e->getMessage());
    $residents = false;
    $total_rows = 0;
    $total_pages = 1;
    $error_message = "An error occurred while fetching residents. Please try again.";
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-users"></i> Residents Management
            </h2>
            <p class="text-muted">
                <?php if ($is_community): ?>
                    View all registered residents
                <?php else: ?>
                    Manage resident information, add new residents, and update records
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <?php if (!$is_community && (hasRole('System_Administrator') || hasRole('Kebele_Manager') || hasRole('Record_Officer'))): ?>
            <a href="add-resident.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add New Resident
            </a>
            <?php endif; ?>
            <a href="dashboard.php" class="btn btn-secondary ms-2">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Error Alert -->
    <?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-md-12">
            <form method="GET" class="d-flex" id="searchForm">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search by name, ID, or kebele..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           id="searchInput">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                    <a href="residents.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Residents Table -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-2"></i> Residents List</span>
            <span class="badge bg-light text-dark">Total: <?php echo number_format($total_rows); ?></span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Birth Date</th>
                            <th>House #</th>
                            <th>Kebele</th>
                            <th>Occupation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($residents && $residents->num_rows > 0): ?>
                            <?php while($row = $residents->fetch_assoc()): ?>
                            <tr class="resident-row" data-id="<?php echo $row['customer_id']; ?>">
                                <td>
                                    <span class="fw-bold"><?php echo $row['customer_id']; ?></span>
                                </td>
                                <td>
                                    <a href="view-resident.php?id=<?php echo $row['customer_id']; ?>" class="text-decoration-none fw-bold">
                                        <?php echo htmlspecialchars($row['first_name'] . ' ' . ($row['last_name'] ?? '')); ?>
                                    </a>
                                </td>
                                <td><?php echo $row['gender'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['birth_date'] ? date('d/m/Y', strtotime($row['birth_date'])) : 'N/A'; ?></td>
                                <td><?php echo $row['house_number'] ?? 'N/A'; ?></td>
                                <td><?php echo $row['kebele_name'] ?? 'N/A'; ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $row['job_title'] ?? 'N/A'; ?></span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view-resident.php?id=<?php echo $row['customer_id']; ?>" 
                                           class="btn btn-sm btn-info" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (!$is_community && (hasRole('System_Administrator') || hasRole('Kebele_Manager') || hasRole('Record_Officer'))): ?>
                                        <a href="edit-resident.php?id=<?php echo $row['customer_id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Edit Resident">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if (!$is_community && hasRole('System_Administrator')): ?>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="confirmDelete(<?php echo $row['customer_id']; ?>)"
                                                title="Delete Resident">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5>No Residents Found</h5>
                                    <p class="text-muted">
                                        <?php if (!empty($search)): ?>
                                            No results match your search criteria "<strong><?php echo htmlspecialchars($search); ?></strong>".
                                        <?php else: ?>
                                            There are no residents registered in the system yet.
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($search)): ?>
                                    <a href="residents.php" class="btn btn-primary">
                                        <i class="fas fa-times"></i> Clear Search
                                    </a>
                                    <?php elseif (!$is_community && (hasRole('System_Administrator') || hasRole('Kebele_Manager') || hasRole('Record_Officer'))): ?>
                                    <a href="add-resident.php" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Add First Resident
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php
                    // Show pagination with ellipsis for many pages
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if ($start > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '">1</a></li>';
                        if ($start > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($end < $total_pages): ?>
                        <?php if ($end < $total_pages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $total_pages; ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <!-- Results info -->
            <?php if ($residents && $residents->num_rows > 0): ?>
            <div class="text-muted small mt-3 text-center">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_rows); ?> of <?php echo $total_rows; ?> residents
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this resident?</p>
                <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. All associated records (IDs, certificates, etc.) will also be affected.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Resident
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Custom Styles -->
<style>
/* Table row hover effect */
.resident-row {
    cursor: pointer;
    transition: background-color 0.2s;
}

.resident-row:hover {
    background-color: rgba(102, 126, 234, 0.1) !important;
}

.resident-row td {
    vertical-align: middle;
}

/* Action buttons group */
.btn-group {
    opacity: 0.8;
    transition: opacity 0.2s;
}

.resident-row:hover .btn-group {
    opacity: 1;
}

/* Badge styling */
.badge.bg-info {
    background: linear-gradient(135deg, #3498db, #2980b9) !important;
    padding: 5px 10px;
    font-weight: 500;
}

/* Page title */
.page-title {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 5px;
}

.page-title i {
    color: #667eea;
    margin-right: 10px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* Search input */
.input-group .form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Card header */
.card-header.bg-primary {
    background: linear-gradient(135deg, #667eea, #764ba2) !important;
}

/* Pagination */
.pagination .page-link {
    color: #667eea;
    border: none;
    margin: 0 3px;
    border-radius: 8px;
    transition: all 0.2s;
}

.pagination .page-link:hover {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    transform: translateY(-2px);
}

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-color: transparent;
}

/* Responsive */
@media (max-width: 768px) {
    .btn-group {
        opacity: 1;
    }
    
    .table td, .table th {
        padding: 8px 5px;
        font-size: 0.9rem;
    }
}
</style>

<!-- Dashboard JavaScript -->
<script>
$(document).ready(function() {
    // Make entire row clickable
    $('.resident-row').on('click', function(e) {
        // Don't trigger if clicking on buttons or links
        if (!$(e.target).closest('a, button, .btn-group').length) {
            var id = $(this).data('id');
            window.location.href = 'view-resident.php?id=' + id;
        }
    });
    
    // Add hover effect
    $('.resident-row').hover(
        function() {
            $(this).find('.fw-bold').css('color', '#667eea');
        },
        function() {
            $(this).find('.fw-bold').css('color', '');
        }
    );
    
    // Prevent double form submission
    $('#searchForm').on('submit', function() {
        $(this).find('button[type="submit"]').prop('disabled', true);
    });
});

// Confirm delete function
function confirmDelete(id) {
    $('#confirmDeleteBtn').attr('href', 'delete-resident.php?id=' + id);
    $('#deleteModal').modal('show');
}

// Export to CSV function
function exportToCSV() {
    alert('Export to CSV feature coming soon!');
}

// Print list function
function printList() {
    window.print();
}

// Quick search with debounce
let searchTimeout;
$('#searchInput').on('keyup', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        $('#searchForm').submit();
    }, 500);
});
</script>

<?php
// Safely close any open statements
if (isset($stmt) && $stmt !== false) {
    $stmt->close();
}
if (isset($count_stmt) && $count_stmt !== false) {
    $count_stmt->close();
}
if (isset($fallback_stmt) && $fallback_stmt !== false) {
    $fallback_stmt->close();
}

// Close database connection
if ($conn) {
    $conn->close();
}

include '../includes/footer.php';
?>