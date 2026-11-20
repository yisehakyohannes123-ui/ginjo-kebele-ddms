<?php
/**
 * LOGIN DIAGNOSTIC TOOL
 * Checks database setup and user accounts
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Login Diagnostic Tool</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #333; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔧 Login System Diagnostic Tool</h1>";

// Step 1: Check database connection
echo "<h2>Step 1: Database Connection</h2>";
require_once 'config/database.php';

$conn = getConnection();
if ($conn) {
    echo "<div class='success'>✅ Database connected successfully</div>";
} else {
    echo "<div class='error'>❌ Database connection failed</div>";
    exit();
}

// Step 2: Check if users table exists
echo "<h2>Step 2: Check Users Table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "<div class='success'>✅ Users table exists</div>";
} else {
    echo "<div class='error'>❌ Users table does not exist!</div>";
    echo "<p>Please import the database.sql file</p>";
    exit();
}

// Step 3: Check roles table
echo "<h2>Step 3: Check Roles Table</h2>";
$result = $conn->query("SELECT * FROM roles");
if ($result->num_rows > 0) {
    echo "<div class='success'>✅ Roles table exists</div>";
    echo "<table>";
    echo "<tr><th>Role ID</th><th>Role Name</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>" . $row['role_id'] . "</td><td>" . $row['role_name'] . "</td></tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ Roles table is empty!</div>";
}

// Step 4: Check users
echo "<h2>Step 4: Check Users</h2>";
$result = $conn->query("SELECT u.user_id, u.username, u.full_name, u.is_active, r.role_name 
                        FROM users u 
                        JOIN roles r ON u.role_id = r.role_id");
if ($result->num_rows > 0) {
    echo "<div class='success'>✅ Users found in database</div>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Active</th><th>Password Hash Valid?</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $active = $row['is_active'] ? '✅ Yes' : '❌ No';
        
        // Check if password hash is valid format
        $user_check = $conn->query("SELECT password FROM users WHERE user_id = " . $row['user_id']);
        $user_data = $user_check->fetch_assoc();
        $hash_valid = (strlen($user_data['password']) == 60 && substr($user_data['password'], 0, 4) == '$2y$') ? '✅ Valid' : '❌ Invalid';
        
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td><strong>" . $row['username'] . "</strong></td>";
        echo "<td>" . $row['full_name'] . "</td>";
        echo "<td>" . $row['role_name'] . "</td>";
        echo "<td>" . $active . "</td>";
        echo "<td>" . $hash_valid . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No users found in database!</div>";
}

// Step 5: Test specific user credentials
echo "<h2>Step 5: Test Admin Login</h2>";
$test_username = 'admin';
$test_password = 'admin123';

$stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.username = ? AND u.is_active = 1");
if ($stmt) {
    $stmt->bind_param("s", $test_username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "<div class='success'>✅ Admin user found</div>";
        if (password_verify($test_password, $row['password'])) {
            echo "<div class='success'>✅ Password correct for admin user</div>";
        } else {
            echo "<div class='error'>❌ Password incorrect for admin user</div>";
            echo "<div class='info'>Stored hash: " . $row['password'] . "</div>";
            
            // Generate correct hash for reference
            $correct_hash = password_hash($test_password, PASSWORD_DEFAULT);
            echo "<div class='info'>Correct hash for 'admin123' should be: " . $correct_hash . "</div>";
        }
    } else {
        echo "<div class='error'>❌ Admin user not found or inactive</div>";
    }
    $stmt->close();
} else {
    echo "<div class='error'>❌ Prepare failed: " . $conn->error . "</div>";
}

// Step 6: Fix script
echo "<h2>Step 6: Auto-Fix Option</h2>";
echo "<div class='info'>";
echo "<p>Click the button below to reset admin user with correct password:</p>";
echo "<form method='post' action='fix-admin.php'>";
echo "<button type='submit' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>🔧 Reset Admin User</button>";
echo "</form>";
echo "</div>";

echo "</div></body></html>";

$conn->close();
?>