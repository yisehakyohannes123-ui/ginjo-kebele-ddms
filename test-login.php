<?php
/**
 * SIMPLE LOGIN TEST
 * Use this to test if login works without the UI
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$conn = getConnection();
if (!$conn) {
    die("Database connection failed");
}

echo "<h1>🔐 Simple Login Test</h1>";

// Test login function
function testLogin($username, $password, $expected_role) {
    global $conn;
    
    echo "<h3>Testing: $username / $password (Expected: $expected_role)</h3>";
    
    $stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "✅ User found in database<br>";
        echo "User ID: " . $row['user_id'] . "<br>";
        echo "Full Name: " . $row['full_name'] . "<br>";
        echo "Role: " . $row['role_name'] . "<br>";
        echo "Active: " . ($row['is_active'] ? 'Yes' : 'No') . "<br>";
        
        if (password_verify($password, $row['password'])) {
            echo "✅ Password correct<br>";
            
            // Set session
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['user_role'] = $row['role_name'];
            
            echo "✅ Session variables set<br>";
            echo "Session user_id: " . $_SESSION['user_id'] . "<br>";
            echo "Session user_role: " . $_SESSION['user_role'] . "<br>";
            
            echo "<a href='pages/dashboard.php' target='_blank'>Click to go to Dashboard</a><br>";
            
        } else {
            echo "❌ Password incorrect<br>";
            // Show hash for debugging
            echo "Stored hash: " . $row['password'] . "<br>";
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            echo "Correct hash for '$password' should be: " . $new_hash . "<br>";
        }
    } else {
        echo "❌ User not found<br>";
    }
    echo "<hr>";
}

// Test all users
testLogin('admin', 'admin123', 'System_Administrator');
testLogin('manager', 'manager123', 'Kebele_Manager');
testLogin('officer', 'officer123', 'Record_Officer');

echo "<p><a href='index.php'>Back to Login Page</a></p>";

$conn->close();
?>