// Main JavaScript for Ginjo Kebele DDMS

// Global variables
let currentUser = null;

// Document ready function
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Form validation
    $('form[data-validate="true"]').on('submit', function(e) {
        let isValid = true;
        $(this).find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showNotification('Please fill in all required fields', 'danger');
        }
    });

    // Password strength meter
    $('#password, #new_password').on('keyup', function() {
        let password = $(this).val();
        let strength = checkPasswordStrength(password);
        updatePasswordStrengthMeter(strength);
    });

    // Confirm delete actions
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });

    // Search functionality
    $('#searchInput').on('keyup', function() {
        let searchText = $(this).val().toLowerCase();
        $('.searchable-row').each(function() {
            let rowText = $(this).text().toLowerCase();
            if (rowText.indexOf(searchText) === -1) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });

    // Print functionality
    $('#printBtn').on('click', function() {
        window.print();
    });

    // Export to Excel
    $('#exportExcel').on('click', function() {
        exportTableToExcel('dataTable', 'export');
    });

    // Export to PDF
    $('#exportPDF').on('click', function() {
        exportToPDF();
    });
});

// Show notification function
function showNotification(message, type = 'info') {
    let notification = `
        <div class="alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3" role="alert" style="z-index: 9999;">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('body').append(notification);
    
    setTimeout(function() {
        $('.alert').last().fadeOut('slow', function() {
            $(this).remove();
        });
    }, 5000);
}

// Check password strength
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    return strength;
}

// Update password strength meter
function updatePasswordStrengthMeter(strength) {
    let meter = $('#passwordStrength');
    let texts = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
    let colors = ['#dc3545', '#ffc107', '#17a2b8', '#28a745', '#28a745'];
    
    if (meter.length) {
        meter.css('width', (strength * 20) + '%');
        meter.text(texts[strength - 1] || '');
        meter.css('background-color', colors[strength - 1] || '#dc3545');
    }
}

// Export table to Excel
function exportTableToExcel(tableId, filename = '') {
    let downloadLink;
    let dataType = 'application/vnd.ms-excel';
    let tableSelect = document.getElementById(tableId);
    let tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    filename = filename ? filename + '.xls' : 'export.xls';
    
    downloadLink = document.createElement("a");
    document.body.appendChild(downloadLink);
    
    if (navigator.msSaveOrOpenBlob) {
        let blob = new Blob(['\ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob(blob, filename);
    } else {
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
        downloadLink.download = filename;
        downloadLink.click();
    }
}

// Export to PDF (simplified)
function exportToPDF() {
    showNotification('PDF export feature coming soon!', 'info');
}

// Date picker initialization
function initDatePicker() {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-ET', {
        style: 'currency',
        currency: 'ETB'
    }).format(amount);
}

// Format date
function formatDate(date) {
    return new Date(date).toLocaleDateString('en-ET', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

// Show loading spinner
function showLoading() {
    $('.spinner-wrapper').addClass('show');
}

// Hide loading spinner
function hideLoading() {
    $('.spinner-wrapper').removeClass('show');
}

// AJAX request with loading
function ajaxRequest(url, method, data, successCallback, errorCallback) {
    showLoading();
    
    $.ajax({
        url: url,
        method: method,
        data: data,
        success: function(response) {
            hideLoading();
            if (successCallback) successCallback(response);
        },
        error: function(xhr, status, error) {
            hideLoading();
            showNotification('An error occurred: ' + error, 'danger');
            if (errorCallback) errorCallback(xhr, status, error);
        }
    });
}

// Form data to JSON
function formToJSON(formId) {
    let form = $('#' + formId);
    let data = {};
    
    form.serializeArray().forEach(function(item) {
        data[item.name] = item.value;
    });
    
    return JSON.stringify(data);
}