<?php
/**
 * NOTIFICATIONS PAGE
 * Display and manage user notifications
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

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Mark notification as read
if (isset($_GET['mark_read'])) {
    $notif_id = (int)$_GET['mark_read'];
    $conn->query("UPDATE notifications SET is_read = 1 WHERE notification_id = $notif_id AND user_id = $user_id");
    if (isset($_GET['redirect'])) {
        header("Location: " . $_GET['redirect']);
        exit();
    }
    header("Location: notifications.php");
    exit();
}

// Mark all as read
if (isset($_GET['mark_all_read'])) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");
    header("Location: notifications.php");
    exit();
}

// Delete notification
if (isset($_GET['delete'])) {
    $notif_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM notifications WHERE notification_id = $notif_id AND user_id = $user_id");
    header("Location: notifications.php");
    exit();
}

// Get notifications
$notifications = $conn->query("SELECT * FROM notifications WHERE user_id = $user_id ORDER BY created_at DESC");

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-bell"></i> Notifications
            </h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="?mark_all_read=1" class="btn btn-success">
                <i class="fas fa-check-double"></i> Mark All Read
            </a>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-list me-2"></i> Notification Center
        </div>
        <div class="card-body">
            <?php if ($notifications && $notifications->num_rows > 0): ?>
                <div class="list-group">
                    <?php while($notif = $notifications->fetch_assoc()): ?>
                    <div class="list-group-item list-group-item-action <?php echo !$notif['is_read'] ? 'list-group-item-primary' : ''; ?>">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 <?php echo !$notif['is_read'] ? 'fw-bold' : ''; ?>">
                                <?php echo htmlspecialchars($notif['title']); ?>
                                <?php if (!$notif['is_read']): ?>
                                    <span class="badge bg-danger ms-2">New</span>
                                <?php endif; ?>
                            </h6>
                            <small class="text-muted">
                                <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?>
                            </small>
                        </div>
                        <p class="mb-1"><?php echo htmlspecialchars($notif['message']); ?></p>
                        <div class="mt-2">
                            <?php if ($notif['link']): ?>
                            <a href="<?php echo $notif['link']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <?php endif; ?>
                            <?php if (!$notif['is_read']): ?>
                            <a href="?mark_read=<?php echo $notif['notification_id']; ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-check"></i> Mark Read
                            </a>
                            <?php endif; ?>
                            <a href="?delete=<?php echo $notif['notification_id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Delete this notification?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-bell-slash fa-4x text-muted mb-3"></i>
                    <h5>No Notifications</h5>
                    <p class="text-muted">You don't have any notifications at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>