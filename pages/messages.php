<?php
require_once '../config/database.php';
requireLogin();

$conn = getConnection();

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) 
                            VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $_SESSION['user_id'], $receiver_id, $subject, $message);
    
    if ($stmt->execute()) {
        $success = "Message sent successfully!";
    } else {
        $error = "Error sending message: " . $conn->error;
    }
}

// Handle marking as read
if (isset($_GET['mark_read'])) {
    $message_id = (int)$_GET['mark_read'];
    $conn->query("UPDATE messages SET is_read = 1 WHERE message_id = $message_id AND receiver_id = {$_SESSION['user_id']}");
    header("Location: messages.php");
    exit();
}

// Get inbox messages
$inbox = $conn->query("
    SELECT m.*, u.full_name as sender_name, u.username as sender_username
    FROM messages m
    JOIN users u ON m.sender_id = u.user_id
    WHERE m.receiver_id = {$_SESSION['user_id']}
    ORDER BY m.sent_at DESC
");

// Get sent messages
$sent = $conn->query("
    SELECT m.*, u.full_name as receiver_name, u.username as receiver_username
    FROM messages m
    JOIN users u ON m.receiver_id = u.user_id
    WHERE m.sender_id = {$_SESSION['user_id']}
    ORDER BY m.sent_at DESC
");

// Get users for sending messages (excluding current user)
$users = $conn->query("SELECT user_id, full_name, username, role_id FROM users WHERE user_id != {$_SESSION['user_id']} ORDER BY full_name");

// Count unread messages
$unread_count = $conn->query("SELECT COUNT(*) as count FROM messages WHERE receiver_id = {$_SESSION['user_id']} AND is_read = 0")->fetch_assoc()['count'];
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-envelope"></i> Messages 
                <?php if ($unread_count > 0): ?>
                <span class="badge bg-danger"><?php echo $unread_count; ?> unread</span>
                <?php endif; ?>
            </h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
                <i class="fas fa-pen"></i> Compose Message
            </button>
        </div>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4" id="messageTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" 
                    type="button" role="tab">Inbox 
                <?php if ($unread_count > 0): ?>
                <span class="badge bg-danger"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" 
                    type="button" role="tab">Sent</button>
        </li>
    </ul>

    <div class="tab-content" id="messageTabsContent">
        <!-- Inbox Tab -->
        <div class="tab-pane fade show active" id="inbox" role="tabpanel">
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>From</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($inbox->num_rows > 0): ?>
                            <?php while($msg = $inbox->fetch_assoc()): ?>
                            <tr class="<?php echo $msg['is_read'] ? '' : 'fw-bold'; ?>">
                                <td><?php echo $msg['sender_name']; ?></td>
                                <td><?php echo $msg['subject']; ?></td>
                                <td><?php echo substr($msg['message'], 0, 50); ?>...</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($msg['sent_at'])); ?></td>
                                <td>
                                    <?php if ($msg['is_read']): ?>
                                    <span class="badge bg-secondary">Read</span>
                                    <?php else: ?>
                                    <span class="badge bg-success">New</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewMessage(<?php echo $msg['message_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if (!$msg['is_read']): ?>
                                    <a href="?mark_read=<?php echo $msg['message_id']; ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No messages in inbox</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sent Tab -->
        <div class="tab-pane fade" id="sent" role="tabpanel">
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>To</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($sent->num_rows > 0): ?>
                            <?php while($msg = $sent->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $msg['receiver_name']; ?></td>
                                <td><?php echo $msg['subject']; ?></td>
                                <td><?php echo substr($msg['message'], 0, 50); ?>...</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($msg['sent_at'])); ?></td>
                                <td>
                                    <?php if ($msg['is_read']): ?>
                                    <span class="badge bg-success">Read</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Unread</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewMessage(<?php echo $msg['message_id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">No sent messages</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Compose Message Modal -->
<div class="modal fade" id="composeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-pen"></i> Compose Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="receiver_id" class="form-label">To *</label>
                        <select class="form-select" id="receiver_id" name="receiver_id" required>
                            <option value="">Select Recipient</option>
                            <?php while($user = $users->fetch_assoc()): ?>
                            <option value="<?php echo $user['user_id']; ?>">
                                <?php echo $user['full_name'] . ' (' . $user['username'] . ')'; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject *</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="message" class="form-label">Message *</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" name="send_message" value="1">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Message Modal -->
<div class="modal fade" id="viewMessageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewSubject"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>From:</strong> <span id="viewFrom"></span></p>
                <p><strong>Date:</strong> <span id="viewDate"></span></p>
                <hr>
                <div id="viewMessage" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewMessage(messageId) {
    // AJAX call to get message details
    $.ajax({
        url: 'get-message.php',
        method: 'POST',
        data: {message_id: messageId},
        success: function(response) {
            let msg = JSON.parse(response);
            $('#viewSubject').text(msg.subject);
            $('#viewFrom').text(msg.sender_name);
            $('#viewDate').text(msg.sent_at);
            $('#viewMessage').html(msg.message.replace(/\n/g, '<br>'));
            $('#viewMessageModal').modal('show');
            
            // Mark as read if it's in inbox
            <?php if (basename($_SERVER['PHP_SELF']) == 'messages.php'): ?>
            if (!msg.is_read) {
                $.ajax({
                    url: '?mark_read=' + messageId,
                    method: 'GET'
                });
            }
            <?php endif; ?>
        }
    });
}
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>