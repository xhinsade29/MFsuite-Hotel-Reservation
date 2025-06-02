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
    <title>All Payments & Revenue</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .modern-table-wrapper {
            background: rgba(255,255,255,0.03);
            border-radius: 20px;
            box-shadow: 0 2px 16px rgba(255,140,0,0.07);
            padding: 24px 16px 16px 16px;
            margin-bottom: 0;
        }
        .modern-table {
            border-radius: 18px !important;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(255,140,0,0.06);
            font-size: 1.08em;
            background: #23234a;
        }
        .modern-table th, .modern-table td {
            border: none !important;
            background: transparent !important;
            vertical-align: middle;
            font-size: 1.07em;
            color: #fff;
        }
        .modern-table thead th {
            background: rgba(255,140,0,0.08) !important;
            color: #ffa533;
            font-weight: 600;
            font-size: 1.12em;
            letter-spacing: 0.5px;
            cursor: pointer;
            user-select: none;
        }
        .modern-table tbody tr {
            background: transparent !important;
        }
        .modern-table tbody tr:hover {
            background: rgba(255,140,0,0.07) !important;
        }
        .sort-icon {
            font-size: 0.9em;
            margin-left: 4px;
        }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="container py-4">
    <h2 class="mb-4">All Payments & Revenue</h2>
    <!-- Filter/Search Form UI -->
    <form class="row g-3 align-items-end mb-4" id="paymentsFilterForm">
        <div class="col-md-2">
            <label for="filter_method" class="form-label">Payment Method</label>
            <select class="form-select" id="filter_method" name="payment_method">
                <option value="">All</option>
                <option value="Cash">Cash</option>
                <option value="GCash">GCash</option>
                <option value="PayPal">PayPal</option>
                <option value="Bank">Bank</option>
                <option value="Credit Card">Credit Card</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="filter_status" class="form-label">Status</label>
            <select class="form-select" id="filter_status" name="payment_status">
                <option value="">All</option>
                <option value="Paid">Paid</option>
                <option value="Pending">Pending</option>
                <option value="Failed">Failed</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="filter_date_from" class="form-label">Date From</label>
            <input type="date" class="form-control" id="filter_date_from" name="date_from">
        </div>
        <div class="col-md-2">
            <label for="filter_date_to" class="form-label">Date To</label>
            <div class="input-group">
                <input type="date" class="form-control" id="filter_date_to" name="date_to">
                <button type="submit" class="btn btn-primary ms-2">Filter</button>
            </div>
        </div>
        <div class="col-md-4">
            <label for="filter_search" class="form-label">Search (Guest or Reservation ID)</label>
            <input type="text" class="form-control" id="filter_search" name="search" placeholder="Guest name or Reservation ID">
        </div>
    </form>
    <div class="modern-table-wrapper mb-4">
        <?php
        // Total revenue
        $total_sql = "SELECT SUM(amount) as total FROM tbl_payment WHERE payment_status = 'Paid'";
        $total_res = $mycon->query($total_sql);
        $total_revenue = 0;
        if ($total_res && $row = $total_res->fetch_assoc()) {
            $total_revenue = $row['total'] ?: 0;
        }
        echo '<div class="fs-4 fw-bold text-success mb-3">Total Revenue: ₱' . number_format($total_revenue, 2) . '</div>';
        ?>
        <table class="table modern-table mb-0">
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
    // Remove date range auto-update
    $('#filter_date_from, #filter_date_to').off('change input');

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