<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
// Toast notification logic
$toast_message = '';
if (isset($_SESSION['admin_login_success'])) {
    $admin_display_name = '';
    if (!empty($_SESSION['full_name'])) {
        $admin_display_name = $_SESSION['full_name'];
    } elseif (!empty($_SESSION['first_name']) || !empty($_SESSION['last_name'])) {
        $admin_display_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    } else {
        $admin_display_name = 'Admin';
    }
    $toast_message = 'Welcome, ' . htmlspecialchars($admin_display_name) . '! You have successfully logged in.';
    unset($_SESSION['admin_login_success']);
}
if (isset($_SESSION['admin_logout_success'])) {
    $toast_message = 'You have successfully logged out.';
    unset($_SESSION['admin_logout_success']);
}
include '../functions/db_connect.php';
// Count new cancellation requests
$cancellation_count = 0;
$cancellation_sql = "SELECT COUNT(*) as cnt FROM tbl_reservation WHERE status = 'cancellation_requested'";
$cancellation_res = mysqli_query($mycon, $cancellation_sql);
if ($cancellation_res && $row = mysqli_fetch_assoc($cancellation_res)) {
    $cancellation_count = (int)$row['cnt'];
}
// Show toast if new requests since last visit
if (!isset($_SESSION['last_cancellation_count'])) {
    $_SESSION['last_cancellation_count'] = $cancellation_count;
}
$show_cancellation_toast = false;
if ($cancellation_count > $_SESSION['last_cancellation_count']) {
    $show_cancellation_toast = true;
    $_SESSION['last_cancellation_count'] = $cancellation_count;
}
// Latest 5 reservations
$recent_res_sql = "
    SELECT r.status, g.first_name, g.last_name, r.check_in
    FROM tbl_reservation r
    LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id
    ORDER BY r.date_created DESC
    LIMIT 5
";
$recent_res = mysqli_query($mycon, $recent_res_sql);
// Latest 5 cancellation requests
$cancellation_notif_sql = "
    SELECT r.reservation_id, g.first_name, g.last_name, r.check_in, r.status, r.date_created
    FROM tbl_reservation r
    LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id
    WHERE r.status = 'cancellation_requested'
    ORDER BY r.date_created DESC
    LIMIT 5
";
$cancellation_notifs = mysqli_query($mycon, $cancellation_notif_sql);
// Latest 5 new guest sign-ups
$new_guests_sql = "
    SELECT first_name, last_name, date_created
    FROM tbl_guest
    ORDER BY date_created DESC
    LIMIT 5
";
$new_guests = mysqli_query($mycon, $new_guests_sql);
// Latest 5 new admin sign-ups
$new_admins_sql = "
    SELECT username, full_name, date_created
    FROM tbl_admin
    ORDER BY date_created DESC
    LIMIT 5
";
$new_admins = mysqli_query($mycon, $new_admins_sql);
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
        .dashboard-container { margin-left: 240px; margin-top: 70px; padding: 40px 24px 24px 24px; }
        .dashboard-title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; }
        .summary-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 28px;
            margin-bottom: 40px;
            justify-content: flex-start;
        }
        .summary-card {
            background: #23234a;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 32px 24px;
            display: flex;
            align-items: center;
            gap: 18px;
            min-width: 220px;
            flex: 1 1 220px;
            max-width: 340px;
        }
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
        @media (max-width: 1200px) {
            .dashboard-container { margin-left: 70px; padding: 18px 4px; }
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
<?php if ($toast_message): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">
    <div id="adminToast" class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
        <div class="d-flex">
            <div class="toast-body">
                <?php echo $toast_message; ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastEl = document.getElementById('adminToast');
        if (toastEl) {
            var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }
    });
