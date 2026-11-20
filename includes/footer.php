<?php
/**
 * FOOTER TEMPLATE
 * Includes common JavaScript files and closing tags
 */

// Check if user is logged in
$is_logged_in = isLoggedIn();
?>

<?php if ($is_logged_in): ?>
            </div> <!-- Close content-area -->
        </div> <!-- Close main-content -->
    </div> <!-- Close row -->
</div> <!-- Close container-fluid -->

<!-- Footer for logged-in users -->
<footer class="footer mt-auto py-3 bg-light">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6 text-muted small">
                &copy; <?php echo date('Y'); ?> Ginjo Guduru Kebele. All rights reserved.
            </div>
            <div class="col-md-6 text-end text-muted small">
                Developed by Group 6 | Jimma University CBTP Phase II
            </div>
        </div>
    </div>
</footer>

<?php else: ?>
    </div> <!-- Close container -->
<?php endif; ?>

<!-- jQuery (already loaded in header) -->
<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Chart.js for graphs -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<!-- Custom JS -->
<script src="/ginjo-kebele-ddms/assets/js/main.js"></script>

<!-- Loading Spinner -->
<div class="spinner-wrapper">
    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<script>
    // Global JavaScript functions
    $(document).ready(function() {
        // Initialize DataTables
        if ($('#dataTable').length) {
            $('#dataTable').DataTable({
                pageLength: 10,
                responsive: true,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search records...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    paginate: {
                        first: '<i class="fas fa-angle-double-left"></i>',
                        previous: '<i class="fas fa-angle-left"></i>',
                        next: '<i class="fas fa-angle-right"></i>',
                        last: '<i class="fas fa-angle-double-right"></i>'
                    }
                }
            });
        }
        
        // Initialize Select2 for dropdowns
        if ($('.select2').length) {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Initialize popovers
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Confirm delete actions
        $('.delete-confirm').on('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
        
        // Add CSRF token to all AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    });
    
    // Loading spinner functions
    function showLoading() {
        $('.spinner-wrapper').addClass('show');
    }
    
    function hideLoading() {
        $('.spinner-wrapper').removeClass('show');
    }
    
    // AJAX error handling
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        hideLoading();
        let errorMsg = 'An error occurred: ' + thrownError;
        if (jqxhr.responseJSON && jqxhr.responseJSON.message) {
            errorMsg = jqxhr.responseJSON.message;
        }
        alert(errorMsg);
    });
    
    // Global search functionality
    function globalSearch() {
        let searchTerm = prompt('Enter search term:');
        if (searchTerm) {
            window.location.href = 'search.php?q=' + encodeURIComponent(searchTerm);
        }
    }
    
    // Print current page
    function printPage() {
        window.print();
    }
    
    // Export to Excel
    function exportToExcel(tableId, filename) {
        let table = document.getElementById(tableId);
        let html = table.outerHTML;
        let url = 'data:application/vnd.ms-excel,' + escape(html);
        let downloadLink = document.createElement('a');
        downloadLink.href = url;
        downloadLink.download = filename + '.xls';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }
    
    // Export to PDF (simplified)
    function exportToPDF() {
        alert('PDF export feature coming soon!');
    }
</script>

<style>
    /* Footer Styles */
    .footer {
        margin-top: auto;
        border-top: 1px solid #dee2e6;
    }
    
    /* Loading Spinner Styles */
    .spinner-wrapper {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(3px);
    }
    
    .spinner-wrapper.show {
        display: flex;
    }
    
    /* Print Styles */
    @media print {
        .no-print, 
        .btn, 
        .navbar, 
        .sidebar, 
        footer, 
        .breadcrumb-area,
        .quick-action-btn,
        .card-header {
            display: none !important;
        }
        
        body {
            background: white !important;
        }
        
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            break-inside: avoid;
        }
        
        .table {
            border-collapse: collapse !important;
            width: 100% !important;
        }
        
        .table td, .table th {
            background-color: white !important;
            color: black !important;
            border: 1px solid #ddd !important;
        }
        
        .main-content {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }
    }
    
    /* Alert Animations */
    .alert {
        animation: slideInRight 0.3s ease-out;
        border-left-width: 4px;
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }
    
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 10px;
        border: 2px solid #f1f1f1;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #764ba2, #667eea);
    }
    
    /* Firefox scrollbar */
    * {
        scrollbar-width: thin;
        scrollbar-color: #764ba2 #f1f1f1;
    }
</style>

<!-- CSRF Token for security -->
<meta name="csrf-token" content="<?php echo md5(uniqid()); ?>">

</body>
</html>