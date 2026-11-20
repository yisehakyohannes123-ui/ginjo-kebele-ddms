<?php
/**
 * CREATE TEST USERS
 * Run this file once to create admin, manager, and officer users
 */

require_once 'config/database.php';

echo "<h1>Creating Test Users</h1>";

$conn = getConnection();
if (!$conn) {
    die("Database connection failed");
}

// First, ensure roles exist
$conn->query("INSERT IGNORE INTO roles (role_id, role_name) VALUES 
              (1, 'System_Administrator'),
              (2, 'Kebele_Manager'),
              (3, 'Record_Officer')");

// Create admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
              VALUES ('admin', '$admin_password', 'System Administrator', 'admin@ginjo.gov.et', '0912345678', 'Male', 1, 1)
              ON DUPLICATE KEY UPDATE password = '$admin_password', is_active = 1";

if ($conn->query($admin_sql)) {
    echo "<p style='color: green'>✅ Admin user created/updated</p>";
} else {
    echo "<p style='color: red'>❌ Error: " . $conn->error . "</p>";
}

// Create manager user
$manager_password = password_hash('manager123', PASSWORD_DEFAULT);
$manager_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                VALUES ('manager', '$manager_password', 'Kebele Manager', 'manager@ginjo.gov.et', '0912345679', 'Male', 2, 1)
                ON DUPLICATE KEY UPDATE password = '$manager_password', is_active = 1";

if ($conn->query($manager_sql)) {
    echo "<p style='color: green'>✅ Manager user created/updated</p>";
} else {
    echo "<p style='color: red'>❌ Error: " . $conn->error . "</p>";
}

// Create officer user
$officer_password = password_hash('officer123', PASSWORD_DEFAULT);
$officer_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                VALUES ('officer', '$officer_password', 'Record Officer', 'officer@ginjo.gov.et', '0912345680', 'Female', 3, 1)
                ON DUPLICATE KEY UPDATE password = '$officer_password', is_active = 1";

if ($conn->query($officer_sql)) {
    echo "<p style='color: green'>✅ Officer user created/updated</p>";
} else {
    echo "<p style='color: red'>❌ Error: " . $conn->error . "</p>";
}

// Show all users
$result = $conn->query("SELECT u.user_id, u.username, u.full_name, u.is_active, r.role_name 
                        FROM users u 
                        JOIN roles r ON u.role_id = r.role_id");

echo "<h2>Current Users:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Active</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['user_id'] . "</td>";
    echo "<td><strong>" . $row['username'] . "</strong></td>";
    echo "<td>" . $row['full_name'] . "</td>";
    echo "<td>" . $row['role_name'] . "</td>";
    echo "<td>" . ($row['is_active'] ? '✅' : '❌') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='index.php'>Go to Login Page</a></p>";

$conn->close();
?>