</script>
<?php endif; ?>
<div class="dashboard-container">
    <?php if (isset($_SESSION['msg'])): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100;">
        <div class="toast show align-items-center text-bg-<?php echo $_SESSION['msg_type'] ?? 'info'; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="d-flex">
                <div class="toast-body">
                    <?php echo $_SESSION['msg']; unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastEl = document.querySelector('.toast');
        if (toastEl) {
            var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
            toast.show();
        }
    });
    </script>
    <?php endif; ?>
    <div class="dashboard-title">Admin Dashboard</div>
    <?php
    // Fetch summary data
    $total_bookings = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation"))[0];
    $total_guests = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_guest"))[0];
    $total_rooms = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_room_type"))[0];
    $total_services = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_services"))[0];
    $available_rooms = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_room WHERE status = 'Available'"))[0];
    $occupied_rooms = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_room WHERE status = 'Occupied'"))[0];
    // New logic for total income
    $non_cash_income = mysqli_fetch_row(mysqli_query($mycon, "SELECT SUM(p.amount) FROM tbl_payment p WHERE p.payment_method != 'Cash' AND p.payment_status = 'Paid'"))[0];
    $cash_income = mysqli_fetch_row(mysqli_query($mycon, "SELECT SUM(p.amount) FROM tbl_payment p JOIN tbl_reservation r ON p.payment_id = r.payment_id WHERE p.payment_method = 'Cash' AND p.payment_status = 'Paid' AND r.status = 'completed'"))[0];
    $total_income = floatval($non_cash_income) + floatval($cash_income);
    if ($total_income === null) $total_income = 0;
    $pending_admin_approval = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'pending'"))[0];
    // --- New quick stats ---
    $todays_checkins = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE DATE(check_in) = CURDATE()"))[0];
    $todays_checkouts = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE DATE(check_out) = CURDATE()"))[0];
    $pending_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'pending'"))[0];
    $completed_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'completed'"))[0];
    $revenue_today = mysqli_fetch_row(mysqli_query($mycon, "SELECT SUM(amount) FROM tbl_payment WHERE payment_status = 'Paid' AND DATE(payment_created) = CURDATE()"))[0] ?? 0;
    $revenue_month = mysqli_fetch_row(mysqli_query($mycon, "SELECT SUM(amount) FROM tbl_payment WHERE payment_status = 'Paid' AND YEAR(payment_created) = YEAR(CURDATE()) AND MONTH(payment_created) = MONTH(CURDATE())"))[0] ?? 0;
    $cancelled_requests = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'cancelled'"))[0];
    $denied_requests = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'denied'"))[0];
    ?>
    <div class="mb-3 d-flex justify-content-end">
      <select id="cardFilter" class="form-select w-auto" style="min-width:180px;">
        <option value="all">Show All</option>
        <option value="reservations" selected>Reservations</option>
        <option value="revenue">Revenue</option>
        <option value="rooms">Rooms</option>
        <option value="requests">Requests</option>
      </select>
    </div>
    <div class="summary-cards">
        <div class="summary-card rooms clickable" id="availableRoomsCard">
            <span class="summary-icon"><i class="bi bi-door-open-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Available Rooms</span>
                <span class="summary-value" id="availableRoomsValue"><?php echo $available_rooms; ?></span>
            </div>
        </div>
        <div class="summary-card rooms clickable" id="occupiedRoomsCard">
            <span class="summary-icon"><i class="bi bi-door-closed-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Occupied Rooms</span>
                <span class="summary-value" id="occupiedRoomsValue"><?php echo $occupied_rooms; ?></span>
            </div>
        </div>
        <div class="summary-card reservations">
            <span class="summary-icon"><i class="bi bi-calendar2-check"></i></span>
            <div class="summary-info">
                <span class="summary-label">Today's Check-ins</span>
                <span class="summary-value"><?php echo $todays_checkins; ?></span>
            </div>
        </div>
        <div class="summary-card reservations">
            <span class="summary-icon"><i class="bi bi-calendar2-x"></i></span>
            <div class="summary-info">
                <span class="summary-label">Today's Check-outs</span>
                <span class="summary-value"><?php echo $todays_checkouts; ?></span>
            </div>
        </div>
        <div class="summary-card reservations">
            <span class="summary-icon"><i class="bi bi-hourglass-split"></i></span>
            <div class="summary-info">
                <span class="summary-label">Pending Reservations</span>
                <span class="summary-value"><?php echo $pending_reservations; ?></span>
            </div>
        </div>
        <div class="summary-card reservations">
            <span class="summary-icon"><i class="bi bi-check-circle"></i></span>
            <div class="summary-info">
                <span class="summary-label">Completed Reservations</span>
                <span class="summary-value"><?php echo $completed_reservations; ?></span>
            </div>
        </div>
        <div class="summary-card revenue">
            <span class="summary-icon"><i class="bi bi-cash"></i></span>
            <div class="summary-info">
                <span class="summary-label">Revenue Today</span>
                <span class="summary-value">₱<?php echo number_format($revenue_today, 2); ?></span>
            </div>
        </div>
        <div class="summary-card revenue">
            <span class="summary-icon"><i class="bi bi-cash-coin"></i></span>
            <div class="summary-info">
                <span class="summary-label">Revenue This Month</span>
                <span class="summary-value">₱<?php echo number_format($revenue_month, 2); ?></span>
            </div>
        </div>
        <div class="summary-card requests">
            <span class="summary-icon"><i class="bi bi-x-circle"></i></span>
            <div class="summary-info">
                <span class="summary-label">Cancelled Requests</span>
                <span class="summary-value"><?php echo $cancelled_requests; ?></span>
            </div>
        </div>
        <div class="summary-card requests">
            <span class="summary-icon"><i class="bi bi-slash-circle"></i></span>
            <div class="summary-info">
                <span class="summary-label">Denied Requests</span>
                <span class="summary-value"><?php echo $denied_requests; ?></span>
            </div>
        </div>
        <div class="summary-card reservations clickable" id="totalBookingsCard">
            <span class="summary-icon"><i class="bi bi-calendar2-check-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Bookings</span>
                <span class="summary-value" id="totalBookingsValue"><?php echo $total_bookings; ?></span>
            </div>
        </div>
        <div class="summary-card reservations clickable" id="totalGuestsCard">
            <span class="summary-icon"><i class="bi bi-people-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Guests</span>
                <span class="summary-value" id="totalGuestsValue"><?php echo $total_guests; ?></span>
            </div>
        </div>
        <div class="summary-card revenue clickable" id="totalIncomeCard">
            <span class="summary-icon"><i class="bi bi-cash-stack"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Income</span>
                <span class="summary-value" id="totalIncomeValue">₱<?php echo number_format($total_income, 2); ?></span>
            </div>
        </div>
    </div>
    <!-- Pending Cancellation Requests -->
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
    <!-- Pending Cash Payment Bookings -->
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
                    <th>Reference #</th>
                    <th>Payment Method</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $cash_sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, p.amount, p.reference_number, p.payment_method FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE p.payment_method = 'Cash' AND r.status = 'pending' ORDER BY r.date_created DESC";
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
                    echo '<td>' . htmlspecialchars($row['reference_number']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['payment_method']) . '</td>';
                    echo '<td>';
                    echo '<form method="POST" action="process_cash_approval.php" style="display:inline-block;">';
                    echo '<input type="hidden" name="reservation_id" value="' . $row['reservation_id'] . '">';
                    echo '<button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>';
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="9" class="text-center text-secondary">No pending cash payment bookings.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        </div>
    </div>
    <!-- Reservations Pending Admin Approval -->
    <div class="table-section mt-4">
        <div class="table-title">Reservations Pending Admin Approval</div>
        <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Payment Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $pending_sql = "SELECT r.reservation_id, r.check_in, r.check_out, g.first_name, g.last_name, rt.type_name, p.payment_status FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE r.status = 'pending' ORDER BY r.date_created DESC";
            $pending_res = mysqli_query($mycon, $pending_sql);
            if ($pending_res && mysqli_num_rows($pending_res) > 0) {
                while ($row = mysqli_fetch_assoc($pending_res)) {
                    echo '<tr>';
                    echo '<td>' . $row['reservation_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
                    echo '<td>' . htmlspecialchars($row['payment_status']) . '</td>';
                    echo '<td>';
                    echo '<form method="POST" action="process_update_status.php" style="display:inline-block;">';
                    echo '<input type="hidden" name="reservation_id" value="' . $row['reservation_id'] . '">';
                    echo '<input type="hidden" name="action" value="approve_reservation">';
                    echo '<button type="submit" class="btn btn-success btn-sm">Approve</button>';
                    echo '</form>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="7" class="text-center text-secondary">No reservations pending admin approval.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        </div>
    </div>
    <!-- Debug Output for Chart Data -->
    <div style="background:#222;color:#ffa533;padding:10px;margin-bottom:10px;font-size:1.1em;">
      <strong>DEBUG: Chart Data</strong><br>
      <pre id="debugChartData"></pre>
      <span id="chartjsStatus"></span>
    </div>
    <!-- Recent Activities Section -->
    <div class="table-section mb-4">
        <div class="table-title d-flex align-items-center">
            <i class="bi bi-clock-history me-2"></i> Recent Activities
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Guest</th>
                        <th>Check-in Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($recent_res && mysqli_num_rows($recent_res) > 0) {
                        while ($row = mysqli_fetch_assoc($recent_res)) {
                            echo '<tr>';
                            echo '<td><span class="badge bg-'.(
                                $row['status']=='approved'?'success':(
                                $row['status']=='cancelled'?'danger':(
                                $row['status']=='denied'?'warning text-dark':(
                                $row['status']=='completed'?'primary':(
                                $row['status']=='cancellation_requested'?'info text-dark':'secondary'))))).'">'.ucfirst($row['status']).'</span></td>';
                            echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                            echo '<td>' . date('M d, Y', strtotime($row['check_in'])) . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="3" class="text-center text-secondary">No recent reservations.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Modals for summary details -->
    <div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark text-light rounded-4 shadow-lg border-0">
          <div class="modal-header border-0 pb-0 justify-content-center bg-transparent">
            <h4 class="modal-title w-100 text-center fw-bold text-warning" id="summaryModalLabel">Summary Details</h4>
            <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="summaryModalBody">
            <!-- Table will be injected here -->
          </div>
        </div>
      </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function showModal(title, tableHtml) {
        document.getElementById('summaryModalLabel').textContent = title;
        document.getElementById('summaryModalBody').innerHTML = tableHtml;
        var modal = new bootstrap.Modal(document.getElementById('summaryModal'));
        modal.show();
    }
    function fetchSummary(type, title) {
        document.getElementById('summaryModalLabel').textContent = title;
        document.getElementById('summaryModalBody').innerHTML = '<div class="text-center py-5"><div class="spinner-border text-warning" role="status"></div></div>';
        var modal = new bootstrap.Modal(document.getElementById('summaryModal'));
        modal.show();
        fetch('dashboard_details.php?type=' + encodeURIComponent(type))
            .then(response => response.text())
            .then(html => {
                document.getElementById('summaryModalBody').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('summaryModalBody').innerHTML = '<div class="text-danger text-center">Failed to load data.</div>';
            });
    }
    document.getElementById('availableRoomsCard').onclick = function() {
        fetchSummary('available_rooms', 'Available Rooms');
    };
    document.getElementById('occupiedRoomsCard').onclick = function() {
        fetchSummary('occupied_rooms', 'Occupied Rooms');
    };
    document.getElementById('totalBookingsCard').onclick = function() {
        fetchSummary('total_bookings', 'All Bookings');
    };
    document.getElementById('totalGuestsCard').onclick = function() {
        fetchSummary('total_guests', 'All Guests');
    };
    document.getElementById('totalIncomeCard').onclick = function() {
        fetchSummary('total_income', 'All Payments');
    };
    document.getElementById('cardFilter').addEventListener('change', function() {
        var value = this.value;
        var cards = document.querySelectorAll('.summary-card');
        if (value === 'all') {
            cards.forEach(card => card.style.display = '');
        } else {
            cards.forEach(card => {
                if (card.classList.contains(value)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    });
    // Trigger filter on page load to apply default
    window.addEventListener('DOMContentLoaded', function() {
        document.getElementById('cardFilter').dispatchEvent(new Event('change'));
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sample PHP data for charts (replace with real queries)
const bookingsData = <?php
  $labels = [];
  $data = [];
  for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('D', strtotime($date));
    $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE DATE(date_created) = '$date'"))[0];
    $data[] = (int)$count;
  }
  echo json_encode(['labels' => $labels, 'data' => $data]);
?>;
const roomTypesData = <?php
  $labels = [];
  $data = [];
  $colors = [];
  $res = mysqli_query($mycon, "SELECT rt.type_name, COUNT(*) as cnt FROM tbl_reservation r LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id GROUP BY rt.type_name");
  while ($row = mysqli_fetch_assoc($res)) {
    $labels[] = $row['type_name'];
    $data[] = (int)$row['cnt'];
    $colors[] = '#' . substr(md5($row['type_name']), 0, 6);
  }
  echo json_encode(['labels' => $labels, 'data' => $data, 'colors' => $colors]);
?>;
const revenueData = <?php
  $labels = [];
  $data = [];
  for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('D', strtotime($date));
    $sum = mysqli_fetch_row(mysqli_query($mycon, "SELECT SUM(amount) FROM tbl_payment WHERE DATE(date_created) = '$date'"))[0];
    $data[] = $sum ? (float)$sum : 0;
  }
  echo json_encode(['labels' => $labels, 'data' => $data]);
?>;
// Bookings Line Chart
(function() {
  var debugDiv = document.getElementById('bookingsChartDebug');
  var canvas = document.getElementById('bookingsLast7Chart');
  var chartStatus = (typeof Chart !== 'undefined') ? 'Chart.js is loaded.' : 'Chart.js is NOT loaded!';
  var bookingsDataStr = '';
  try {
    bookingsDataStr = JSON.stringify(bookingsData, null, 2);
  } catch (e) {
    bookingsDataStr = 'Error stringifying bookingsData: ' + e;
  }
  debugDiv.innerHTML = '<b>bookingsData:</b><pre style="color:#ffa533;white-space:pre-wrap;">' + bookingsDataStr + '</pre>' +
    '<b>Chart.js status:</b> ' + chartStatus + '<br>' +
    '<b>Canvas present:</b> ' + (canvas ? 'YES' : 'NO');
  if (!canvas) return;
  if (typeof Chart === 'undefined') {
    document.getElementById('bookingsChartFallback').style.display = 'flex';
    document.getElementById('bookingsChartFallback').textContent = 'Chart.js is NOT loaded!';
    return;
  }
  try {
    new Chart(canvas, {
      type: 'line',
      data: {
        labels: bookingsData.labels,
        datasets: [{
          label: 'Bookings',
          data: bookingsData.data,
          borderColor: '#ffa533',
          backgroundColor: 'rgba(255,165,51,0.15)',
          tension: 0.4,
          fill: true,
          pointRadius: 7,
          pointBackgroundColor: '#ffa533',
          pointBorderColor: '#fff',
          pointHoverRadius: 10
        }]
      },
      options: {
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#232323',
            titleColor: '#ffa533',
            bodyColor: '#fff',
            borderColor: '#ffa533',
            borderWidth: 2,
            cornerRadius: 12,
            padding: 16,
            titleFont: { weight: '700', size: 20 },
            bodyFont: { size: 18 }
          }
        },
        scales: {
          y: { beginAtZero: true, ticks: { color: '#fff', font: { size: 18 } } },
          x: { ticks: { color: '#fff', font: { size: 18 } } }
        }
      }
    });
  } catch (err) {
    document.getElementById('bookingsChartFallback').style.display = 'flex';
    document.getElementById('bookingsChartFallback').textContent = 'Chart error: ' + err;
    debugDiv.innerHTML += '<br><b style="color:#ff4d4d;">Chart error:</b> ' + err;
  }
})();
// Room Types Pie Chart
new Chart(document.getElementById('roomTypesBookedChart'), {
  type: 'pie',
  data: {
    labels: roomTypesData.labels,
    datasets: [{
      data: roomTypesData.data,
      backgroundColor: roomTypesData.colors,
      borderColor: '#23234a',
      borderWidth: 3
    }]
  },
  options: {
    plugins: {
      legend: { labels: { color: '#fff', font: { weight: 'bold', size: 20 } } },
      tooltip: {
        backgroundColor: '#232323',
        titleColor: '#ffa533',
        bodyColor: '#fff',
        borderColor: '#ffa533',
        borderWidth: 2,
        cornerRadius: 12,
        padding: 16,
        titleFont: { weight: '700', size: 20 },
        bodyFont: { size: 18 }
      }
    }
  }
});
// Revenue Bar Chart
new Chart(document.getElementById('revenueLast7Chart'), {
  type: 'bar',
  data: {
    labels: revenueData.labels,
    datasets: [{
      label: 'Revenue (₱)',
      data: revenueData.data,
      backgroundColor: '#ffa533',
      borderRadius: 12
    }]
  },
  options: {
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#232323',
        titleColor: '#ffa533',
        bodyColor: '#fff',
        borderColor: '#ffa533',
        borderWidth: 2,
        cornerRadius: 12,
        padding: 16,
        titleFont: { weight: '700', size: 20 },
        bodyFont: { size: 18 }
      }
    },
    scales: {
      y: { beginAtZero: true, ticks: { color: '#fff', font: { size: 18 } } },
      x: { ticks: { color: '#fff', font: { size: 18 } } }
    }
  }
});
// Debug: Print chart data variables
window.addEventListener('DOMContentLoaded', function() {
  let debug = '';
  debug += 'bookingsData: ' + JSON.stringify(bookingsData, null, 2) + '\n';
  debug += 'roomTypesData: ' + JSON.stringify(roomTypesData, null, 2) + '\n';
  debug += 'revenueData: ' + JSON.stringify(revenueData, null, 2) + '\n';
  document.getElementById('debugChartData').textContent = debug;
  // Check if Chart.js is loaded
  document.getElementById('chartjsStatus').textContent = (typeof Chart !== 'undefined') ? 'Chart.js is loaded.' : 'Chart.js is NOT loaded!';
});
// Bookings Last 7 Days Chart
const bookingsLast7Data = <?php
  $labels = [];
  $data = [];
  for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('D', strtotime($date));
    $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE DATE(date_created) = '$date'"))[0];
    $data[] = (int)$count;
  }
  echo json_encode(['labels' => $labels, 'data' => $data]);
?>;
new Chart(document.getElementById('bookingsLast7Chart'), {
  type: 'line',
  data: {
    labels: bookingsLast7Data.labels,
    datasets: [{
      label: 'Bookings',
      data: bookingsLast7Data.data,
      borderColor: '#ffa533',
      backgroundColor: 'rgba(255,165,51,0.15)',
      tension: 0.4,
      fill: true,
      pointRadius: 5,
      pointBackgroundColor: '#ffa533',
      pointBorderColor: '#fff',
      pointHoverRadius: 8
    }]
  },
  options: {
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#232323',
        titleColor: '#ffa533',
        bodyColor: '#fff',
        borderColor: '#ffa533',
        borderWidth: 1.5,
        cornerRadius: 10,
        padding: 12,
        titleFont: { weight: '700', size: 15 },
        bodyFont: { size: 14 }
      }
    },
    scales: {
      y: { beginAtZero: true, ticks: { color: '#fff', font: { size: 14 } } },
      x: { ticks: { color: '#fff', font: { size: 14 } } }
    },
    layout: { padding: 8 },
    animation: { duration: 900, easing: 'easeOutQuart' },
    responsive: true,
    maintainAspectRatio: false,
    backgroundColor: '#181818'
  }
});
</script>
<script>
function updateRoomCounts() {
    fetch('dashboard_details.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('availableRoomsCount').textContent = data.available;
            document.getElementById('occupiedRoomsCount').textContent = data.occupied;
        });
}
setInterval(updateRoomCounts, 5000); // Update every 5 seconds
document.addEventListener('DOMContentLoaded', updateRoomCounts);
</script>
</body>
</html> 