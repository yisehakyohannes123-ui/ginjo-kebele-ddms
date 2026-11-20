<?php
/**
 * SIDEBAR NAVIGATION
 * Dynamic sidebar based on user role (Admin, Manager, Officer, Community)
 */

// Check if session exists
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_community = isset($_SESSION['is_community']) && $_SESSION['is_community'] === true;
$is_logged_in = isset($_SESSION['user_id']) || $is_community;

// DO NOT redeclare hasRole() here - it's already declared elsewhere
// Just use it if it exists, otherwise define a local version only if needed
if (!function_exists('hasRole')) {
    function hasRole($role) {
        if (isset($_SESSION['is_community']) && $_SESSION['is_community'] === true) {
            return false;
        }
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
    }
}
?>
<div class="sidebar">
    <ul class="nav flex-column">
        <?php if ($is_logged_in): ?>
            <!-- Dashboard - visible to all logged-in users -->
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="/ginjo-kebele-ddms/pages/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            
            <?php if ($is_community): ?>
                <!-- ===== COMMUNITY USER MENU ===== -->
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['residents.php', 'view-resident.php']) ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/residents.php">
                        <i class="fas fa-users"></i> View Residents
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['id-management.php', 'view-id.php']) ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/id-management.php">
                        <i class="fas fa-id-card"></i> ID Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['certificates.php', 'view-certificate.php']) ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/certificates.php">
                        <i class="fas fa-certificate"></i> Certificate Services
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/reports.php">
                        <i class="fas fa-chart-bar"></i> View Reports
                    </a>
                </li>
                
            <?php else: ?>
                <!-- ===== STAFF USER MENU ===== -->
                <!-- Residents Management - All staff -->
                <?php if (function_exists('hasRole') && (hasRole('System_Administrator') || hasRole('Kebele_Manager') || hasRole('Record_Officer'))): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['residents.php', 'add-resident.php', 'view-resident.php', 'edit-resident.php']) ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/residents.php">
                        <i class="fas fa-users"></i> Residents
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- ID Management - Admin and Manager only -->
                <?php if (function_exists('hasRole') && (hasRole('System_Administrator') || hasRole('Kebele_Manager'))): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['id-management.php', 'issue-id.php', 'renew-id.php', 'view-id.php']) ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/id-management.php">
                        <i class="fas fa-id-card"></i> ID Management
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Certificates - Admin and Officer only -->
                <?php if (function_exists('hasRole') && (hasRole('System_Administrator') || hasRole('Record_Officer'))): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['certificates.php', 'issue-certificate.php', 'view-certificate.php']) ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/certificates.php">
                        <i class="fas fa-certificate"></i> Certificates
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Social Court - Admin and Manager only -->
                <?php if (function_exists('hasRole') && (hasRole('System_Administrator') || hasRole('Kebele_Manager'))): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['social-court.php', 'view-case.php', 'add-case.php']) ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/social-court.php">
                        <i class="fas fa-gavel"></i> Social Court
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Clearance - Admin and Manager only -->
                <?php if (function_exists('hasRole') && (hasRole('System_Administrator') || hasRole('Kebele_Manager'))): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['clearance.php', 'issue-clearance.php']) ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/clearance.php">
                        <i class="fas fa-exchange-alt"></i> Clearance
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Messages - Admin and Manager only -->
                <?php if (function_exists('hasRole') && (hasRole('System_Administrator') || hasRole('Kebele_Manager'))): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['messages.php', 'compose-message.php']) ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/messages.php">
                        <i class="fas fa-envelope"></i> Messages
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Reports - All staff -->
                <?php if (function_exists('hasRole') && (hasRole('System_Administrator') || hasRole('Kebele_Manager') || hasRole('Record_Officer'))): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/reports.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Account Management - Admin only -->
                <?php if (function_exists('hasRole') && hasRole('System_Administrator')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($current_page, ['account-management.php', 'create-user.php', 'edit-user.php']) ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/account-management.php">
                        <i class="fas fa-user-cog"></i> Account Management
                    </a>
                </li>
                <?php endif; ?>
                
                <!-- Change Password - All staff -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'change-password.php' ? 'active' : ''; ?>" 
                       href="/ginjo-kebele-ddms/pages/change-password.php">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                </li>
            <?php endif; ?>
            
            <!-- Divider for all logged-in users -->
            <li class="nav-item">
                <hr class="sidebar-divider">
            </li>
            
            <!-- Logout for all users -->
            <li class="nav-item">
                <a class="nav-link text-danger" href="/ginjo-kebele-ddms/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
            
        <?php else: ?>
            <!-- Menu for Non-logged-in Users -->
            <li class="nav-item">
                <a class="nav-link" href="/ginjo-kebele-ddms/index.php">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/ginjo-kebele-ddms/pages/login.php">
                    <i class="fas fa-sign-in-alt"></i> Staff Login
                </a>
            </li>
            <li class="nav-item mt-4">
                <div class="text-white text-center small">
                    <p>Ginjo Guduru Kebele<br>Digital Documentation System</p>
                </div>
            </li>
        <?php endif; ?>
    </ul>
</div>

<style>
/* Sidebar Styles */
.sidebar {
    min-height: calc(100vh - 56px);
    background: linear-gradient(135deg, #2c3e50, #34495e);
    color: white;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
}

.sidebar .nav-link {
    color: rgba(255,255,255,0.8);
    padding: 12px 20px;
    margin: 2px 0;
    border-radius: 5px;
    transition: all 0.3s;
}

.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    transform: translateX(5px);
}

.sidebar .nav-link.active {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
}

.sidebar .nav-link i {
    margin-right: 10px;
    width: 20px;
}

.sidebar .nav-link.text-danger {
    color: #ff6b6b !important;
}

.sidebar .nav-link.text-danger:hover {
    background: rgba(255,107,107,0.2) !important;
}

.sidebar-divider {
    border-top: 1px solid rgba(255,255,255,0.2);
    margin: 15px 0;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        min-height: auto;
    }
}
</style>