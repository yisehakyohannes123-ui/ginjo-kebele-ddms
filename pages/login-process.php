<?php
/**
 * LOGIN PROCESS HANDLER - FIXED VERSION
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../config/database.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header("Location: ../index.php?error=invalid");
    exit();
}

// Get form data
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role = isset($_POST['role']) ? trim($_POST['role']) : '';

// Validate input
if (empty($username) || empty($password) || empty($role)) {
    header("Location: ../index.php?error=invalid");
    exit();
}

// Map role to role_id
$role_map = [
    'admin' => 1,
    'manager' => 2,
    'officer' => 3
];

if (!isset($role_map[$role])) {
    header("Location: ../index.php?error=invalid");
    exit();
}

$expected_role_id = $role_map[$role];

// Get database connection
$conn = getConnection();

if (!$conn) {
    error_log("Login failed: Database connection error");
    header("Location: ../index.php?error=system");
    exit();
}

// First, check if tables exist
$tables_check = $conn->query("SHOW TABLES");
$tables = [];
while ($row = $tables_check->fetch_array()) {
    $tables[] = $row[0];
}

if (!in_array('users', $tables)) {
    error_log("Users table does not exist. Available tables: " . implode(', ', $tables));
    header("Location: ../index.php?error=system");
    exit();
}

if (!in_array('roles', $tables)) {
    error_log("Roles table does not exist");
    header("Location: ../index.php?error=system");
    exit();
}

// Simple query to find user
$sql = "SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.username = ? AND u.is_active = 1";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    error_log("SQL: " . $sql);
    header("Location: ../index.php?error=system");
    exit();
}

$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    error_log("Login failed: User not found or inactive - $username");
    header("Location: ../index.php?error=invalid");
    exit();
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    error_log("Login failed: Invalid password for user $username");
    header("Location: ../index.php?error=invalid");
    exit();
}

// Check if role matches
if ($user['role_id'] != $expected_role_id) {
    error_log("Login failed: Role mismatch. Expected: $expected_role_id, Found: " . $user['role_id']);
    header("Location: ../index.php?error=invalid");
    exit();
}

// Login successful - set session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['user_role'] = $user['role_name'];
$_SESSION['is_community'] = false;
$_SESSION['last_activity'] = time();

error_log("Login successful: $username as " . $user['role_name']);

// Redirect to dashboard
header("Location: dashboard.php");
exit();
?>