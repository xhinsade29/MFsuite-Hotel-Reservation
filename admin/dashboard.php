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
    ?>
    <div class="summary-cards">
        <div class="summary-card clickable" id="availableRoomsCard">
            <span class="summary-icon"><i class="bi bi-door-open-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Available Rooms</span>
                <span class="summary-value" id="availableRoomsValue"><?php echo $available_rooms; ?></span>
            </div>
        </div>
        <div class="summary-card clickable" id="occupiedRoomsCard">
            <span class="summary-icon"><i class="bi bi-door-closed-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Occupied Rooms</span>
                <span class="summary-value" id="occupiedRoomsValue"><?php echo $occupied_rooms; ?></span>
            </div>
        </div>
        <div class="summary-card clickable" id="totalBookingsCard">
            <span class="summary-icon"><i class="bi bi-calendar2-check-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Bookings</span>
                <span class="summary-value" id="totalBookingsValue"><?php echo $total_bookings; ?></span>
            </div>
        </div>
        <div class="summary-card clickable" id="totalGuestsCard">
            <span class="summary-icon"><i class="bi bi-people-fill"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Guests</span>
                <span class="summary-value" id="totalGuestsValue"><?php echo $total_guests; ?></span>
            </div>
        </div>
        <div class="summary-card clickable" id="totalIncomeCard">
            <span class="summary-icon"><i class="bi bi-cash-stack"></i></span>
            <div class="summary-info">
                <span class="summary-label">Total Income</span>
                <span class="summary-value" id="totalIncomeValue">₱<?php echo number_format($total_income, 2); ?></span>
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
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 