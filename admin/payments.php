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
        }
        .modern-table tbody tr {
            background: transparent !important;
        }
        .modern-table tbody tr:hover {
            background: rgba(255,140,0,0.07) !important;
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
            <input type="date" class="form-control" id="filter_date_to" name="date_to">
        </div>
        <div class="col-md-3">
            <label for="filter_search" class="form-label">Search (Guest or Reservation ID)</label>
            <input type="text" class="form-control" id="filter_search" name="search" placeholder="Guest name or Reservation ID">
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-primary">Filter</button>
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
                    <th>Reservation ID</th>
                    <th>Guest Name</th>
                    <th>Payment Method</th>
                    <th>Amount Paid</th>
                    <th>Payment Status</th>
                    <th>Date of Payment</th>
                    <th>Options</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT p.amount, p.payment_method, p.payment_status, p.reference_number, p.created_at, r.reservation_id, CONCAT(g.first_name, ' ', IFNULL(g.middle_name, ''), ' ', g.last_name) AS guest_name FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id ORDER BY p.created_at DESC";
            $res = $mycon->query($sql);
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($row['reservation_id'] ?: '-') . '</td>';
                    echo '<td>' . htmlspecialchars(trim($row['guest_name']) ?: '-') . '</td>';
                    echo '<td>' . htmlspecialchars($row['payment_method']) . '</td>';
                    echo '<td>₱' . number_format($row['amount'], 2) . '</td>';
                    echo '<td>' . htmlspecialchars($row['payment_status']) . '</td>';
                    echo '<td>' . date('M d, Y H:i', strtotime($row['created_at'])) . '</td>';
                    echo '<td>';
                    echo '<button class="btn btn-sm btn-outline-info me-1">View Receipt</button>';
                    echo '<button class="btn btn-sm btn-outline-secondary">Re-send Confirmation</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7" class="text-center text-secondary">No payments found.</td></tr>';
            }
            ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html> 