<?php
/**
 * COMPLETE DATABASE RESET - ULTIMATE FIX
 * This will completely remove and recreate the database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Complete Database Reset</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f0f2f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .info { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .warning { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .step { background: #e2e3e5; padding: 10px; margin: 10px 0; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>";

echo "<h1>🔧 Complete Database Reset</h1>";
echo "<div class='warning'>⚠️ This will DELETE all data and recreate the database!</div>";

// Step 1: Connect to MySQL server
echo "<div class='step'>Step 1: Connecting to MySQL</div>";
$conn = new mysqli('localhost', 'root', '');

if ($conn->connect_error) {
    echo "<div class='error'>❌ Cannot connect to MySQL: " . $conn->connect_error . "</div>";
    echo "<div class='info'>Please make sure XAMPP MySQL is running (green light)</div>";
    exit();
}

echo "<div class='success'>✅ Connected to MySQL server</div>";

// Step 2: Kill all connections to the database
echo "<div class='step'>Step 2: Killing database connections</div>";
$dbname = 'ginjo_kebele_ddms';

// Force close all connections to the database
$conn->query("KILL (SELECT CONNECTION_ID())");
$conn->query("FLUSH TABLES");

// Try to force drop database with multiple attempts
echo "<div class='info'>Attempting to drop database...</div>";

// Method 1: Try with IF EXISTS
$conn->query("DROP DATABASE IF EXISTS $dbname");

// Method 2: Try without IF EXISTS (might work better)
@$conn->query("DROP DATABASE $dbname");

// Method 3: Try with mysqladmin style
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("DROP DATABASE IF EXISTS $dbname");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

// Check if database still exists
$check = $conn->query("SHOW DATABASES LIKE '$dbname'");
if ($check->num_rows == 0) {
    echo "<div class='success'>✅ Database dropped successfully</div>";
} else {
    echo "<div class='warning'>⚠️ Database still exists. Trying alternative method...</div>";
    
    // Alternative: Drop all tables first
    $conn->select_db($dbname);
    $tables = $conn->query("SHOW TABLES");
    if ($tables) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        while ($row = $tables->fetch_array()) {
            $table = $row[0];
            $conn->query("DROP TABLE IF EXISTS $table");
            echo "<div class='info'>✅ Dropped table: $table</div>";
        }
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    // Now try to drop database again
    $conn->query("DROP DATABASE IF EXISTS $dbname");
}

// Step 3: Create fresh database
echo "<div class='step'>Step 3: Creating fresh database</div>";
if ($conn->query("CREATE DATABASE $dbname CHARACTER SET utf8 COLLATE utf8_general_ci")) {
    echo "<div class='success'>✅ Fresh database created</div>";
} else {
    echo "<div class='error'>❌ Error creating database: " . $conn->error . "</div>";
    exit();
}

// Select the database
$conn->select_db($dbname);

// Step 4: Create tables
echo "<div class='step'>Step 4: Creating tables</div>";

// Create roles table
$sql = "CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

if ($conn->query($sql)) {
    echo "<div class='success'>✅ Roles table created</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Insert roles
$conn->query("INSERT INTO roles (role_name) VALUES 
              ('System_Administrator'),
              ('Kebele_Manager'),
              ('Record_Officer')");
echo "<div class='success'>✅ Default roles inserted</div>";

// Create users table
$sql = "CREATE TABLE users (
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
}

// Create jobs table
$sql = "CREATE TABLE jobs (
    job_id INT PRIMARY KEY AUTO_INCREMENT,
    job_title VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$conn->query($sql);
echo "<div class='success'>✅ Jobs table created</div>";

// Insert sample jobs
$conn->query("INSERT INTO jobs (job_title) VALUES 
              ('Farmer'), ('Teacher'), ('Merchant'), ('Student'), 
              ('Government Employee'), ('Private Employee'), ('Other')");
echo "<div class='success'>✅ Sample jobs inserted</div>";

// Create kebeles table
$sql = "CREATE TABLE kebeles (
    kebele_id INT PRIMARY KEY AUTO_INCREMENT,
    kebele_name VARCHAR(100) NOT NULL,
    zone VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$conn->query($sql);
echo "<div class='success'>✅ Kebeles table created</div>";

// Insert default kebele
$conn->query("INSERT INTO kebeles (kebele_name, zone) VALUES ('Ginjo Guduru', 'Jimma')");
echo "<div class='success'>✅ Default kebele inserted</div>";

// Create houses table
$sql = "CREATE TABLE houses (
    house_id INT PRIMARY KEY AUTO_INCREMENT,
    house_number VARCHAR(50) NOT NULL,
    kebele_id INT,
    zone VARCHAR(100),
    FOREIGN KEY (kebele_id) REFERENCES kebeles(kebele_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$conn->query($sql);
echo "<div class='success'>✅ Houses table created</div>";

// Create customers table
$sql = "CREATE TABLE customers (
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

// Create certificate_types table
$sql = "CREATE TABLE certificate_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$conn->query($sql);
echo "<div class='success'>✅ Certificate types table created</div>";

// Insert certificate types
$conn->query("INSERT INTO certificate_types (type_name) VALUES 
              ('Birth Certificate'),
              ('Death Certificate'),
              ('Marriage Certificate'),
              ('Divorce Certificate')");
echo "<div class='success'>✅ Certificate types inserted</div>";

// Create certificates table
$sql = "CREATE TABLE certificates (
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

$conn->query($sql);
echo "<div class='success'>✅ Certificates table created</div>";

// Create request_types table
$sql = "CREATE TABLE request_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL,
    type_category VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$conn->query($sql);
echo "<div class='success'>✅ Request types table created</div>";

// Insert request types
$conn->query("INSERT INTO request_types (type_name, type_category) VALUES
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

// Create requests table
$sql = "CREATE TABLE requests (
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

$conn->query($sql);
echo "<div class='success'>✅ Requests table created</div>";

// Create notifications table
$sql = "CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$conn->query($sql);
echo "<div class='success'>✅ Notifications table created</div>";

// Step 5: Create users
echo "<div class='step'>Step 5: Creating user accounts</div>";

// Admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
              VALUES ('admin', '$admin_password', 'System Administrator', 'admin@ginjo.gov.et', '0912345678', 'Male', 1, 1)";

if ($conn->query($admin_sql)) {
    echo "<div class='success'>✅ Admin user created (admin/admin123)</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Manager user
$manager_password = password_hash('manager123', PASSWORD_DEFAULT);
$manager_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                VALUES ('manager', '$manager_password', 'Kebele Manager', 'manager@ginjo.gov.et', '0912345679', 'Male', 2, 1)";

if ($conn->query($manager_sql)) {
    echo "<div class='success'>✅ Manager user created (manager/manager123)</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Officer user
$officer_password = password_hash('officer123', PASSWORD_DEFAULT);
$officer_sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                VALUES ('officer', '$officer_password', 'Record Officer', 'officer@ginjo.gov.et', '0912345680', 'Female', 3, 1)";

if ($conn->query($officer_sql)) {
    echo "<div class='success'>✅ Officer user created (officer/officer123)</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conn->error . "</div>";
}

// Step 6: Verify
echo "<div class='step'>Step 6: Verification</div>";

$tables = ['roles', 'users', 'customers', 'certificates', 'requests'];
$all_good = true;

foreach ($tables as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<div class='success'>✅ Table '$table' has $count records</div>";
    } else {
        echo "<div class='error'>❌ Error with table '$table': " . $conn->error . "</div>";
        $all_good = false;
    }
}

if ($all_good) {
    echo "<div class='success' style='margin-top:20px;'>";
    echo "<h3>✅ RESET COMPLETE - EVERYTHING IS WORKING!</h3>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> admin / admin123</li>";
    echo "<li><strong>Manager:</strong> manager / manager123</li>";
    echo "<li><strong>Officer:</strong> officer / officer123</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page</a></p>";

echo "</div></body></html>";

$conn->close();
?>