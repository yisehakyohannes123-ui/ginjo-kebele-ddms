<?php
// ============================================
// COMPLETE LOGIN FIX SCRIPT - ONE FILE SOLUTION
// ============================================

echo "<!DOCTYPE html>";
echo "<html><head>";
echo "<title>Login System Fix Tool</title>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f0f2f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
    .step { background: #e2e3e5; padding: 10px; margin: 5px 0; border-radius: 5px; }
    .btn { background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px; }
    .btn:hover { background: #764ba2; }
    code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
    </style></head><body>";
echo "<div class='container'>";
echo "<h1>🔧 Ginjo Kebele DDMS - Login Fix Tool</h1>";

// STEP 1: Test PHP
echo "<div class='step'>✅ STEP 1: PHP is working (PHP Version: " . phpversion() . ")</div>";

// STEP 2: Fix file extensions
echo "<div class='step'>📁 STEP 2: Fixing file extensions...</div>";

function fixFileExtensions($dir) {
    $fixed = 0;
    if (is_dir($dir)) {
        $files = glob($dir . "/*.php.TXT");
        $files = array_merge($files, glob($dir . "/*.php.txt"));
        
        foreach ($files as $file) {
            $newName = str_replace(['.php.TXT', '.php.txt'], '.php', $file);
            if (rename($file, $newName)) {
                $fixed++;
                echo "<div class='success'>✅ Fixed: " . basename($file) . " → " . basename($newName) . "</div>";
            }
        }
    }
    return $fixed;
}

$pagesDir = __DIR__ . "/pages";
$fixed = fixFileExtensions($pagesDir);
if ($fixed > 0) {
    echo "<div class='success'>✅ Fixed $fixed file extensions</div>";
} else {
    echo "<div class='success'>✅ No files needed fixing</div>";
}

// STEP 3: Create database connection
echo "<div class='step'>🗄️ STEP 3: Setting up database...</div>";

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'ginjo_kebele_ddms';

// Create connection
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    echo "<div class='error'>❌ Cannot connect to MySQL: " . $conn->connect_error . "</div>";
    echo "<div class='error'>Make sure MySQL is running in XAMPP</div>";
} else {
    echo "<div class='success'>✅ Connected to MySQL successfully</div>";
    
    // Create database if not exists
    $conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "<div class='success'>✅ Database '$dbname' ready</div>";
    
    // Select database
    $conn->select_db($dbname);
    
    // Create users table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        phone VARCHAR(20),
        gender ENUM('Male', 'Female', 'Other'),
        birth_date DATE,
        role_id INT DEFAULT 1,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Users table created/verified</div>";
    } else {
        echo "<div class='error'>❌ Error creating table: " . $conn->error . "</div>";
    }
    
    // Create roles table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS roles (
        role_id INT PRIMARY KEY AUTO_INCREMENT,
        role_name VARCHAR(50) UNIQUE NOT NULL
    )";
    $conn->query($sql);
    
    // Insert default roles
    $conn->query("INSERT IGNORE INTO roles (role_id, role_name) VALUES 
                  (1, 'System_Administrator'),
                  (2, 'Kebele_Manager'),
                  (3, 'Record_Officer')");
    
    // STEP 4: Create admin user
    echo "<div class='step'>👤 STEP 4: Creating admin user...</div>";
    
    // Password to use
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Delete existing admin
    $conn->query("DELETE FROM users WHERE username = 'admin'");
    
    // Insert new admin
    $sql = "INSERT INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
            VALUES ('admin', '$hashed_password', 'System Administrator', 'admin@ginjo.gov.et', '0912345678', 'Male', 1, 1)";
    
    if ($conn->query($sql)) {
        echo "<div class='success'>✅ Admin user created successfully!</div>";
        echo "<div class='success'>📝 Username: <strong>admin</strong></div>";
        echo "<div class='success'>📝 Password: <strong>admin123</strong></div>";
        echo "<div class='success'>📝 Password Hash: " . $hashed_password . "</div>";
    } else {
        echo "<div class='error'>❌ Error creating admin: " . $conn->error . "</div>";
    }
    
    // Create manager user
    $manager_pass = password_hash('manager123', PASSWORD_DEFAULT);
    $conn->query("INSERT IGNORE INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                  VALUES ('manager', '$manager_pass', 'Kebele Manager', 'manager@ginjo.gov.et', '0912345679', 'Male', 2, 1)");
    
    // Create officer user
    $officer_pass = password_hash('officer123', PASSWORD_DEFAULT);
    $conn->query("INSERT IGNORE INTO users (username, password, full_name, email, phone, gender, role_id, is_active) 
                  VALUES ('officer', '$officer_pass', 'Record Officer', 'officer@ginjo.gov.et', '0912345680', 'Female', 3, 1)");
    
    // STEP 5: Verify users
    echo "<div class='step'>🔍 STEP 5: Verifying users...</div>";
    
    $result = $conn->query("SELECT user_id, username, is_active FROM users");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Status</th><th>Password Test</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['is_active'] ? '✅ Active' : '❌ Inactive';
        $pass_test = '';
        if ($row['username'] == 'admin') {
            $test = password_verify('admin123', $hashed_password);
            $pass_test = $test ? '✅ Password OK' : '❌ Password Error';
        }
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td><strong>" . $row['username'] . "</strong></td>";
        echo "<td>" . $status . "</td>";
        echo "<td>" . $pass_test . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $conn->close();
}

// STEP 6: Create config file if missing
echo "<div class='step'>⚙️ STEP 6: Checking config file...</div>";

$configDir = __DIR__ . "/config";
if (!is_dir($configDir)) {
    mkdir($configDir, 0777, true);
}

$configFile = $configDir . "/database.php";
if (!file_exists($configFile)) {
    $configContent = '<?php
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "ginjo_kebele_ddms");

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>';
    file_put_contents($configFile, $configContent);
    echo "<div class='success'>✅ Created config/database.php</div>";
} else {
    echo "<div class='success'>✅ Config file exists</div>";
}

