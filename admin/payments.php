<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
$admin_id = $_SESSION['admin_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        .payments-container { margin-left: 240px; margin-top: 70px; padding: 40px 24px 24px 24px; }
        .payments-title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; }
        @media (max-width: 1200px) {
            .payments-container { margin-left: 70px; padding: 18px 4px; }
        }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="payments-container">
    <h2 class="payments-title text-center">All Payments & Revenue</h2>
    <!-- Filter/Search Form UI -->
    <div id="transactionHistorySection" style="display:block;">
        <div class="card bg-dark text-light rounded-4 shadow mb-4">
            <div class="card-body">
                <form class="row g-3 align-items-end mb-3" id="paymentsFilterForm">
                    <div class="col-md-2">
                        <label for="filter_method" class="form-label">Payment Method</label>
                        <select class="form-select form-select-sm rounded-3" id="filter_method" name="payment_method">
                            <option value="">All</option>
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="PayPal">PayPal</option>
                            <option value="Bank">Bank</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Wallet">Wallet</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_status" class="form-label">Status</label>
                        <select class="form-select form-select-sm rounded-3" id="filter_status" name="payment_status">
                            <option value="">All</option>
                            <option value="Paid">Paid</option>
                            <option value="Pending">Pending</option>
                            <option value="Failed">Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="filter_date_from" class="form-label">Date From</label>
                        <input type="date" class="form-control form-control-sm rounded-3" id="filter_date_from" name="date_from">
                    </div>
                    <div class="col-md-2">
                        <label for="filter_date_to" class="form-label">Date To</label>
                        <div class="input-group">
                            <input type="date" class="form-control form-control-sm rounded-3" id="filter_date_to" name="date_to">
                            <button type="submit" class="btn btn-warning btn-sm ms-2 rounded-3">Filter</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label for="filter_search" class="form-label">Search (Guest or Reservation ID)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-0"><i class="bi bi-search text-warning"></i></span>
                            <input type="text" class="form-control form-control-sm rounded-end-3" id="filter_search" name="search" placeholder="Guest name or Reservation ID">
                        </div>
                    </div>
                </form>
                <div class="d-flex justify-content-center mb-4">
                    <div style="width:100%;max-width:1100px;">
                        <table class="table table-dark table-hover table-striped table-bordered modern-table mb-0">
                            <thead>
                                <tr>
                                    <th data-sort="reservation_id">Reservation ID <span class="sort-icon" id="sort-icon-reservation_id"></span></th>
                                    <th data-sort="guest_name">Guest Name <span class="sort-icon" id="sort-icon-guest_name"></span></th>
                                    <th data-sort="payment_method">Payment Method <span class="sort-icon" id="sort-icon-payment_method"></span></th>
                                    <th data-sort="amount">Amount Paid <span class="sort-icon" id="sort-icon-amount"></span></th>
                                    <th data-sort="payment_status">Payment Status <span class="sort-icon" id="sort-icon-payment_status"></span></th>
                                    <th data-sort="created_at">Date of Payment <span class="sort-icon" id="sort-icon-created_at"></span></th>
                                    <th>Options</th>
                                </tr>
                            </thead>
                            <tbody id="paymentsTableBody">
                                <!-- AJAX loaded rows here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- View Receipt Modal -->
<div class="modal fade" id="viewReceiptModal" tabindex="-1" aria-labelledby="viewReceiptModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title" id="viewReceiptModalLabel">Payment Receipt</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="receiptModalBody">
        <div class="text-center text-secondary py-4">Loading...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="printReceiptBtn">Print</button>
      </div>
    </div>
  </div>
</div>
<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
  <div id="toastContainer"></div>
</div>
<script>
// Default sort
let currentSortBy = 'created_at';
let currentSortDir = 'desc';

function getFilterParams() {
    return {
        payment_method: $('#filter_method').val(),
        payment_status: $('#filter_status').val(),
        date_from: $('#filter_date_from').val(),
        date_to: $('#filter_date_to').val(),
        search: $('#filter_search').val(),
    };
}

function loadPaymentsTable(sortBy = currentSortBy, sortDir = currentSortDir) {
    let params = getFilterParams();
    params.sort_by = sortBy;
    params.sort_dir = sortDir;
    $.get('ajax_payments_table.php', params, function(data) {
        $('#paymentsTableBody').html(data);
        // Update sort icons
        $(".sort-icon").html('');
        let icon = sortDir === 'asc' ? '▲' : '▼';
        $('#sort-icon-' + sortBy).html(icon);
    });
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

$(function() {
    // Initial load
    loadPaymentsTable();
    // Sorting
    $('.modern-table thead th[data-sort]').on('click', function() {
        let sortBy = $(this).data('sort');
        if (currentSortBy === sortBy) {
            currentSortDir = (currentSortDir === 'asc') ? 'desc' : 'asc';
        } else {
            currentSortBy = sortBy;
            currentSortDir = 'asc';
        }
        loadPaymentsTable(currentSortBy, currentSortDir);
    });
    // Filter form submit
    $('#paymentsFilterForm').on('submit', function(e) {
        e.preventDefault();
        loadPaymentsTable(currentSortBy, currentSortDir);
    });
    // Auto-update table when Payment Method or Status changes
    $('#filter_method, #filter_status').on('change', function() {
        loadPaymentsTable(currentSortBy, currentSortDir);
    });
    // Auto-update table when Date From or Date To changes
    $('#filter_date_from, #filter_date_to').on('change input', function() {
        loadPaymentsTable(currentSortBy, currentSortDir);
    });
    // Search input: live update with debounce
    $('#filter_search').on('input', debounce(function() {
        loadPaymentsTable(currentSortBy, currentSortDir);
    }, 350));

    // View Receipt button click
    $(document).on('click', '.view-receipt-btn', function() {
        const paymentId = $(this).data('payment-id');
        $('#viewReceiptModal').modal('show');
        $('#receiptModalBody').html('<div class="text-center text-secondary py-4">Loading...</div>');
        $.get('process_view_receipt.php', { payment_id: paymentId }, function(data) {
            $('#receiptModalBody').html(data);
        });
    });

    // Print Receipt
    $('#printReceiptBtn').on('click', function() {
        let printContents = document.getElementById('receiptModalBody').innerHTML;
        let originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    });

    // Re-send Confirmation button click
    $(document).on('click', '.resend-confirmation-btn', function() {
        const btn = $(this);
        const paymentId = btn.data('payment-id');
        btn.prop('disabled', true);
        $.post('process_resend_confirmation.php', { payment_id: paymentId }, function(resp) {
            let toastType = resp.success ? 'bg-success' : 'bg-danger';
            let toastHtml = `<div class="toast align-items-center text-white ${toastType} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">` +
                `<div class="d-flex"><div class="toast-body">${resp.message}</div>` +
                `<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`;
            $('#toastContainer').append(toastHtml);
            let toastEl = $('#toastContainer .toast').last()[0];
            let toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
            btn.prop('disabled', false);
        }, 'json').fail(function() {
            let toastHtml = `<div class="toast align-items-center text-white bg-danger border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">` +
                `<div class="d-flex"><div class="toast-body">Failed to send confirmation.</div>` +
                `<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`;
            $('#toastContainer').append(toastHtml);
            let toastEl = $('#toastContainer .toast').last()[0];
            let toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
            btn.prop('disabled', false);
        });
    });
});
</script>
</body>
</html> 