<?php
// Check if session is already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && !isset($_SESSION['is_community'])) {
    header("Location: pages/dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ginjo Guduru Kebele - Community Services Portal</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        /* Navbar */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #2c3e50 !important;
        }
        
        .navbar-brand i {
            color: #667eea;
            margin-right: 10px;
        }
        
        .nav-link {
            color: #2c3e50 !important;
            font-weight: 500;
            padding: 8px 20px !important;
            margin: 0 5px;
            border-radius: 50px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
        }
        
        .nav-link i {
            margin-right: 8px;
        }
        
        /* Hero Section */
        .hero-section {
            padding: 80px 0 40px;
            color: white;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            animation: fadeInUp 1s ease;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 700px;
            margin: 0 auto 30px;
            animation: fadeInUp 1s ease 0.2s both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Stats Section */
        .stats-section {
            padding: 40px 0;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            color: white;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            overflow: hidden;
            height: 100%;
        }
        
        .card:hover {
            transform: translateY(-10px);
        }
        
        .card-header {
            padding: 20px 25px;
            font-weight: 600;
            font-size: 1.3rem;
            border-bottom: none;
        }
        
        .card-header i {
            margin-right: 10px;
        }
        
        .card-body {
            padding: 30px;
        }
        
        /* Community Card */
        .community-card {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }
        
        .community-card .card-header {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        
        .service-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 20px 0;
        }
        
        .service-item {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .service-item:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.05);
        }
        
        .service-item i {
            font-size: 24px;
            margin-bottom: 8px;
            display: block;
        }
        
        .service-item span {
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .btn-community {
            background: white;
            color: #3498db;
            border-radius: 50px;
            padding: 15px 30px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            width: 100%;
            text-align: center;
            transition: all 0.3s;
            border: none;
            font-size: 1.1rem;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .btn-community:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(255,255,255,0.3);
            color: #2980b9;
        }
        
        /* Staff Card */
        .staff-card {
            background: white;
        }
        
        .staff-card .card-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
        }
        
        .role-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
        }
        
        .role-tab {
            flex: 1;
            text-align: center;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .role-tab i {
            display: block;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .role-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .role-tab.admin-tab { color: #e74c3c; }
        .role-tab.manager-tab { color: #3498db; }
        .role-tab.officer-tab { color: #2ecc71; }
        .role-tab.active { color: white; }
        
        .login-form {
            display: none;
        }
        
        .login-form.active {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 50px;
            padding: 12px;
            color: white;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .role-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-admin { background: #e74c3c; color: white; }
        .badge-manager { background: #3498db; color: white; }
        .badge-officer { background: #2ecc71; color: white; }
        
        /* About Section */
        .about-section {
            background: white;
            border-radius: 30px 30px 0 0;
            padding: 70px 0;
            margin-top: 70px;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .section-title h2 i {
            color: #667eea;
            margin-right: 10px;
        }
        
        .section-title p {
            color: #666;
            font-size: 1.1rem;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .about-card {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 30px;
            height: 100%;
            transition: all 0.3s;
            border: 1px solid #e0e0e0;
        }
        
        .about-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: #667eea;
        }
        
        .about-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .about-icon i {
            font-size: 30px;
            color: white;
        }
        
        .about-card h4 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .about-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        
        .contact-info {
            list-style: none;
            padding: 0;
        }
        
        .contact-info li {
            padding: 8px 0;
            color: #666;
        }
        
        .contact-info li i {
            width: 25px;
            color: #667eea;
            margin-right: 10px;
        }
        
        .map-container {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: 40px;
        }
        
        .working-hours {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        
        .working-hours h4 {
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        
        .working-hours p {
            margin: 8px 0;
            font-size: 1rem;
        }
        
        /* Footer */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0 20px;
        }
        
        .footer h5 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .footer h5 i {
            margin-right: 10px;
            color: #667eea;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .footer-links a:hover {
            color: white;
            padding-left: 5px;
        }
        
        .footer-links i {
            margin-right: 8px;
            color: #667eea;
        }
        
        .social-links {
            margin-top: 20px;
        }
        
        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            color: white;
            transition: all 0.3s;
        }
        
        .social-links a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 30px;
            padding-top: 20px;
            text-align: center;
            color: rgba(255,255,255,0.6);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .role-tabs {
                flex-direction: column;
            }
            
            .service-grid {
                grid-template-columns: 1fr;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .about-card {
                margin-bottom: 20px;
            }
        }
        
        /* Alert Messages */
        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease;
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
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-building"></i> Ginjo Guduru Kebele
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services"><i class="fas fa-concierge-bell"></i> Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about"><i class="fas fa-info-circle"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact"><i class="fas fa-envelope"></i> Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <h1 class="hero-title">Welcome to Ginjo Guduru Kebele</h1>
            <p class="hero-subtitle">
                Your trusted partner in community service and digital documentation management. 
                We provide efficient, transparent, and accessible services to all residents.
            </p>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-number">5,000+</div>
                        <div class="stat-label">Residents</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-number">3,200+</div>
                        <div class="stat-label">ID Cards</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-number">1,500+</div>
                        <div class="stat-label">Certificates</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-card">
                        <div class="stat-number">15+</div>
                        <div class="stat-label">Staff Members</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <section id="services" class="container">
        <div class="row g-4">
            <!-- Community Portal Column -->
            <div class="col-lg-6">
                <div class="card community-card">
                    <div class="card-header">
                        <i class="fas fa-users"></i> Community Portal
                    </div>
                    <div class="card-body">
                        <h5>Welcome, Resident!</h5>
                        <p class="small">Access kebele services online - No login required</p>
                        
                        <div class="service-grid">
                            <div class="service-item">
                                <i class="fas fa-id-card"></i>
                                <span>New ID Card</span>
                            </div>
                            <div class="service-item">
                                <i class="fas fa-sync-alt"></i>
                                <span>Renew ID</span>
                            </div>
                            <div class="service-item">
                                <i class="fas fa-baby"></i>
                                <span>Birth Cert</span>
                            </div>
                            <div class="service-item">
                                <i class="fas fa-cross"></i>
                                <span>Death Cert</span>
                            </div>
                            <div class="service-item">
                                <i class="fas fa-ring"></i>
                                <span>Marriage</span>
                            </div>
                            <div class="service-item">
                                <i class="fas fa-heart-broken"></i>
                                <span>Divorce</span>
                            </div>
                            <div class="service-item">
                                <i class="fas fa-gavel"></i>
                                <span>Court Cases</span>
                            </div>
                            <div class="service-item">
                                <i class="fas fa-file-alt"></i>
                                <span>Clearance</span>
                            </div>
                        </div>
                        
                        <!-- Community Login Button -->
                        <form method="POST" action="pages/community-login.php">
                            <button type="submit" class="btn-community">
                                <i class="fas fa-sign-in-alt"></i> Enter Community Portal
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Staff Login Column -->
            <div class="col-lg-6">
                <div class="card staff-card">
                    <div class="card-header">
                        <i class="fas fa-user-lock"></i> Staff Login
                    </div>
                    <div class="card-body">
                        <?php
                        // Show error message if login failed
                        if (isset($_GET['error'])) {
                            $error_msg = '';
                            if ($_GET['error'] == 'invalid') {
                                $error_msg = 'Invalid username or password!';
                            } elseif ($_GET['error'] == 'inactive') {
                                $error_msg = 'Account is inactive. Contact administrator.';
                            } elseif ($_GET['error'] == 'system') {
                                $error_msg = 'System error. Please try again later.';
                            }
                            if ($error_msg) {
                                echo '<div class="alert alert-danger">' . $error_msg . '</div>';
                            }
                        }
                        
                        // Show logout message
                        if (isset($_GET['msg']) && $_GET['msg'] == 'logout') {
                            echo '<div class="alert alert-success">Logged out successfully!</div>';
                        }
                        ?>
                        
                        <!-- Role Selection Tabs -->
                        <div class="role-tabs">
                            <div class="role-tab admin-tab active" onclick="switchRole('admin')" id="tab-admin">
                                <i class="fas fa-crown"></i>
                                <span>Admin</span>
                            </div>
                            <div class="role-tab manager-tab" onclick="switchRole('manager')" id="tab-manager">
                                <i class="fas fa-user-tie"></i>
                                <span>Manager</span>
                            </div>
                            <div class="role-tab officer-tab" onclick="switchRole('officer')" id="tab-officer">
                                <i class="fas fa-user-graduate"></i>
                                <span>Officer</span>
                            </div>
                        </div>
                        
                        <!-- Admin Login Form -->
                        <form class="login-form active" id="admin-form" method="POST" action="pages/login-process.php">
                            <input type="hidden" name="role" value="admin">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" placeholder="Enter admin username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                            </div>
                            <div class="mb-3">
                                <span class="role-badge badge-admin"><i class="fas fa-crown"></i> Administrator</span>
                                <small class="text-muted ms-2">Full system access</small>
                            </div>
                            <button type="submit" class="btn-login">
                                <i class="fas fa-sign-in-alt"></i> Login as Admin
                            </button>
                        </form>
                        
                        <!-- Manager Login Form -->
                        <form class="login-form" id="manager-form" method="POST" action="pages/login-process.php">
                            <input type="hidden" name="role" value="manager">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" placeholder="Enter manager username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                            </div>
                            <div class="mb-3">
                                <span class="role-badge badge-manager"><i class="fas fa-user-tie"></i> Kebele Manager</span>
                                <small class="text-muted ms-2">ID & Court management</small>
                            </div>
                            <button type="submit" class="btn-login">Login as Manager</button>
                        </form>
                        
                        <!-- Officer Login Form -->
                        <form class="login-form" id="officer-form" method="POST" action="pages/login-process.php">
                            <input type="hidden" name="role" value="officer">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" placeholder="Enter officer username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                            </div>
                            <div class="mb-3">
                                <span class="role-badge badge-officer"><i class="fas fa-user-graduate"></i> Record Officer</span>
                                <small class="text-muted ms-2">Certificates & records</small>
                            </div>
                            <button type="submit" class="btn-login">Login as Officer</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt"></i> Authorized personnel only
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="section-title">
                <h2><i class="fas fa-info-circle"></i> About Ginjo Guduru Kebele</h2>
                <p>Dedicated to serving our community with transparency, efficiency, and care since 1991 E.C.</p>
            </div>
            
            <div class="row g-4">
                <!-- Our Story -->
                <div class="col-md-6">
                    <div class="about-card">
                        <div class="about-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <h4>Our Story</h4>
                        <p>Ginjo Guduru Kebele has been the cornerstone of local governance in Jimma Zone for over three decades. Established in 1991 E.C., we have consistently worked to improve the lives of our residents through effective administration and community-focused services.</p>
                        <p>Today, we serve approximately 5,000 residents across 5 sub-kebeles, covering an area of 15 square kilometers. Our dedicated team of 15 staff members works tirelessly to ensure every resident receives the services they need.</p>
                    </div>
                </div>
                
                <!-- Mission & Vision -->
                <div class="col-md-6">
                    <div class="about-card">
                        <div class="about-icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h4>Mission & Vision</h4>
                        <p><strong>Our Mission:</strong> To provide efficient, transparent, and accessible administrative services to all residents while maintaining the highest standards of integrity and professionalism.</p>
                        <p><strong>Our Vision:</strong> To become a model kebele in Ethiopia, leveraging technology to create a seamless, paperless administrative system that serves as an example for others to follow.</p>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div id="contact" class="row g-4 mt-2">
                <div class="col-md-4">
                    <div class="about-card">
                        <div class="about-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4>Our Location</h4>
                        <ul class="contact-info">
                            <li><i class="fas fa-map-pin"></i> Ginjo Guduru Kebele</li>
                            <li><i class="fas fa-city"></i> Jimma Zone, Oromia Region</li>
                            <li><i class="fas fa-flag"></i> Ethiopia</li>
                            <li><i class="fas fa-globe"></i> 5 sub-kebeles, 15 km² area</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="about-card">
                        <div class="about-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <h4>Contact Us</h4>
                        <ul class="contact-info">
                            <li><i class="fas fa-phone"></i> +251 912 345 678</li>
                            <li><i class="fas fa-phone"></i> +251 987 654 321</li>
                            <li><i class="fas fa-envelope"></i> info@ginjoguduru.gov.et</li>
                            <li><i class="fas fa-envelope"></i> support@ginjoguduru.gov.et</li>
                        </ul>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="about-card">
                        <div class="about-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>Office Hours</h4>
                        <ul class="contact-info">
                            <li><i class="fas fa-calendar-day"></i> Monday - Friday: 8:00 AM - 5:00 PM</li>
                            <li><i class="fas fa-calendar-day"></i> Saturday: 8:00 AM - 12:00 PM</li>
                            <li><i class="fas fa-calendar-day"></i> Sunday: Closed</li>
                            <li><i class="fas fa-calendar-day"></i> Public Holidays: Closed</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Emergency Contacts -->
            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <div class="about-card">
                        <div class="about-icon">
                            <i class="fas fa-ambulance"></i>
                        </div>
                        <h4>Emergency Contacts</h4>
                        <div class="row">
                            <div class="col-6">
                                <ul class="contact-info">
                                    <li><i class="fas fa-shield-alt"></i> Police: 911</li>
                                    <li><i class="fas fa-ambulance"></i> Ambulance: 912</li>
                                </ul>
                            </div>
                            <div class="col-6">
                                <ul class="contact-info">
                                    <li><i class="fas fa-fire"></i> Fire: 913</li>
                                    <li><i class="fas fa-shield-alt"></i> Kebele Security: +251 912 345 679</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="about-card">
                        <div class="about-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h4>Community Services</h4>
                        <ul class="contact-info">
                            <li><i class="fas fa-id-card"></i> ID Registration & Renewal</li>
                            <li><i class="fas fa-certificate"></i> Birth, Death, Marriage, Divorce Certificates</li>
                            <li><i class="fas fa-gavel"></i> Social Court Services</li>
                            <li><i class="fas fa-file-alt"></i> Clearance Letters & Support Letters</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Working Hours Banner -->
            <div class="working-hours mt-5">
                <h4><i class="fas fa-clock"></i> Office Hours Summary</h4>
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Monday</strong> 8:00 - 5:00</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Tuesday</strong> 8:00 - 5:00</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Wednesday</strong> 8:00 - 5:00</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Thursday</strong> 8:00 - 5:00</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Friday</strong> 8:00 - 4:30</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Saturday</strong> 8:00 - 12:00</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Sunday</strong> Closed</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Holidays</strong> Closed</p>
                    </div>
                </div>
                <p class="mt-3 mb-0">
                    <i class="fas fa-phone-alt"></i> 24/7 Emergency Hotline: +251 912 345 680
                </p>
            </div>
            
            <!-- Map Placeholder -->
            <div class="map-container mt-4">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31909.34186770154!2d36.823678!3d7.66667!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zN8KwNDAnMDAuMCJOIDM2wrA0OSczMC4wIkU!5e0!3m2!1sen!2set!4v1620000000000!5m2!1sen!2set" 
                    width="100%" 
                    height="400" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-building"></i> Ginjo Guduru Kebele</h5>
                    <p class="small">Serving our community with dedication and transparency since 1991 E.C. Your trusted partner in local governance.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-telegram-plane"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-link"></i> Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="#home"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="#services"><i class="fas fa-chevron-right"></i> Services</a></li>
                        <li><a href="#about"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="#contact"><i class="fas fa-chevron-right"></i> Contact</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Privacy Policy</a></li>
                        <li><a href="#"><i class="fas fa-chevron-right"></i> Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-address-card"></i> Contact Info</h5>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Ginjo Guduru, Jimma, Ethiopia</li>
                        <li><i class="fas fa-phone"></i> +251 912 345 678</li>
                        <li><i class="fas fa-envelope"></i> info@ginjoguduru.gov.et</li>
                        <li><i class="fas fa-clock"></i> Mon-Fri: 8:00 AM - 5:00 PM</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="mb-0">&copy; 2024 Ginjo Guduru Kebele. All rights reserved.</p>
                <p class="small mt-2">Developed by Group 6 - Jimma University CBTP Phase II | Advisor: Mr. Jermia B.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Switch between role login forms
        function switchRole(role) {
            // Hide all forms
            document.querySelectorAll('.login-form').forEach(form => {
                form.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.role-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected form and activate tab
            document.getElementById(role + '-form').classList.add('active');
            document.getElementById('tab-' + role).classList.add('active');
        }
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>