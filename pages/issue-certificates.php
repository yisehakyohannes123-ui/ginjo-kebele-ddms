<?php
/**
 * ISSUE CERTIFICATE PAGE
 * Allows Record Officers and Admins to issue new certificates
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once '../config/database.php';

// Define required functions if they don't exist
if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['is_community'])) {
            header("Location: login.php");
            exit();
        }
    }
}

if (!function_exists('hasRole')) {
    function hasRole($role) {
        if (isset($_SESSION['is_community']) && $_SESSION['is_community'] === true) {
            return false;
        }
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
    }
}

// Require login
requireLogin();

// Check if user has permission (Record Officer or Admin only)
if (!hasRole('Record_Officer') && !hasRole('System_Administrator')) {
    header("Location: dashboard.php?error=permission");
    exit();
}

// Get database connection
$conn = getConnection();
if (!$conn) {
    die("Database connection failed");
}

$error = '';
$success = '';

// Get certificate types
$cert_types = $conn->query("SELECT * FROM certificate_types ORDER BY type_name");

// If no certificate types, create them
if ($cert_types->num_rows == 0) {
    $conn->query("INSERT INTO certificate_types (type_name) VALUES 
                  ('Birth Certificate'),
                  ('Death Certificate'),
                  ('Marriage Certificate'),
                  ('Divorce Certificate')");
    $cert_types = $conn->query("SELECT * FROM certificate_types ORDER BY type_name");
}

// Get active residents
$residents = $conn->query("
    SELECT c.*, h.house_number, k.kebele_name 
    FROM customers c
    LEFT JOIN houses h ON c.house_id = h.house_id
    LEFT JOIN kebeles k ON h.kebele_id = k.kebele_id
    WHERE c.is_active = 1
    ORDER BY c.first_name
");

// Generate certificate number
$year = date('Y');
$month = date('m');
$result = $conn->query("SELECT COUNT(*) as count FROM certificates WHERE YEAR(issue_date) = $year");
$count = $result->fetch_assoc()['count'] + 1;
$cert_number = "CERT-$year-$month-" . str_pad($count, 4, '0', STR_PAD_LEFT);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'];
    $type_id = $_POST['type_id'];
    $issue_date = $_POST['issue_date'];
    $certificate_number = $_POST['certificate_number'];
    
    if (empty($customer_id) || empty($type_id) || empty($issue_date)) {
        $error = "Please fill in all required fields!";
    } else {
        // Check if certificate number already exists
        $check = $conn->prepare("SELECT certificate_id FROM certificates WHERE certificate_number = ?");
        $check->bind_param("s", $certificate_number);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            // Generate new number
            $certificate_number = "CERT-$year-$month-" . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        }
        
        // Insert certificate
        $stmt = $conn->prepare("INSERT INTO certificates 
                                (certificate_number, customer_id, type_id, issue_date, issued_by) 
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siisi", $certificate_number, $customer_id, $type_id, $issue_date, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Certificate issued successfully! Number: " . $certificate_number;
            header("Location: certificates.php");
            exit();
        } else {
            $error = "Error issuing certificate: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issue Certificate - Ginjo Kebele DDMS</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Select2 CSS (for better dropdowns) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            padding: 15px 0;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.3rem;
        }
        
        .navbar-brand i {
            margin-right: 10px;
            color: #f1c40f;
        }
        
        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border-radius: 0;
            padding: 20px 25px;
            font-size: 1.3rem;
            font-weight: 600;
            border-bottom: none;
        }
        
        .card-header i {
            margin-right: 10px;
            font-size: 1.5rem;
        }
        
        .card-body {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .form-label i {
            color: #3498db;
            margin-right: 5px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
        }
        
        .form-control[readonly] {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border: none;
            padding: 12px 35px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary {
            background: #95a5a6;
            border: none;
            padding: 12px 35px;
            font-weight: 600;
            border-radius: 50px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 15px;
            padding: 15px 20px;
            border: none;
            animation: slideDown 0.5s ease;
        }
        
        .alert-danger {
            background: #fee;
            color: #c0392b;
            border-left: 4px solid #e74c3c;
        }
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
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
        
        .requirements-list {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .requirements-list li {
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .requirements-list i {
            color: #27ae60;
            margin-right: 10px;
        }
        
        /* Select2 Customization */
        .select2-container--default .select2-selection--single {
            height: 48px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 8px 15px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 46px;
            right: 10px;
        }
        
        /* Loading Spinner */
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .card-body {
                padding: 20px;
            }
            
            .btn-primary, .btn-secondary {
                padding: 10px 25px;
                width: 100%;
                margin: 5px 0;
            }
            
            .text-end {
                text-align: center !important;
            }
        }
        
        /* Print Styles */
        @media print {
            .btn, .navbar, .alert-info {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-certificate"></i> Ginjo Kebele DDMS - Issue Certificate
            </a>
            <div>
                <span class="me-3">
                    <i class="fas fa-user-circle"></i> 
                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Officer'); ?>
                    <span class="badge bg-light text-dark ms-2">
                        <?php echo $_SESSION['user_role'] ?? 'Staff'; ?>
                    </span>
                </span>
                <a href="dashboard.php" class="btn btn-light btn-sm me-2">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="../logout.php" class="btn btn-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb bg-white p-3 rounded-3 shadow-sm">
                <li class="breadcrumb-item">
                    <a href="dashboard.php" class="text-decoration-none">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="certificates.php" class="text-decoration-none">
                        <i class="fas fa-certificate"></i> Certificates
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-plus-circle"></i> Issue New Certificate
                </li>
            </ol>
        </nav>

        <!-- Main Card -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-certificate"></i> Issue New Certificate
                <span class="float-end">
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-info-circle"></i> Record Officer Only
                    </span>
                </span>
            </div>
            
            <div class="card-body">
                <!-- Error Alert -->
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> 
                    <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Success Alert (from session) -->
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> 
                    <strong>Success!</strong> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Certificate Form -->
                <form method="POST" action="" id="certificateForm" class="needs-validation" novalidate>
                    <!-- Certificate Number (Read-only) -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-hashtag"></i> Certificate Number
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="certificate_number" 
                                   value="<?php echo htmlspecialchars($cert_number); ?>" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="refreshNumber" 
                                    data-bs-toggle="tooltip" title="Generate New Number">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> Auto-generated certificate number
                        </small>
                    </div>

                    <!-- Resident Selection -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Select Resident <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="customer_id" id="customerSelect" required>
                            <option value="">-- Search and Select Resident --</option>
                            <?php if ($residents && $residents->num_rows > 0): ?>
                                <?php while($row = $residents->fetch_assoc()): ?>
                                <option value="<?php echo $row['customer_id']; ?>" 
                                        data-house="<?php echo $row['house_number'] ?? 'N/A'; ?>"
                                        data-kebele="<?php echo $row['kebele_name'] ?? 'Ginjo Guduru'; ?>">
                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . ($row['last_name'] ?? '')); ?> - 
                                    House: <?php echo htmlspecialchars($row['house_number'] ?? 'N/A'); ?> - 
                                    <?php echo htmlspecialchars($row['kebele_name'] ?? 'Ginjo Guduru'); ?>
                                </option>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="" disabled>No residents found</option>
                            <?php endif; ?>
                        </select>
                        <div class="invalid-feedback">
                            Please select a resident.
                        </div>
                    </div>

                    <!-- Certificate Type and Issue Date Row -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-tag"></i> Certificate Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" name="type_id" id="typeSelect" required>
                                <option value="">-- Select Certificate Type --</option>
                                <?php if ($cert_types && $cert_types->num_rows > 0): ?>
                                    <?php while($type = $cert_types->fetch_assoc()): ?>
                                    <option value="<?php echo $type['type_id']; ?>">
                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <div class="invalid-feedback">
                                Please select certificate type.
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt"></i> Issue Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" name="issue_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required 
                                   min="2020-01-01" max="<?php echo date('Y-m-d'); ?>">
                            <div class="invalid-feedback">
                                Please select issue date.
                            </div>
                        </div>
                    </div>

                    <!-- Resident Details Preview (Hidden by default, shows when resident selected) -->
                    <div class="alert alert-info" id="residentPreview" style="display: none;">
                        <i class="fas fa-info-circle"></i>
                        <strong>Selected Resident:</strong> 
                        <span id="previewName"></span> | 
                        House: <span id="previewHouse"></span> | 
                        Kebele: <span id="previewKebele"></span>
                    </div>

                    <!-- Requirements List -->
                    <div class="requirements-list">
                        <h6 class="mb-3"><i class="fas fa-clipboard-list"></i> Requirements Checklist:</h6>
                        <ul class="list-unstyled">
                            <li>
                                <i class="fas fa-check-circle"></i>
                                Resident must be registered in the system
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                All information must be verified with original documents
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                Required fees must be paid at the cashier
                            </li>
                            <li>
                                <i class="fas fa-check-circle"></i>
                                Witnesses must be present (for marriage certificates)
                            </li>
                        </ul>
                    </div>

                    <hr class="my-4">

                    <!-- Form Actions -->
                    <div class="text-end">
                        <a href="certificates.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-certificate"></i> Issue Certificate
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Info Card -->
        <div class="card mt-4 bg-light">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <i class="fas fa-id-card fa-2x text-primary mb-2"></i>
                        <h6>ID Required</h6>
                        <small>Resident must present valid ID</small>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-clock fa-2x text-success mb-2"></i>
                        <h6>Processing Time</h6>
                        <small>5-10 minutes</small>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-money-bill fa-2x text-warning mb-2"></i>
                        <h6>Fee</h6>
                        <small>20 Birr per certificate</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });

            // Initialize Select2 for better dropdowns
            $('#customerSelect').select2({
                placeholder: "Search for a resident...",
                allowClear: true,
                width: '100%'
            });

            // Show resident preview when selected
            $('#customerSelect').on('change', function() {
                var selected = $(this).find('option:selected');
                if (selected.val()) {
                    var name = selected.text().split('-')[0].trim();
                    var house = selected.data('house');
                    var kebele = selected.data('kebele');
                    
                    $('#previewName').text(name);
                    $('#previewHouse').text(house);
                    $('#previewKebele').text(kebele);
                    $('#residentPreview').slideDown();
                } else {
                    $('#residentPreview').slideUp();
                }
            });

            // Refresh certificate number
            $('#refreshNumber').click(function() {
                var year = new Date().getFullYear();
                var month = String(new Date().getMonth() + 1).padStart(2, '0');
                var random = Math.floor(Math.random() * 9000 + 1000);
                $('input[name="certificate_number"]').val('CERT-' + year + '-' + month + '-' + random);
                
                // Show feedback
                $(this).tooltip('show');
                setTimeout(function() {
                    $('#refreshNumber').tooltip('dispose');
                }, 1500);
            });

            // Form validation
            var form = document.getElementById('certificateForm');
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    // Disable submit button to prevent double submission
                    $('#submitBtn').prop('disabled', true);
                    $('#submitBtn').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
                }
                form.classList.add('was-validated');
            }, false);

            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow', function() {
                    $(this).remove();
                });
            }, 5000);

            // Preview certificate type info
            $('#typeSelect').change(function() {
                var type = $(this).find('option:selected').text();
                if (type) {
                    var message = '';
                    if (type.includes('Birth')) {
                        message = 'Birth certificate requires: Child\'s name, Date of birth, Parents\' names';
                    } else if (type.includes('Death')) {
                        message = 'Death certificate requires: Date of death, Cause of death, Informant\'s name';
                    } else if (type.includes('Marriage')) {
                        message = 'Marriage certificate requires: Both spouses\' names, Date of marriage, Witnesses';
                    } else if (type.includes('Divorce')) {
                        message = 'Divorce certificate requires: Court case number, Date of divorce';
                    }
                    
                    if (message) {
                        alert(message);
                    }
                }
            });
        });
    </script>
</body>
</html>
<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>