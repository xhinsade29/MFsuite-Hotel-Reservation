<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
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
        .dashboard-container { margin-left: 240px; padding: 40px 24px 24px 24px; }
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
    <!-- Notifications Section -->
    <div class="table-section mb-4">
        <div class="table-title d-flex align-items-center">
            <i class="bi bi-bell me-2"></i> Notifications
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-dark-subtle">
                    <h6 class="fw-bold text-warning mb-3"><i class="bi bi-x-octagon me-1"></i> Recent Cancellation Requests</h6>
                    <ul class="list-group list-group-flush">
                        <?php
                        if ($cancellation_notifs && mysqli_num_rows($cancellation_notifs) > 0) {
                            while ($row = mysqli_fetch_assoc($cancellation_notifs)) {
                                echo '<li class="list-group-item bg-transparent text-light">';
                                echo '<span class="badge bg-danger me-2">Cancellation</span>';
                                echo '<b>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</b> requested cancellation for <b>#' . $row['reservation_id'] . '</b> (' . date('M d, Y', strtotime($row['check_in'])) . ')';
                                echo '<span class="text-muted float-end" style="font-size:0.9em">' . date('M d, Y h:i A', strtotime($row['date_created'])) . '</span>';
                                echo '</li>';
                            }
                        } else {
                            echo '<li class="list-group-item bg-transparent text-secondary">No recent cancellation requests.</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-dark-subtle">
                    <h6 class="fw-bold text-warning mb-3"><i class="bi bi-person-plus me-1"></i> Recent Sign-ups</h6>
                    <ul class="list-group list-group-flush mb-2">
                        <?php
                        if ($new_guests && mysqli_num_rows($new_guests) > 0) {
                            while ($row = mysqli_fetch_assoc($new_guests)) {
                                echo '<li class="list-group-item bg-transparent text-light">';
                                echo '<span class="badge bg-info me-2">Guest</span>';
                                echo '<b>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</b> signed up';
                                echo '<span class="text-muted float-end" style="font-size:0.9em">' . date('M d, Y h:i A', strtotime($row['date_created'])) . '</span>';
                                echo '</li>';
                            }
                        }
                        if ($new_admins && mysqli_num_rows($new_admins) > 0) {
                            while ($row = mysqli_fetch_assoc($new_admins)) {
                                echo '<li class="list-group-item bg-transparent text-light">';
                                echo '<span class="badge bg-primary me-2">Admin</span>';
                                echo '<b>' . htmlspecialchars($row['full_name'] ?: $row['username']) . '</b> registered as admin';
                                echo '<span class="text-muted float-end" style="font-size:0.9em">' . date('M d, Y h:i A', strtotime($row['date_created'])) . '</span>';
                                echo '</li>';
                            }
                        }
                        if ((!$new_guests || mysqli_num_rows($new_guests) == 0) && (!$new_admins || mysqli_num_rows($new_admins) == 0)) {
                            echo '<li class="list-group-item bg-transparent text-secondary">No recent sign-ups.</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>
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
</body>
</html> 