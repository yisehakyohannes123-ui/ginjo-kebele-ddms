<?php
/**
 * COMMUNITY LOGIN HANDLER
 * This file handles community user login without password
 */

// Check if session is already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set session variables for community user
$_SESSION['user_id'] = 0;
$_SESSION['username'] = 'community';
$_SESSION['full_name'] = 'Community Resident';
$_SESSION['user_role'] = 'Community_User';
$_SESSION['is_community'] = true;
$_SESSION['last_activity'] = time();

// Redirect to dashboard
header("Location: dashboard.php");
exit();
?>