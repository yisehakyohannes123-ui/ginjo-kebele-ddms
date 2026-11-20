<?php
/**
 * AJAX HANDLER for Notification Count
 * Returns JSON response only
 */

// Disable error reporting for AJAX response
ini_set('display_errors', 0);
error_reporting(0);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = [
    'count' => 0,
    'success' => true,
    'error' => ''
];

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['is_community'])) {
    $response['error'] = 'Not logged in';
    echo json_encode($response);
    exit();
}

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Get database connection
$conn = getConnection();
if (!$conn) {
    $response['error'] = 'Database connection failed';
    $response['success'] = false;
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;

if ($user_id > 0) {
    try {
        // Check if notifications table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($table_check && $table_check->num_rows > 0) {
            $result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
            if ($result) {
                $row = $result->fetch_assoc();
                $response['count'] = (int)$row['count'];
            }
        }
    } catch (Exception $e) {
        $response['error'] = 'Database error';
        $response['success'] = false;
    }
}

$conn->close();
echo json_encode($response);
exit();
?>