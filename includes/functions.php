<?php
/**
 * CENTRAL FUNCTIONS FILE
 * All shared functions are defined here to avoid redeclaration errors
 */

// Check if session exists
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * @return bool True if user is logged in (including community)
 */
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) || (isset($_SESSION['is_community']) && $_SESSION['is_community'] === true);
    }
}

/**
 * Check if user has a specific role
 * @param string $role Role to check
 * @return bool True if user has the role
 */
if (!function_exists('hasRole')) {
    function hasRole($role) {
        if (isset($_SESSION['is_community']) && $_SESSION['is_community'] === true) {
            return false;
        }
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
    }
}

/**
 * Require user to be logged in
 * Redirects to login if not logged in
 */
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            header("Location: /ginjo-kebele-ddms/pages/login.php");
            exit();
        }
    }
}

/**
 * Require user to have a specific role
 * @param string $role Required role
 */
if (!function_exists('requireRole')) {
    function requireRole($role) {
        requireLogin();
        if (!hasRole($role)) {
            header("Location: /ginjo-kebele-ddms/pages/dashboard.php?error=unauthorized");
            exit();
        }
    }
}

/**
 * Check if current user is community user
 * @return bool True if community user
 */
if (!function_exists('isCommunity')) {
    function isCommunity() {
        return isset($_SESSION['is_community']) && $_SESSION['is_community'] === true;
    }
}

/**
 * Get current user's display name
 * @return string User's display name
 */
if (!function_exists('getUserName')) {
    function getUserName() {
        return $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'User');
    }
}

/**
 * Get current user's role display name
 * @return string User's role for display
 */
if (!function_exists('getUserRole')) {
    function getUserRole() {
        if (isset($_SESSION['is_community']) && $_SESSION['is_community'] === true) {
            return 'Community User';
        }
        return $_SESSION['user_role'] ?? 'Guest';
    }
}

/**
 * Get user role badge class
 * @return string CSS class for role badge
 */
if (!function_exists('getRoleBadgeClass')) {
    function getRoleBadgeClass() {
        if (isCommunity()) {
            return 'badge bg-warning text-dark';
        }
        
        $role = $_SESSION['user_role'] ?? '';
        if (strpos($role, 'Administrator') !== false) {
            return 'badge bg-danger';
        } elseif (strpos($role, 'Manager') !== false) {
            return 'badge bg-primary';
        } elseif (strpos($role, 'Officer') !== false) {
            return 'badge bg-success';
        }
        return 'badge bg-secondary';
    }
}

/**
 * Get user role display text
 * @return string Short role name for display
 */
if (!function_exists('getRoleDisplay')) {
    function getRoleDisplay() {
        if (isCommunity()) {
            return 'Community';
        }
        
        $role = $_SESSION['user_role'] ?? '';
        if (strpos($role, 'Administrator') !== false) {
            return 'Admin';
        } elseif (strpos($role, 'Manager') !== false) {
            return 'Manager';
        } elseif (strpos($role, 'Officer') !== false) {
            return 'Officer';
        }
        return 'Staff';
    }
}
?>