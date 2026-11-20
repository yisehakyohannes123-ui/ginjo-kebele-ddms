<?php
require_once '../config/database.php';
requireLogin();

$conn = getConnection();
$error = '';
$success = '';

// Get houses, jobs for dropdowns
$houses = $conn->query("SELECT h.*, k.kebele_name FROM houses h 
                        JOIN kebeles k ON h.kebele_id = k.kebele_id");
$jobs = $conn->query("SELECT * FROM jobs ORDER BY job_title");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $gender = $_POST['gender'];
    $birth_date = $_POST['birth_date'];
    $house_id = $_POST['house_id'] ?: null;
    $job_id = $_POST['job_id'] ?: null;
    $registration_date = date('Y-m-d');
    
    // Validate
    if (empty($first_name) || empty($gender) || empty($birth_date)) {
        $error = "Please fill in all required fields!";
    } else {
        // Insert customer
        $stmt = $conn->prepare("INSERT INTO customers (first_name, middle_name, last_name, gender, birth_date, house_id, job_id, registration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiis", $first_name, $middle_name, $last_name, $gender, $birth_date, $house_id, $job_id, $registration_date);
        
        if ($stmt->execute()) {
            $customer_id = $stmt->insert_id;
            
            // Insert phone numbers
            if (!empty($_POST['phone1'])) {
                $stmt2 = $conn->prepare("INSERT INTO customer_contacts (customer_id, phone_number, is_primary) VALUES (?, ?, 1)");
                $stmt2->bind_param("is", $customer_id, $_POST['phone1']);
                $stmt2->execute();
            }
            
            if (!empty($_POST['phone2'])) {
                $stmt2 = $conn->prepare("INSERT INTO customer_contacts (customer_id, phone_number, is_primary) VALUES (?, ?, 0)");
                $stmt2->bind_param("is", $customer_id, $_POST['phone2']);
                $stmt2->execute();
            }
            
            header("Location: residents.php?msg=added");
            exit();
        } else {
            $error = "Error adding resident: " . $conn->error;
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-user-plus"></i> Add New Resident</h2>
        </div>
        <div class="col text-end">
            <a href="residents.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Residents
            </a>
        </div>
    </div>
    
    <div class="form-container">
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" data-validate="true">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="first_name" class="form-label">First Name *</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="middle_name" class="form-label">Middle Name</label>
                    <input type="text" class="form-control" id="middle_name" name="middle_name">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" class="form-control" id="last_name" name="last_name">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="gender" class="form-label">Gender *</label>
                    <select class="form-select" id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="birth_date" class="form-label">Date of Birth *</label>
                    <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="job_id" class="form-label">Occupation</label>
                    <select class="form-select" id="job_id" name="job_id">
                        <option value="">Select Occupation</option>
                        <?php while($job = $jobs->fetch_assoc()): ?>
                        <option value="<?php echo $job['job_id']; ?>"><?php echo $job['job_title']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="house_id" class="form-label">House</label>
                    <select class="form-select" id="house_id" name="house_id">
                        <option value="">Select House</option>
                        <?php while($house = $houses->fetch_assoc()): ?>
                        <option value="<?php echo $house['house_id']; ?>">
                            <?php echo $house['house_number'] . ' - ' . $house['kebele_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="phone1" class="form-label">Primary Phone</label>
                    <input type="tel" class="form-control" id="phone1" name="phone1" 
                           placeholder="09xxxxxxxx">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="phone2" class="form-label">Secondary Phone</label>
                    <input type="tel" class="form-control" id="phone2" name="phone2" 
                           placeholder="09xxxxxxxx">
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="text-end">
                <button type="reset" class="btn btn-secondary">Reset</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Resident
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>