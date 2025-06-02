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
        .payments-container { margin-left: 240px; padding: 40px 24px 24px 24px; }
        .payments-title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; }
        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 40px;
            justify-content: flex-start;
        }
        .summary-card {
            background: #23234a;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 18px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 180px;
            flex: 1 1 120px;
            max-width: 220px;
            cursor: pointer;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .summary-card:hover { box-shadow: 0 8px 32px #ffa53333; transform: translateY(-2px) scale(1.02); }
        .summary-icon { font-size: 1.5rem; color: #FF8C00; background: rgba(255,140,0,0.08); border-radius: 8px; padding: 8px; display: flex; align-items: center; justify-content: center; }
        .summary-info { display: flex; flex-direction: column; }
        .summary-label { color: #bdbdbd; font-size: 0.95em; }
        .summary-value { color: #ffa533; font-size: 1.2rem; font-weight: 700; }
        .big-summary-table-card .card-body { padding: 2rem 1.2rem; }
        .big-summary-table-card .fs-5 { font-size: 1.5rem !important; }
        .big-summary-table-card table { font-size: 1em; }
        .big-summary-table-card th, .big-summary-table-card td { padding: 0.7em 0.8em !important; font-size: 1em; }
        .big-summary-table-card thead th { font-size: 1.08em; }
        .modal-content.bg-dashboard { background: #23234a !important; color: #fff; border-radius: 18px; box-shadow: 0 8px 32px #ffa53322; }
        .modal-header.dashboard-modal-header { border: none; background: transparent; justify-content: center; }
        .modal-title.dashboard-modal-title { color: #ffa533; font-size: 2rem; font-weight: 700; letter-spacing: 1px; width: 100%; text-align: center; }
        .dashboard-modal-table { background: #f8f9fa; border-radius: 12px; box-shadow: 0 2px 12px #ffa53311; }
        .dashboard-modal-table th { background: #ffe5c2; color: #23234a; font-weight: 600; }
        .dashboard-modal-table td { color: #23234a; }
        .dashboard-modal-table tr:nth-child(even) { background: #fff; }
        .dashboard-modal-table tr:nth-child(odd) { background: #f8f9fa; }
        .dashboard-modal-table th, .dashboard-modal-table td { vertical-align: middle; }
        @media (max-width: 1200px) {
            .payments-container { margin-left: 70px; padding: 18px 4px; }
            .summary-cards { gap: 18px; }
        }
        @media (max-width: 900px) {
            .summary-cards { flex-direction: column; gap: 18px; }
            .summary-card { max-width: 100%; min-width: 0; }
        }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="container py-4">
   
    <!-- Summary metrics -->
    <?php
    // Summary metrics
    $summary = [
        'total_transactions' => 0,
        'total_revenue' => 0,
        'todays_revenue' => 0,
        'total_refunded' => 0,
    ];
    // Total transactions
    $res = $mycon->query("SELECT COUNT(*) as cnt FROM tbl_payment");
    if ($res && $row = $res->fetch_assoc()) $summary['total_transactions'] = $row['cnt'];
    // Total revenue (Paid only)
    $res = $mycon->query("SELECT SUM(amount) as total FROM tbl_payment WHERE payment_status = 'Paid'");
    if ($res && $row = $res->fetch_assoc()) $summary['total_revenue'] = $row['total'] ?: 0;
    // Today's revenue (Paid only)
    $res = $mycon->query("SELECT SUM(amount) as total FROM tbl_payment WHERE payment_status = 'Paid' AND DATE(created_at) = CURDATE()");
    if ($res && $row = $res->fetch_assoc()) $summary['todays_revenue'] = $row['total'] ?: 0;
    // Total refunded
    $res = $mycon->query("SELECT SUM(amount) as total FROM tbl_payment WHERE payment_status = 'Refunded'");
    if ($res && $row = $res->fetch_assoc()) $summary['total_refunded'] = $row['total'] ?: 0;
    ?>
    <h2 class="payments-title text-center">All Payments & Revenue</h2>
    <div class="payments-container">
        <div class="summary-cards">
            <div class="summary-card" id="card-transactions" style="cursor:pointer;">
                <span class="summary-icon"><i class="bi bi-receipt"></i></span>
                <div class="summary-info">
                    <span class="summary-label">Total Transactions</span>
                    <span class="summary-value"><?php echo number_format($summary['total_transactions']); ?></span>
                </div>
            </div>
            <div class="summary-card" id="card-revenue" style="cursor:pointer;">
                <span class="summary-icon"><i class="bi bi-cash-coin"></i></span>
                <div class="summary-info">
                    <span class="summary-label">Total Revenue</span>
                    <span class="summary-value">₱<?php echo number_format($summary['total_revenue'], 2); ?></span>
                </div>
            </div>
            <div class="summary-card" id="card-today" style="cursor:pointer;">
                <span class="summary-icon"><i class="bi bi-calendar-day"></i></span>
                <div class="summary-info">
                    <span class="summary-label">Today's Revenue</span>
                    <span class="summary-value">₱<?php echo number_format($summary['todays_revenue'], 2); ?></span>
                </div>
            </div>
            <div class="summary-card" id="card-refunded" style="cursor:pointer;">
                <span class="summary-icon"><i class="bi bi-arrow-counterclockwise"></i></span>
                <div class="summary-info">
                    <span class="summary-label">Total Refunded</span>
                    <span class="summary-value">₱<?php echo number_format($summary['total_refunded'], 2); ?></span>
                </div>
            </div>
        </div>
        <!-- Summary Tables -->
        <div class="mb-4">
            <div class="card bg-dark text-light rounded-4 shadow mb-4 big-summary-table-card w-100" id="transactions-table">
                <div class="card-body">
                    <div class="fs-5 fw-bold mb-3 text-warning">Total Transactions</div>
                    <table class="table table-dark table-hover table-striped table-bordered align-middle mb-0 w-100">
                        <thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
                        <tr><td><span class='badge bg-success'>Paid</span></td><td><?php $r=$mycon->query("SELECT COUNT(*) as cnt FROM tbl_payment WHERE payment_status='Paid'"); $row=$r->fetch_assoc(); echo number_format($row['cnt']); ?></td></tr>
                        <tr><td><span class='badge bg-warning text-dark'>Pending</span></td><td><?php $r=$mycon->query("SELECT COUNT(*) as cnt FROM tbl_payment WHERE payment_status='Pending'"); $row=$r->fetch_assoc(); echo number_format($row['cnt']); ?></td></tr>
                        <tr><td><span class='badge bg-danger'>Failed</span></td><td><?php $r=$mycon->query("SELECT COUNT(*) as cnt FROM tbl_payment WHERE payment_status='Failed'"); $row=$r->fetch_assoc(); echo number_format($row['cnt']); ?></td></tr>
                        <tr><td><span class='badge bg-info text-dark'>Refunded</span></td><td><?php $r=$mycon->query("SELECT COUNT(*) as cnt FROM tbl_payment WHERE payment_status='Refunded'"); $row=$r->fetch_assoc(); echo number_format($row['cnt']); ?></td></tr>
                        </tbody></table>
                </div>
            </div>
            <div class="card bg-dark text-light rounded-4 shadow mb-4 big-summary-table-card w-100" id="revenue-table">
                <div class="card-body">
                    <div class="fs-5 fw-bold mb-3 text-success">Total Revenue</div>
                    <div class='mb-3'>Sum of all successful (Paid) payments.</div>
                    <table class="table table-dark table-hover table-striped table-bordered align-middle mb-0 w-100">
                        <thead><tr><th>Guest</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead><tbody>
                        <?php $res = $mycon->query("SELECT p.amount, p.payment_method, p.created_at, CONCAT(g.first_name, ' ', g.last_name) as guest FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id WHERE p.payment_status = 'Paid' ORDER BY p.created_at DESC LIMIT 20");
                        while($row = $res->fetch_assoc()): ?>
                        <tr><td><?php echo htmlspecialchars($row['guest']); ?></td><td>₱<?php echo number_format($row['amount'],2); ?></td><td><?php echo htmlspecialchars($row['payment_method']); ?></td><td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td></tr>
                        <?php endwhile; ?>
                        </tbody></table>
                </div>
            </div>
            <div class="card bg-dark text-light rounded-4 shadow mb-4 big-summary-table-card w-100" id="today-table">
                <div class="card-body">
                    <div class="fs-5 fw-bold mb-3 text-primary">Today's Revenue</div>
                    <div class='mb-3'>Total revenue received today from Paid payments.</div>
                    <table class="table table-dark table-hover table-striped table-bordered align-middle mb-0 w-100">
                        <thead><tr><th>Guest</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead><tbody>
                        <?php $res = $mycon->query("SELECT p.amount, p.payment_method, p.created_at, CONCAT(g.first_name, ' ', g.last_name) as guest FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id WHERE p.payment_status = 'Paid' AND DATE(p.created_at) = CURDATE() ORDER BY p.created_at DESC LIMIT 20");
                        while($row = $res->fetch_assoc()): ?>
                        <tr><td><?php echo htmlspecialchars($row['guest']); ?></td><td>₱<?php echo number_format($row['amount'],2); ?></td><td><?php echo htmlspecialchars($row['payment_method']); ?></td><td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td></tr>
                        <?php endwhile; ?>
                        </tbody></table>
                </div>
            </div>
            <div class="card bg-dark text-light rounded-4 shadow mb-4 big-summary-table-card w-100" id="refunded-table">
                <div class="card-body">
                    <div class="fs-5 fw-bold mb-3 text-danger">Total Refunded</div>
                    <div class='mb-3'>Sum of all refunded payments.</div>
                    <table class="table table-dark table-hover table-striped table-bordered align-middle mb-0 w-100">
                        <thead><tr><th>Guest</th><th>Amount</th><th>Method</th><th>Date</th></tr></thead><tbody>
                        <?php $res = $mycon->query("SELECT p.amount, p.payment_method, p.created_at, CONCAT(g.first_name, ' ', g.last_name) as guest FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id WHERE p.payment_status = 'Refunded' ORDER BY p.created_at DESC LIMIT 20");
                        while($row = $res->fetch_assoc()): ?>
                        <tr><td><?php echo htmlspecialchars($row['guest']); ?></td><td>₱<?php echo number_format($row['amount'],2); ?></td><td><?php echo htmlspecialchars($row['payment_method']); ?></td><td><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></td></tr>
                        <?php endwhile; ?>
                        </tbody></table>
                </div>
            </div>
            <button class="btn btn-warning fw-bold mb-4 w-100" id="toggleHistoryBtn">Show Transaction History</button>
        </div>
    </div>
    <!-- Filter/Search Form UI -->
    <div id="transactionHistorySection" style="display:none;">
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

    // Toggle transaction history section
    $('#toggleHistoryBtn').on('click', function() {
        const section = $('#transactionHistorySection');
        if (section.is(':visible')) {
            section.slideUp(200);
            $(this).text('Show Transaction History');
        } else {
            section.slideDown(200);
            $(this).text('Hide Transaction History');
            loadPaymentsTable(currentSortBy, currentSortDir);
        }
    });

    // Card click scroll to table
    $('#card-transactions').on('click', function() {
        document.getElementById('transactions-table').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    $('#card-revenue').on('click', function() {
        document.getElementById('revenue-table').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    $('#card-today').on('click', function() {
        document.getElementById('today-table').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    $('#card-refunded').on('click', function() {
        document.getElementById('refunded-table').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
});
</script>
</body>
</html> 