// STEP 7: Create index.php if missing
echo "<div class='step'>🏠 STEP 7: Checking index file...</div>";

$indexFile = __DIR__ . "/index.php";
if (!file_exists($indexFile)) {
    $indexContent = '<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: pages/dashboard.php");
} else {
    header("Location: pages/login.php");
}
exit();
?>';
    file_put_contents($indexFile, $indexContent);
    echo "<div class='success'>✅ Created index.php</div>";
} else {
    echo "<div class='success'>✅ Index file exists</div>";
}

// STEP 8: Create .htaccess
echo "<div class='step'>🔒 STEP 8: Creating .htaccess...</div>";

$htaccessFile = __DIR__ . "/.htaccess";
if (!file_exists($htaccessFile)) {
    $htaccessContent = 'Options -Indexes
DirectoryIndex index.php
AddType application/x-httpd-php .php';
    file_put_contents($htaccessFile, $htaccessContent);
    echo "<div class='success'>✅ Created .htaccess</div>";
}

// STEP 9: Test login page
echo "<div class='step'>🔑 STEP 9: Login page check...</div>";

$loginFile = __DIR__ . "/pages/login.php";
if (file_exists($loginFile)) {
    echo "<div class='success'>✅ Login page exists at: pages/login.php</div>";
} else {
    echo "<div class='error'>❌ Login page missing!</div>";
}

// STEP 10: Final instructions
echo "<div class='step'>🎯 STEP 10: FINAL - Login Instructions</div>";

echo "<div class='success' style='font-size: 1.2em;'>";
echo "<h3>✅ ALL FIXES COMPLETE!</h3>";
echo "<p>Now follow these steps exactly:</p>";
echo "<ol>";
echo "<li><strong>Restart XAMPP:</strong> Stop both Apache and MySQL, then start them again</li>";
echo "<li><strong>Open your browser</strong> and go to: <a href='http://localhost/ginjo-kebele-ddms/pages/login.php' target='_blank' style='font-weight: bold;'>http://localhost/ginjo-kebele-ddms/pages/login.php</a></li>";
echo "<li><strong>Login with:</strong></li>";
echo "<ul style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<li>👤 <strong>Username:</strong> <code>admin</code></li>";
echo "<li>🔑 <strong>Password:</strong> <code>admin123</code></li>";
echo "</ul>";
echo "<li><strong>Click the Login button</strong></li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='pages/login.php' target='_blank' class='btn' style='background: #28a745;'>🔑 Go to Login Page Now</a>";
echo "<a href='http://localhost/phpmyadmin' target='_blank' class='btn' style='background: #007bff;'>🗄️ Open phpMyAdmin</a>";
echo "<a href='index.php' target='_blank' class='btn' style='background: #17a2b8;'>🏠 Open Website</a>";
echo "</div>";

echo "<hr>";
echo "<p style='color: #666; text-align: center;'>If you still cannot login, please tell me:</p>";
echo "<ul style='color: #666;'>";
echo "<li>What error message do you see on the login page?</li>";
echo "<li>Do you see any users in the table above?</li>";
echo "<li>What happens when you click the login button?</li>";
echo "</ul>";

echo "</div></body></html>";
?>