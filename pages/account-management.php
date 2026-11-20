<?php
require_once '../config/database.php';
requireRole('System_Administrator');

$conn = getConnection();
$error = '';
$success = '';

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'] ?? null;
    $role_id = $_POST['role_id'];
    
    // Check if username exists
    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, gender, birth_date, role_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssi", $username, $hashed_password, $full_name, $email, $phone, $gender, $birth_date, $role_id);
        
        if ($stmt->execute()) {
            $success = "User created successfully!";
        } else {
            $error = "Error creating user: " . $conn->error;
        }
    }
}

// Handle user status toggle
if (isset($_GET['toggle_status'])) {
    $user_id = (int)$_GET['toggle_status'];
    $conn->query("UPDATE users SET is_active = NOT is_active WHERE user_id = $user_id");
    header("Location: account-management.php?msg=toggled");
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    if ($user_id != $_SESSION['user_id']) { // Can't delete yourself
        $conn->query("DELETE FROM users WHERE user_id = $user_id");
        header("Location: account-management.php?msg=deleted");
        exit();
    }
}

// Get all users
$users = $conn->query("
    SELECT u.*, r.role_name 
    FROM users u
    JOIN roles r ON u.role_id = r.role_id
    ORDER BY u.created_at DESC
");

// Get roles for dropdown
$roles = $conn->query("SELECT * FROM roles");
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-user-cog"></i> Account Management</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="fas fa-plus"></i> Create New User
            </button>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] == 'toggled'): ?>
        <div class="alert alert-info">User status updated successfully!</div>
        <?php elseif ($_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-warning">User deleted successfully!</div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="table-container">
        <table id="dataTable" class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $users->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['user_id']; ?></td>
                    <td><strong><?php echo $row['username']; ?></strong></td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td><?php echo $row['email'] ?? 'N/A'; ?></td>
                    <td><?php echo $row['phone'] ?? 'N/A'; ?></td>
                    <td>
                        <span class="badge bg-info"><?php echo $row['role_name']; ?></span>
                    </td>
                    <td>
                        <?php if ($row['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                        <?php else: ?>
                        <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <a href="edit-user.php?id=<?php echo $row['user_id']; ?>" 
                           class="btn btn-sm btn-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        
                        <?php if ($row['user_id'] != $_SESSION['user_id']): ?>
                            <a href="?toggle_status=<?php echo $row['user_id']; ?>" 
                               class="btn btn-sm btn-<?php echo $row['is_active'] ? 'secondary' : 'success'; ?>" 
                               title="<?php echo $row['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas fa-<?php echo $row['is_active'] ? 'ban' : 'check'; ?>"></i>
                            </a>
                            
                            <a href="?delete=<?php echo $row['user_id']; ?>" 
                               class="btn btn-sm btn-danger delete-confirm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Create New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="gender" class="form-label">Gender *</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="birth_date" class="form-label">Birth Date</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="role_id" class="form-label">Role *</label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php while($role = $roles->fetch_assoc()): ?>
                                <option value="<?php echo $role['role_id']; ?>">
                                    <?php echo $role['role_name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="create_user" value="1">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>