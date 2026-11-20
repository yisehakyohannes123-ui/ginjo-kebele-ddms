<?php
/**
 * REPORTS PAGE
 * Generate and view various reports
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get database connection
$conn = getConnection();
$is_community = isCommunity();

// Get report type
$report_type = isset($_GET['type']) ? $_GET['type'] : 'demographic';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Initialize report data
$report_data = [];
$report_title = '';

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="page-title">
                <i class="fas fa-chart-bar"></i> Reports
            </h2>
            <p class="text-muted">
                <?php if ($is_community): ?>
                    View demographic and statistical reports
                <?php else: ?>
                    Generate and export various administrative reports
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <?php if (!$is_community): ?>
            <button class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Export
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Report Controls -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-sliders-h me-2"></i> Report Controls
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select class="form-select" name="type">
                        <option value="demographic" <?php echo $report_type == 'demographic' ? 'selected' : ''; ?>>
                            Demographic Report
                        </option>
                        <?php if (!$is_community): ?>
                        <option value="ids" <?php echo $report_type == 'ids' ? 'selected' : ''; ?>>
                            ID Issuance Report
                        </option>
                        <option value="certificates" <?php echo $report_type == 'certificates' ? 'selected' : ''; ?>>
                            Certificate Report
                        </option>
                        <option value="court" <?php echo $report_type == 'court' ? 'selected' : ''; ?>>
                            Court Cases Report
                        </option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync-alt"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Results -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <i class="fas fa-file-alt me-2"></i> Report Results
            <span class="float-end">
                <?php echo date('d/m/Y', strtotime($date_from)); ?> - <?php echo date('d/m/Y', strtotime($date_to)); ?>
            </span>
        </div>
        <div class="card-body">
            <?php if ($report_type == 'demographic'): ?>
                <!-- Demographic Report -->
                <div class="row">
                    <div class="col-md-6">
                        <h5>Population Statistics</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Total Residents</th>
                                <td>5,423</td>
                            </tr>
                            <tr>
                                <th>Male</th>
                                <td>2,845 (52.5%)</td>
                            </tr>
                            <tr>
                                <th>Female</th>
                                <td>2,578 (47.5%)</td>
                            </tr>
                            <tr>
                                <th>Under 18</th>
                                <td>1,245 (23%)</td>
                            </tr>
                            <tr>
                                <th>18-35 years</th>
                                <td>2,168 (40%)</td>
                            </tr>
                            <tr>
                                <th>36-60 years</th>
                                <td>1,412 (26%)</td>
                            </tr>
                            <tr>
                                <th>60+ years</th>
                                <td>598 (11%)</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Occupation Distribution</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th>Farmer</th>
                                <td>2,150</td>
                            </tr>
                            <tr>
                                <th>Merchant</th>
                                <td>890</td>
                            </tr>
                            <tr>
                                <th>Government Employee</th>
                                <td>445</td>
                            </tr>
                            <tr>
                                <th>Private Employee</th>
                                <td>678</td>
                            </tr>
                            <tr>
                                <th>Student</th>
                                <td>890</td>
                            </tr>
                            <tr>
                                <th>Other</th>
                                <td>370</td>
                            </tr>
                        </table>
                    </div>
                </div>

            <?php elseif ($report_type == 'ids' && !$is_community): ?>
                <!-- ID Report -->
                <h5>ID Issuance Summary</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Total IDs Issued</th>
                        <td>3,456</td>
                    </tr>
                    <tr>
                        <th>Active IDs</th>
                        <td>3,124</td>
                    </tr>
                    <tr>
                        <th>Renewed IDs</th>
                        <td>892</td>
                    </tr>
                    <tr>
                        <th>Expiring in 30 days</th>
                        <td>45</td>
                    </tr>
                </table>

            <?php elseif ($report_type == 'certificates' && !$is_community): ?>
                <!-- Certificate Report -->
                <h5>Certificate Issuance Summary</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Total Certificates</th>
                        <td>1,789</td>
                    </tr>
                    <tr>
                        <th>Birth Certificates</th>
                        <td>892</td>
                    </tr>
                    <tr>
                        <th>Death Certificates</th>
                        <td>234</td>
                    </tr>
                    <tr>
                        <th>Marriage Certificates</th>
                        <td>445</td>
                    </tr>
                    <tr>
                        <th>Divorce Certificates</th>
                        <td>218</td>
                    </tr>
                </table>

            <?php elseif ($report_type == 'court' && !$is_community): ?>
                <!-- Court Report -->
                <h5>Court Cases Summary</h5>
                <table class="table table-bordered">
                    <tr>
                        <th>Total Cases</th>
                        <td>156</td>
                    </tr>
                    <tr>
                        <th>Pending</th>
                        <td>23</td>
                    </tr>
                    <tr>
                        <th>Decided</th>
                        <td>98</td>
                    </tr>
                    <tr>
                        <th>Closed</th>
                        <td>35</td>
                    </tr>
                </table>
            <?php endif; ?>

            <div class="text-muted small mt-3">
                <i class="fas fa-info-circle me-1"></i> 
                Report generated on: <?php echo date('l, F j, Y \a\t g:i A'); ?>
            </div>
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    alert('Export to Excel feature coming soon!');
}
</script>

<?php
$conn->close();
include '../includes/footer.php';
?>