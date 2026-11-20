<?php
/**
 * COMPLETE DATABASE SETUP - FINAL WORKING VERSION
 * NO DROP DATABASE COMMAND - Works on Windows
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Complete Database Setup</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔧 Complete Database Setup - Final Version</h1>";

// Step 1: Connect to MySQL server
echo "<h2>Step 1: Connecting to MySQL</h2>";
$conn = new mysqli('localhost', 'root', '');

if ($conn->connect_error) {
    echo "<div class='error'>❌ Cannot connect to MySQL: " . $conn->connect_error . "</div>";
    echo "<div class='info'>";
    echo "<p>Please check:</p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL is running (green light)</li>";
    echo "<li>MySQL port is 3306 (default)</li>";
    echo "</ul>";
    echo "</div>";
    exit();
}

echo "<div class='success'>✅ Connected to MySQL server</div>";

// Step 2: Create database if it doesn't exist - NO DROP
echo "<h2>Step 2: Setting up Database</h2>";
$dbname = 'ginjo_kebele_ddms';

// Create database if not exists (don't try to drop)
if ($conn->query("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8 COLLATE utf8_general_ci")) {
    echo "<div class='success'>✅ Database '$dbname' is ready</div>";
} else {
    echo "<div class='error'>❌ Error creating database: " . $conn->error . "</div>";
    exit();
}

// Select the database
$conn->select_db($dbname);

// Step 3: Disable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Step 4: Drop all existing tables in correct order
echo "<h2>Step 3: Cleaning up old tables</h2>";

// Get all tables
$tables_result = $conn->query("SHOW TABLES");
$tables = [];
if ($tables_result) {
    while ($row = $tables_result->fetch_array()) {
        $tables[] = $row[0];
    }
}

// Drop tables in reverse order of dependencies
$drop_order = [
    'notifications',
    'requests',
    'certificates',
    'ids',
    'customers',
    'houses',
    'request_types',
    'certificate_types',
    'kebeles',
    'jobs',
    'users',
    'roles'
];

$dropped_count = 0;
foreach ($drop_order as $table) {
    if (in_array($table, $tables)) {
        if ($conn->query("DROP TABLE IF EXISTS $table")) {
            echo "<div class='info'>✅ Dropped table: $table</div>";
            $dropped_count++;
        }
    }
}

echo "<div class='success'>✅ Cleaned up $dropped_count tables</div>";

// Step 5: Create tables
echo "<h2>Step 4: Creating Tables</h2>";

$success = true;

// Create roles table
echo "<div class='info'>Creating roles table...</div>";
$sql = "CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Roles table created</div>";
    
    // Insert roles
    $conn->query("INSERT IGNORE INTO roles (role_name) VALUES 
                  ('System_Administrator'),
                  ('Kebele_Manager'),
                  ('Record_Officer')");
    echo "<div class='success'>✅ Default roles inserted</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    $success = false;
}

// Create users table
echo "<div class='info'>Creating users table...</div>";
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Users table created</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
    $success = false;
}

// Create jobs table
echo "<div class='info'>Creating jobs table...</div>";
$sql = "CREATE TABLE IF NOT EXISTS jobs (
    job_id INT PRIMARY KEY AUTO_INCREMENT,
    job_title VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Jobs table created</div>";
    
    // Insert sample jobs
    $conn->query("INSERT IGNORE INTO jobs (job_title) VALUES 
                  ('Farmer'), ('Teacher'), ('Merchant'), ('Student'), 
                  ('Government Employee'), ('Private Employee'), ('Other')");
    echo "<div class='success'>✅ Sample jobs inserted</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Create kebeles table
echo "<div class='info'>Creating kebeles table...</div>";
$sql = "CREATE TABLE IF NOT EXISTS kebeles (
    kebele_id INT PRIMARY KEY AUTO_INCREMENT,
    kebele_name VARCHAR(100) NOT NULL,
    zone VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Kebeles table created</div>";
    
    // Insert default kebele
    $conn->query("INSERT IGNORE INTO kebeles (kebele_name, zone) VALUES ('Ginjo Guduru', 'Jimma')");
    echo "<div class='success'>✅ Default kebele inserted</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Create houses table
echo "<div class='info'>Creating houses table...</div>";
$sql = "CREATE TABLE IF NOT EXISTS houses (
    house_id INT PRIMARY KEY AUTO_INCREMENT,
    house_number VARCHAR(50) NOT NULL,
    kebele_id INT,
    zone VARCHAR(100),
    FOREIGN KEY (kebele_id) REFERENCES kebeles(kebele_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Houses table created</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Create customers table
echo "<div class='info'>Creating customers table...</div>";
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
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (house_id) REFERENCES houses(house_id),
    FOREIGN KEY (job_id) REFERENCES jobs(job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Customers table created</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Create ids table
echo "<div class='info'>Creating ids table...</div>";
$sql = "CREATE TABLE IF NOT EXISTS ids (
    id_number INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    issued_date DATE NOT NULL,
    expiry_date DATE,
    issued_by INT,
    is_renewed BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (issued_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ IDs table created</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Create certificate_types table
echo "<div class='info'>Creating certificate types table...</div>";
$sql = "CREATE TABLE IF NOT EXISTS certificate_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

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
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Create certificates table
echo "<div class='info'>Creating certificates table...</div>";
$sql = "CREATE TABLE IF NOT EXISTS certificates (
    certificate_id INT PRIMARY KEY AUTO_INCREMENT,
    certificate_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    type_id INT,
    issue_date DATE NOT NULL,
    issued_by INT,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (type_id) REFERENCES certificate_types(type_id),
    FOREIGN KEY (issued_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Certificates table created</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Create request_types table
echo "<div class='info'>Creating request types table...</div>";
$sql = "CREATE TABLE IF NOT EXISTS request_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL,
    type_category VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

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
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Create requests table
echo "<div class='info'>Creating requests table...</div>";
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Requests table created</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Create notifications table
echo "<div class='info'>Creating notifications table...</div>";
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Notifications table created</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Step 6: Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Step 7: Create users
echo "<h2>Step 5: Creating User Accounts</h2>";

// Clear existing test users
$conn->query("DELETE FROM users WHERE username IN ('admin', 'manager', 'officer')");

// Admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
              VALUES ('admin', '$admin_password', 'System Administrator', 'admin@ginjo.gov.et', '0912345678', 'Male', 1, 1)";

if ($conn->query($admin_sql)) {
    echo "<div class='success'>✅ Admin user created (admin/admin123)</div>";
} else {
    echo "<div class='error'>❌ Error creating admin: " . $conn->error . "</div>";
}

// Manager user
$manager_password = password_hash('manager123', PASSWORD_DEFAULT);
$manager_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                VALUES ('manager', '$manager_password', 'Kebele Manager', 'manager@ginjo.gov.et', '0912345679', 'Male', 2, 1)";

if ($conn->query($manager_sql)) {
    echo "<div class='success'>✅ Manager user created (manager/manager123)</div>";
} else {
    echo "<div class='error'>❌ Error creating manager: " . $conn->error . "</div>";
}

// Officer user
$officer_password = password_hash('officer123', PASSWORD_DEFAULT);
$officer_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                VALUES ('officer', '$officer_password', 'Record Officer', 'officer@ginjo.gov.et', '0912345680', 'Female', 3, 1)";

if ($conn->query($officer_sql)) {
    echo "<div class='success'>✅ Officer user created (officer/officer123)</div>";
} else {
    echo "<div class='error'>❌ Error creating officer: " . $conn->error . "</div>";
}

// Step 8: Verify tables
echo "<h2>Step 6: Verifying Tables</h2>";

$check_tables = ['roles', 'users', 'customers', 'ids', 'certificates', 'requests', 'notifications'];
$all_good = true;

foreach ($check_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "<div class='success'>✅ Table '$table' exists</div>";
    } else {
        echo "<div class='error'>❌ Table '$table' does not exist!</div>";
        $all_good = false;
    }
}

// Step 9: Show users
$result = $conn->query("SELECT u.user_id, u.username, u.full_name, u.is_active, r.role_name 
                        FROM users u 
                        JOIN roles r ON u.role_id = r.role_id");

if ($result && $result->num_rows > 0) {
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
}

if ($all_good) {
    echo "<div class='success' style='margin-top:20px;'>";
    echo "<h3>✅ SETUP COMPLETE - EVERYTHING IS WORKING!</h3>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin / admin123</li>";
    echo "<li><strong>Manager:</strong> manager / manager123</li>";
    echo "<li><strong>Officer:</strong> officer / officer123</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='error' style='margin-top:20px;'>";
    echo "<h3>❌ Some tables are missing. Please check the errors above.</h3>";
    echo "</div>";
}

echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

echo "</div></body></html>";

$conn->close();
?>