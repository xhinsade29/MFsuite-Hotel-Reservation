<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        .dashboard-container { margin-left: 240px; padding: 40px 24px 24px 24px; }
        .dashboard-title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 28px; margin-bottom: 40px; }
        .summary-card { background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 32px 24px; display: flex; align-items: center; gap: 18px; }
        .summary-icon { font-size: 2.2rem; color: #FF8C00; background: rgba(255,140,0,0.08); border-radius: 12px; padding: 16px; }
        .summary-info { display: flex; flex-direction: column; }
        .summary-label { color: #bdbdbd; font-size: 1.1em; }
        .summary-value { color: #ffa533; font-size: 2rem; font-weight: 700; }
        .table-section { background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 32px 18px; }
        .table-title { color: #ffa533; font-size: 1.3rem; font-weight: 600; margin-bottom: 18px; }
        .table thead { background: #1a1a2e; color: #ffa533; }
        .table tbody tr { color: #fff; }
        .table tbody tr:nth-child(even) { background: #23234a; }
        .table td, .table th { vertical-align: middle; }
        @media (max-width: 900px) { .dashboard-container { margin-left: 70px; padding: 18px 4px; } .summary-cards { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="dashboard-container">
    <div class="dashboard-title">Admin Dashboard</div>
    <?php
    // Fetch summary data
    $total_bookings = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation"))[0];
    $total_guests = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_guest"))[0];
    $total_rooms = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_room_type"))[0];
    $total_services = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_services"))[0];
    // New logic for total income
    $non_cash_income = mysqli_fetch_row(mysqli_query($mycon, "SELECT SUM(p.amount) FROM tbl_payment p WHERE p.payment_method != 'Cash' AND p.payment_status = 'Paid'"))[0];
    $cash_income = mysqli_fetch_row(mysqli_query($mycon, "SELECT SUM(p.amount) FROM tbl_payment p JOIN tbl_reservation r ON p.payment_id = r.payment_id WHERE p.payment_method = 'Cash' AND p.payment_status = 'Paid' AND r.status = 'completed'"))[0];
    $total_income = floatval($non_cash_income) + floatval($cash_income);
    if ($total_income === null) $total_income = 0;
    ?>
    <div class="summary-cards">
        <div class="summary-card">
            <span class="summary-icon"><i class="bi bi-calendar2-check-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Bookings</span>
                <span class="summary-value"><?php echo $total_bookings; ?></span>
            </div>
        </div>
        <div class="summary-card">
            <span class="summary-icon"><i class="bi bi-people-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Guests</span>
                <span class="summary-value"><?php echo $total_guests; ?></span>
            </div>
        </div>
        <div class="summary-card">
            <span class="summary-icon"><i class="bi bi-door-closed-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Rooms</span>
                <span class="summary-value"><?php echo $total_rooms; ?></span>
            </div>
        </div>
        <div class="summary-card">
            <span class="summary-icon"><i class="bi bi-cup-hot"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Services</span>
                <span class="summary-value"><?php echo $total_services; ?></span>
            </div>
        </div>
        <div class="summary-card">
            <span class="summary-icon"><i class="bi bi-cash-stack"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Income</span>
                <span class="summary-value">₱<?php echo number_format($total_income, 2); ?></span>
            </div>
        </div>
    </div>
    <div class="table-section mt-4">
        <div class="table-title">Pending Cancellation Requests</div>
        <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Guest</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $cancel_sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, cr.reason_text FROM tbl_reservation r JOIN cancelled_reservation c ON r.reservation_id = c.reservation_id JOIN tbl_guest g ON r.guest_id = g.guest_id JOIN tbl_cancellation_reason cr ON c.reason_id = cr.reason_id WHERE r.status = 'cancellation_requested' ORDER BY r.date_created DESC";
            $cancel_res = mysqli_query($mycon, $cancel_sql);
            if ($cancel_res && mysqli_num_rows($cancel_res) > 0) {
                while ($row = mysqli_fetch_assoc($cancel_res)) {
                    echo '<tr>';
                    echo '<td>' . $row['reservation_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
                    echo '<td>' . htmlspecialchars($row['reason_text']) . '</td>';
                    echo '<td>';
                    echo '<form method="POST" action="process_cancellation.php" style="display:inline-block;">';
                    echo '<input type="hidden" name="reservation_id" value="' . $row['reservation_id'] . '">';
                    echo '<button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>';
                    echo '</form> ';
                    echo '<form method="POST" action="process_cancellation.php" style="display:inline-block;">';
                    echo '<input type="hidden" name="reservation_id" value="' . $row['reservation_id'] . '">';
                    echo '<button type="submit" name="action" value="deny" class="btn btn-danger btn-sm">Deny</button>';
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6" class="text-center text-secondary">No pending cancellation requests.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        </div>
    </div>
    <div class="table-section mt-4">
        <div class="table-title">Pending Cash Payment Bookings</div>
        <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $cash_sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, p.amount FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE p.payment_method = 'Cash' AND r.status = 'pending' ORDER BY r.date_created DESC";
            $cash_res = mysqli_query($mycon, $cash_sql);
            if ($cash_res && mysqli_num_rows($cash_res) > 0) {
                while ($row = mysqli_fetch_assoc($cash_res)) {
                    echo '<tr>';
                    echo '<td>' . $row['reservation_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
                    echo '<td>₱' . number_format($row['amount'], 2) . '</td>';
                    echo '<td>';
                    echo '<form method="POST" action="process_cash_approval.php" style="display:inline-block;">';
                    echo '<input type="hidden" name="reservation_id" value="' . $row['reservation_id'] . '">';
                    echo '<button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>';
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7" class="text-center text-secondary">No pending cash payment bookings.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</body>
</html> 