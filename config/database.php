<?php
/**
 * DATABASE CONFIGURATION
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ginjo_kebele_ddms');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        error_log("Connection failed: " . $conn->connect_error);
        return false;
    }
    
    $conn->set_charset("utf8");
    return $conn;
}

// Start session if needed
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>