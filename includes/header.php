<?php
/**
 * HEADER TEMPLATE
 * Includes navigation bar, sidebar, and common CSS/JS
 */

// Check if session is already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include central functions file
require_once dirname(__DIR__) . '/includes/functions.php';

// Include database config
require_once dirname(__DIR__) . '/config/database.php';

// Check if this is a protected page (not login or index)
$current_page = basename($_SERVER['PHP_SELF']);
$is_protected_page = !in_array($current_page, ['login.php', 'index.php']);

// For protected pages, check login
if ($is_protected_page) {
    if (!isLoggedIn()) {
        header("Location: " . ($current_page == 'index.php' ? 'pages/login.php' : 'login.php'));
        exit();
    }
}

// Get user information
$display_name = getUserName();
$is_community = isCommunity();
$user_role_display = getRoleDisplay();
$role_badge_class = getRoleBadgeClass();

// Get notification count for logged-in users
$notification_count = 0;
if (isLoggedIn() && isset($conn) && $conn) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $notif_result = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $user_id AND is_read = 0");
    if ($notif_result) {
        $notification_count = $notif_result->fetch_assoc()['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ginjo Guduru Kebele - Digital Documentation Management System</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/ginjo-kebele-ddms/assets/css/style.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        /* Header and Navigation Styles */
        .navbar {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 12px 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand i {
            margin-right: 10px;
            color: #f1c40f;
            font-size: 1.8rem;
        }
        
        .navbar-toggler {
            border: none;
            padding: 8px 12px;
        }
        
        .navbar-toggler:focus {
            box-shadow: none;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 8px 15px !important;
            margin: 0 3px;
            border-radius: 50px;
            transition: all 0.3s;
            position: relative;
        }
        
        .nav-link:hover {
            background: rgba(255,255,255,0.2);
            color: white !important;
            transform: translateY(-2px);
        }
        
        .notification-bell {
            position: relative;
        }
        
        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 18px;
            text-align: center;
            transform: translate(25%, -25%);
        }
        
        .user-dropdown .dropdown-toggle {
            background: rgba(255,255,255,0.15);
            color: white !important;
            border-radius: 50px;
            padding: 8px 20px !important;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .user-dropdown .dropdown-toggle:hover {
            background: rgba(255,255,255,0.25);
        }
        
        .user-dropdown .dropdown-toggle::after {
            margin-left: 8px;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 25px rgba(0,0,0,0.15);
            border-radius: 12px;
            padding: 8px;
            min-width: 220px;
        }
        
        .dropdown-item {
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dropdown-item i {
            width: 20px;
            color: #667eea;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }
        
        .dropdown-item:hover i {
            color: white;
        }
        
        .dropdown-divider {
            margin: 8px 0;
        }
        
        /* Role Badge */
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .badge.bg-danger { background: linear-gradient(135deg, #e74c3c, #c0392b) !important; }
        .badge.bg-primary { background: linear-gradient(135deg, #3498db, #2980b9) !important; }
        .badge.bg-success { background: linear-gradient(135deg, #2ecc71, #27ae60) !important; }
        .badge.bg-warning { background: linear-gradient(135deg, #f39c12, #e67e22) !important; color: #2c3e50 !important; }
        
        /* Breadcrumb */
        .breadcrumb-area {
            background: white;
            padding: 15px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .breadcrumb {
            margin-bottom: 0;
            padding: 0;
            background: transparent;
        }
        
        .breadcrumb-item a {
            color: #3498db;
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb-item.active {
            color: #2c3e50;
            font-weight: 500;
        }
        
        /* Main Content Area */
        .main-content {
            min-height: calc(100vh - 140px);
            padding: 20px 25px;
            background: #f8f9fa;
        }
        
        /* Loading Spinner */
        .spinner-wrapper {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .spinner-wrapper.show {
            display: flex;
        }
        
        /* Alert Animations */
        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            border: none;
            animation: slideDown 0.5s ease;
            margin-bottom: 20px;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar-brand i {
                font-size: 1.4rem;
            }
            
            .user-dropdown .dropdown-toggle span {
                display: none;
            }
            
            .main-content {
                padding: 15px;
            }
        }
        
        /* Print Styles */
        @media print {
            .navbar, .sidebar, .breadcrumb-area, .no-print {
                display: none !important;
            }
            
            .main-content {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
    </style>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <!-- Navigation Bar for Logged-in Users -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/ginjo-kebele-ddms/pages/dashboard.php">
                <i class="fas fa-building"></i> Ginjo Guduru Kebele
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- Notifications Bell -->
                    <li class="nav-item me-2">
                        <a class="nav-link notification-bell" href="/ginjo-kebele-ddms/pages/notifications.php">
                            <i class="fas fa-bell"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-badge"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Requests Link for Staff -->
                    <?php if (!$is_community): ?>
                    <li class="nav-item me-2">
                        <a class="nav-link" href="/ginjo-kebele-ddms/pages/requests.php">
                            <i class="fas fa-tasks"></i> Requests
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown user-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle fa-lg"></i>
                            <span><?php echo htmlspecialchars($display_name); ?></span>
                            <span class="<?php echo $role_badge_class; ?> ms-2">
                                <?php echo $user_role_display; ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="/ginjo-kebele-ddms/pages/dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/ginjo-kebele-ddms/pages/profile.php">
                                    <i class="fas fa-user"></i> My Profile
                                </a>
                            </li>
                            <?php if (!$is_community): ?>
                            <li>
                                <a class="dropdown-item" href="/ginjo-kebele-ddms/pages/change-password.php">
                                    <i class="fas fa-key"></i> Change Password
                                </a>
                            </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item" href="/ginjo-kebele-ddms/index.php">
                                    <i class="fas fa-home"></i> Home
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="/ginjo-kebele-ddms/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Container with Sidebar -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <?php include 'sidebar.php'; ?>
            </div>
            
            <!-- Main Content Area -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Breadcrumb Navigation -->
                <div class="breadcrumb-area">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="/ginjo-kebele-ddms/pages/dashboard.php">
                                    <i class="fas fa-home"></i> Dashboard
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php 
                                $page_name = str_replace('.php', '', basename($_SERVER['PHP_SELF']));
                                echo ucwords(str_replace('-', ' ', $page_name));
                                ?>
                            </li>
                        </ol>
                    </nav>
                </div>
                
                <!-- Dynamic Content Area -->
                <div class="content-area">
    <?php else: ?>
    <!-- Simple Header for Non-logged-in Pages -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/ginjo-kebele-ddms/index.php">
                <i class="fas fa-building"></i> Ginjo Guduru Kebele
            </a>
        </div>
    </nav>
    <div class="container-fluid p-0">
    <?php endif; ?>

    <!-- Notification Update Script -->
    <script>
    function updateNotificationCount() {
        $.ajax({
            url: '/ginjo-kebele-ddms/pages/get-notifications.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                if (data.count > 0) {
                    $('.notification-bell .notification-badge').text(data.count).show();
                } else {
                    $('.notification-bell .notification-badge').hide();
                }
            }
        });
    }

    // Update notifications every 30 seconds
    $(document).ready(function() {
        updateNotificationCount();
        setInterval(updateNotificationCount, 30000);
    });
    </script>