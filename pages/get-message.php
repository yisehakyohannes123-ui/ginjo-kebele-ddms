<?php
require_once '../config/database.php';
requireLogin();

if (isset($_POST['message_id'])) {
    $conn = getConnection();
    $message_id = (int)$_POST['message_id'];
    
    $stmt = $conn->prepare("
        SELECT m.*, 
               sender.full_name as sender_name,
               receiver.full_name as receiver_name
        FROM messages m
        JOIN users sender ON m.sender_id = sender.user_id
        JOIN users receiver ON m.receiver_id = receiver.user_id
        WHERE m.message_id = ?
    ");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    }
    
    $conn->close();
}
?>