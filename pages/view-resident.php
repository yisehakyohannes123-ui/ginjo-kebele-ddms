<?php
require_once '../config/database.php';
requireLogin();

$conn = getConnection();
$customer_id = (int)$_GET['id'];

// Get resident details
$resident = $conn->prepare("
    SELECT c.*, h.house_number, k.kebele_name, k.zone, j.job_title
    FROM customers c
    LEFT JOIN houses h ON c.house_id = h.house_id
    LEFT JOIN kebeles k ON h.kebele_id = k.kebele_id
    LEFT JOIN jobs j ON c.job_id = j.job_id
    WHERE c.customer_id = ?
");
$resident->bind_param("i", $customer_id);
$resident->execute();
$resident_data = $resident->get_result()->fetch_assoc();

if (!$resident_data) {
    header("Location: residents.php");
    exit();
}

// Get resident contacts
$contacts = $conn->prepare("SELECT * FROM customer_contacts WHERE customer_id = ?");
$contacts->bind_param("i", $customer_id);
$contacts->execute();
$contacts_data = $contacts->get_result();

// Get issued IDs
$ids = $conn->prepare("
    SELECT i.*, u.full_name as issued_by_name
    FROM ids i
    JOIN users u ON i.issued_by = u.user_id
    WHERE i.customer_id = ?
    ORDER BY i.issued_date DESC
");
$ids->bind_param("i", $customer_id);
$ids->execute();
$ids_data = $ids->get_result();

// Get certificates
$certificates = $conn->prepare("
    SELECT cert.*, ct.type_name, u.full_name as issued_by_name
    FROM certificates cert
    JOIN certificate_types ct ON cert.type_id = ct.type_id
    JOIN users u ON cert.issued_by = u.user_id
    WHERE cert.customer_id = ?
    ORDER BY cert.issue_date DESC
");
$certificates->bind_param("i", $customer_id);
$certificates->execute();
$certificates_data = $certificates->get_result();

// Get court cases
$cases = $conn->prepare("
    SELECT * FROM social_court_cases
    WHERE customer_id = ?
    ORDER BY hearing_date DESC
");
$cases->bind_param("i", $customer_id);
$cases->execute();
$cases_data = $cases->get_result();
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h2><i class="fas fa-user"></i> Resident Profile</h2>
        </div>
        <div class="col text-end">
            <a href="residents.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Residents
            </a>
            <a href="edit-resident.php?id=<?php echo $customer_id; ?>" class="btn btn-warning">
                <i class="fas fa-edit"></i> Edit
            </a>
        </div>
    </div>

    <!-- Resident Information -->
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-id-card"></i> Personal Information
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Full Name:</th>
                            <td>
                                <?php 
                                echo $resident_data['first_name'] . ' '; 
                                if ($resident_data['middle_name']) echo $resident_data['middle_name'] . ' ';
                                echo $resident_data['last_name'];
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Gender:</th>
                            <td><?php echo $resident_data['gender']; ?></td>
                        </tr>
                        <tr>
                            <th>Date of Birth:</th>
                            <td><?php echo date('d/m/Y', strtotime($resident_data['birth_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Age:</th>
                            <td><?php echo date_diff(date_create($resident_data['birth_date']), date_create('today'))->y; ?> years</td>
                        </tr>
                        <tr>
                            <th>Occupation:</th>
                            <td><?php echo $resident_data['job_title'] ?? 'N/A'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-map-marker-alt"></i> Address Information
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>House Number:</th>
                            <td><?php echo $resident_data['house_number'] ?? 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Kebele:</th>
                            <td><?php echo $resident_data['kebele_name'] ?? 'N/A'; ?></td>
                        </tr>
                        <tr>
                            <th>Zone:</th>
                            <td><?php echo $resident_data['zone'] ?? 'N/A'; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-phone"></i> Contact Information
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <?php if ($contacts_data->num_rows > 0): ?>
                            <?php while($contact = $contacts_data->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo $contact['phone_number']; ?>
                                    <?php if ($contact['is_primary']): ?>
                                    <span class="badge bg-success">Primary</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td class="text-muted">No contact information</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- IDs Section -->
    <div class="card mb-4">
        <div class="card-header bg-warning">
            <i class="fas fa-id-card"></i> Issued IDs
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Issue Date</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Issued By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($ids_data->num_rows > 0): ?>
                        <?php while($id = $ids_data->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $id['id_number']; ?></strong></td>
                            <td><?php echo date('d/m/Y', strtotime($id['issued_date'])); ?></td>
                            <td><?php echo $id['expiry_date'] ? date('d/m/Y', strtotime($id['expiry_date'])) : 'Lifetime'; ?></td>
                            <td>
                                <?php if ($id['is_renewed']): ?>
                                <span class="badge bg-warning">Renewed</span>
                                <?php else: ?>
                                <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $id['issued_by_name']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No IDs issued</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Certificates Section -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-certificate"></i> Issued Certificates
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Certificate #</th>
                        <th>Type</th>
                        <th>Issue Date</th>
                        <th>Issued By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($certificates_data->num_rows > 0): ?>
                        <?php while($cert = $certificates_data->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $cert['certificate_number']; ?></td>
                            <td><?php echo $cert['type_name']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($cert['issue_date'])); ?></td>
                            <td><?php echo $cert['issued_by_name']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No certificates issued</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Court Cases Section -->
    <?php if (hasRole('Kebele_Manager')): ?>
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-gavel"></i> Court Cases
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Case #</th>
                        <th>Type</th>
                        <th>Hearing Date</th>
                        <th>Status</th>
                        <th>Verdict</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($cases_data->num_rows > 0): ?>
                        <?php while($case = $cases_data->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $case['case_number']; ?></td>
                            <td><?php echo $case['case_type']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($case['hearing_date'])); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                if ($case['status'] == 'Pending') $status_class = 'warning';
                                elseif ($case['status'] == 'Decided') $status_class = 'success';
                                elseif ($case['status'] == 'Closed') $status_class = 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $status_class; ?>">
                                    <?php echo $case['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $case['verdict'] ?? 'Pending'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No court cases</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include '../includes/footer.php';
?>