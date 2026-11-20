<?php
/**
 * FIX ADMIN USER
 * Resets admin user with correct password
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Admin User</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔧 Fix Admin User</h1>";

$conn = getConnection();
if (!$conn) {
    echo "<div class='error'>❌ Database connection failed</div>";
    exit();
}

// First, ensure roles exist
$conn->query("INSERT IGNORE INTO roles (role_id, role_name) VALUES 
              (1, 'System_Administrator'),
              (2, 'Kebele_Manager'),
              (3, 'Record_Officer')");

// Generate correct password hash
$password = 'admin123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Check if admin exists
$check = $conn->query("SELECT user_id FROM users WHERE username = 'admin'");

if ($check->num_rows > 0) {
    // Update existing admin
    $sql = "UPDATE users SET 
            password = '$hashed_password', 
            is_active = 1,
            role_id = 1
            WHERE username = 'admin'";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Admin user updated successfully!</div>";
    } else {
        echo "<div class='error'>❌ Error updating admin: " . $conn->error . "</div>";
    }
} else {
    // Create new admin
    $sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
            VALUES ('admin', '$hashed_password', 'System Administrator', 'admin@ginjo.gov.et', '0912345678', 'Male', 1, 1)";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Admin user created successfully!</div>";
    } else {
        echo "<div class='error'>❌ Error creating admin: " . $conn->error . "</div>";
    }
}

// Also create/fix manager and officer
$manager_pass = password_hash('manager123', PASSWORD_DEFAULT);
$conn->query("INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
              VALUES ('manager', '$manager_pass', 'Kebele Manager', 'manager@ginjo.gov.et', '0912345679', 'Male', 2, 1)
              ON DUPLICATE KEY UPDATE password = '$manager_pass', is_active = 1");

$officer_pass = password_hash('officer123', PASSWORD_DEFAULT);
$conn->query("INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
              VALUES ('officer', '$officer_pass', 'Record Officer', 'officer@ginjo.gov.et', '0912345680', 'Female', 3, 1)
              ON DUPLICATE KEY UPDATE password = '$officer_pass', is_active = 1");

echo "<div class='success'>✅ All users configured!</div>";
echo "<div class='success'>";
echo "<strong>Login credentials:</strong><br>";
echo "Admin: admin / admin123<br>";
echo "Manager: manager / manager123<br>";
echo "Officer: officer / officer123<br>";
echo "</div>";

echo "<p><a href='pages/login.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

echo "</div></body></html>";

$conn->close();
?>