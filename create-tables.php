<?php
/**
 * CREATE DATABASE TABLES
 * Run this to create all necessary tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Create Database Tables</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔧 Creating Database Tables</h1>";

// Connect to MySQL (without database)
$conn = new mysqli('localhost', 'root', '');

if ($conn->connect_error) {
    echo "<div class='error'>❌ Connection failed: " . $conn->connect_error . "</div>";
    exit();
}

echo "<div class='success'>✅ Connected to MySQL</div>";

// Create database if not exists
$dbname = 'ginjo_kebele_ddms';
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8 COLLATE utf8_general_ci");
echo "<div class='success'>✅ Database '$dbname' created or already exists</div>";

// Select the database
$conn->select_db($dbname);

// Create roles table
$sql = "CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL
)";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Roles table created</div>";
} else {
    echo "<div class='error'>❌ Error creating roles table: " . $conn->error . "</div>";
}

// Insert default roles
$conn->query("INSERT IGNORE INTO roles (role_id, role_name) VALUES 
              (1, 'System_Administrator'),
              (2, 'Kebele_Manager'),
              (3, 'Record_Officer')");
echo "<div class='success'>✅ Default roles inserted</div>";

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    gender ENUM('Male', 'Female', 'Other'),
    birth_date DATE,
    role_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
)";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Users table created</div>";
} else {
    echo "<div class='error'>❌ Error creating users table: " . $conn->error . "</div>";
}

// Create customers table
$sql = "CREATE TABLE IF NOT EXISTS customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50),
    gender ENUM('Male', 'Female', 'Other'),
    birth_date DATE,
    house_id INT,
    job_id INT,
    email VARCHAR(100),
    phone VARCHAR(20),
    registration_date DATE,
    is_active BOOLEAN DEFAULT TRUE
)";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Customers table created</div>";
} else {
    echo "<div class='error'>❌ Error creating customers table: " . $conn->error . "</div>";
}

// Create ids table
$sql = "CREATE TABLE IF NOT EXISTS ids (
    id_number INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    issued_date DATE NOT NULL,
    expiry_date DATE,
    issued_by INT,
    is_renewed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (issued_by) REFERENCES users(user_id)
)";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ IDs table created</div>";
} else {
    echo "<div class='error'>❌ Error creating ids table: " . $conn->error . "</div>";
}

// Create certificates table
$sql = "CREATE TABLE IF NOT EXISTS certificates (
    certificate_id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    type_id INT,
    issue_date DATE NOT NULL,
    issued_by INT,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (issued_by) REFERENCES users(user_id)
)";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Certificates table created</div>";
} else {
    echo "<div class='error'>❌ Error creating certificates table: " . $conn->error . "</div>";
}

// Create certificate_types table
$sql = "CREATE TABLE IF NOT EXISTS certificate_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL
)";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Certificate types table created</div>";
    
    // Insert certificate types
    $conn->query("INSERT IGNORE INTO certificate_types (type_name) VALUES 
                  ('Birth Certificate'),
                  ('Death Certificate'),
                  ('Marriage Certificate'),
                  ('Divorce Certificate')");
    echo "<div class='success'>✅ Certificate types inserted</div>";
    
} else {
    echo "<div class='error'>❌ Error creating certificate types table: " . $conn->error . "</div>";
}

// Create requests table
$sql = "CREATE TABLE IF NOT EXISTS request_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL,
    type_category VARCHAR(50) NOT NULL
)";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Request types table created</div>";
    
    // Insert request types
    $conn->query("INSERT IGNORE INTO request_types (type_name, type_category) VALUES
                  ('New ID Card', 'id'),
                  ('ID Card Renewal', 'id'),
                  ('Birth Certificate', 'certificate'),
                  ('Death Certificate', 'certificate'),
                  ('Marriage Certificate', 'certificate'),
                  ('Divorce Certificate', 'certificate'),
                  ('Resident Registration', 'registration'),
                  ('Clearance Letter', 'clearance'),
                  ('Court Case Filing', 'court')");
    echo "<div class='success'>✅ Request types inserted</div>";
    
} else {
    echo "<div class='error'>❌ Error creating request types table: " . $conn->error . "</div>";
}

// Create requests table
$sql = "CREATE TABLE IF NOT EXISTS requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    request_number VARCHAR(50) UNIQUE NOT NULL,
    resident_name VARCHAR(100) NOT NULL,
    resident_phone VARCHAR(20) NOT NULL,
    resident_email VARCHAR(100),
    request_type_id INT NOT NULL,
    request_details TEXT,
    status ENUM('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    assigned_to INT,
    requested_by INT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_date DATETIME,
    notes TEXT,
    FOREIGN KEY (request_type_id) REFERENCES request_types(type_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id),
    FOREIGN KEY (requested_by) REFERENCES users(user_id)
)";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Requests table created</div>";
} else {
    echo "<div class='error'>❌ Error creating requests table: " . $conn->error . "</div>";
}

// Create notifications table
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Notifications table created</div>";
} else {
    echo "<div class='error'>❌ Error creating notifications table: " . $conn->error . "</div>";
}

echo "<h2>Creating Test Users</h2>";

// Create admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
              VALUES ('admin', '$admin_password', 'System Administrator', 'admin@ginjo.gov.et', '0912345678', 'Male', 1, 1)
              ON DUPLICATE KEY UPDATE password = '$admin_password', is_active = 1";

if ($conn->query($admin_sql)) {
    echo "<div class='success'>✅ Admin user created (admin/admin123)</div>";
} else {
    echo "<div class='error'>❌ Error creating admin: " . $conn->error . "</div>";
}

// Create manager user
$manager_password = password_hash('manager123', PASSWORD_DEFAULT);
$manager_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                VALUES ('manager', '$manager_password', 'Kebele Manager', 'manager@ginjo.gov.et', '0912345679', 'Male', 2, 1)
                ON DUPLICATE KEY UPDATE password = '$manager_password', is_active = 1";

if ($conn->query($manager_sql)) {
    echo "<div class='success'>✅ Manager user created (manager/manager123)</div>";
} else {
    echo "<div class='error'>❌ Error creating manager: " . $conn->error . "</div>";
}

// Create officer user
$officer_password = password_hash('officer123', PASSWORD_DEFAULT);
$officer_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                VALUES ('officer', '$officer_password', 'Record Officer', 'officer@ginjo.gov.et', '0912345680', 'Female', 3, 1)
                ON DUPLICATE KEY UPDATE password = '$officer_password', is_active = 1";

if ($conn->query($officer_sql)) {
    echo "<div class='success'>✅ Officer user created (officer/officer123)</div>";
} else {
    echo "<div class='error'>❌ Error creating officer: " . $conn->error . "</div>";
}

// Show all users
$result = $conn->query("SELECT u.user_id, u.username, u.full_name, u.is_active, r.role_name 
                        FROM users u 
                        JOIN roles r ON u.role_id = r.role_id");

echo "<h2>Current Users:</h2>";
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

echo "<div class='info' style='margin-top:20px;'>";
echo "<h3>✅ Setup Complete!</h3>";
echo "<p><strong>Login Credentials:</strong></p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin / admin123</li>";
echo "<li><strong>Manager:</strong> manager / manager123</li>";
echo "<li><strong>Officer:</strong> officer / officer123</li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

echo "</div></body></html>";

$conn->close();
?>