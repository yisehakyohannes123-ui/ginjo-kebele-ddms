<?php
/**
 * RESET ALL USERS
 * Run this to fix user accounts
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Reset Users</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔄 Reset All Users</h1>";

require_once 'config/database.php';

$conn = getConnection();
if (!$conn) {
    echo "<div class='error'>❌ Database connection failed</div>";
    exit();
}

// First, ensure roles exist
$conn->query("CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL
)");

$conn->query("INSERT IGNORE INTO roles (role_id, role_name) VALUES 
              (1, 'System_Administrator'),
              (2, 'Kebele_Manager'),
              (3, 'Record_Officer')");

echo "<div class='success'>✅ Roles table ready</div>";

// Clear existing users (optional - comment out if you want to keep existing)
// $conn->query("DELETE FROM users WHERE username IN ('admin', 'manager', 'officer')");
// echo "<div class='info'>✅ Cleared existing test users</div>";

// Create admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
              VALUES ('admin', '$admin_password', 'System Administrator', 'admin@ginjo.gov.et', '0912345678', 'Male', 1, 1)
              ON DUPLICATE KEY UPDATE 
              password = '$admin_password', 
              full_name = 'System Administrator',
              email = 'admin@ginjo.gov.et',
              phone = '0912345678',
              role_id = 1,
              is_active = 1";

if ($conn->query($admin_sql)) {
    echo "<div class='success'>✅ Admin user created/updated</div>";
} else {
    echo "<div class='error'>❌ Error creating admin: " . $conn->error . "</div>";
}

// Create manager user
$manager_password = password_hash('manager123', PASSWORD_DEFAULT);
$manager_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                VALUES ('manager', '$manager_password', 'Kebele Manager', 'manager@ginjo.gov.et', '0912345679', 'Male', 2, 1)
                ON DUPLICATE KEY UPDATE 
                password = '$manager_password',
                full_name = 'Kebele Manager',
                email = 'manager@ginjo.gov.et',
                phone = '0912345679',
                role_id = 2,
                is_active = 1";

if ($conn->query($manager_sql)) {
    echo "<div class='success'>✅ Manager user created/updated</div>";
} else {
    echo "<div class='error'>❌ Error creating manager: " . $conn->error . "</div>";
}

// Create officer user
$officer_password = password_hash('officer123', PASSWORD_DEFAULT);
$officer_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                VALUES ('officer', '$officer_password', 'Record Officer', 'officer@ginjo.gov.et', '0912345680', 'Female', 3, 1)
                ON DUPLICATE KEY UPDATE 
                password = '$officer_password',
                full_name = 'Record Officer',
                email = 'officer@ginjo.gov.et',
                phone = '0912345680',
                role_id = 3,
                is_active = 1";

if ($conn->query($officer_sql)) {
    echo "<div class='success'>✅ Officer user created/updated</div>";
} else {
    echo "<div class='error'>❌ Error creating officer: " . $conn->error . "</div>";
}

// Show final users
$result = $conn->query("SELECT u.user_id, u.username, u.full_name, u.is_active, r.role_name 
                        FROM users u 
                        JOIN roles r ON u.role_id = r.role_id");

echo "<h3>Current Users:</h3>";
echo "<table border='1' cellpadding='5' style='width:100%; border-collapse:collapse;'>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Active</th></tr>";

while ($row = $result->fetch_assoc()) {
    $active = $row['is_active'] ? '✅' : '❌';
    echo "<tr>";
    echo "<td>" . $row['user_id'] . "</td>";
    echo "<td><strong>" . $row['username'] . "</strong></td>";
    echo "<td>" . $row['full_name'] . "</td>";
    echo "<td>" . $row['role_name'] . "</td>";
    echo "<td>" . $active . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div class='success' style='margin-top:20px;'>";
echo "<h3>✅ Login Credentials:</h3>";
echo "<p><strong>Admin:</strong> admin / admin123</p>";
echo "<p><strong>Manager:</strong> manager / manager123</p>";
echo "<p><strong>Officer:</strong> officer / officer123</p>";
echo "</div>";

echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";
echo "<p><a href='test-login.php' style='display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Run Login Test</a></p>";

echo "</div></body></html>";

$conn->close();
?>