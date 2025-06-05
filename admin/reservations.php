<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
require_once '../functions/payment_helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reservations - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        .reservations-container { margin-left: 240px; margin-top: 70px; padding: 40px 24px 24px 24px; }
        .reservations-title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; }
        .table-section { background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 32px 18px; }
        .table-title { color: #ffa533; font-size: 1.3rem; font-weight: 600; margin-bottom: 18px; }
        .table thead { background: #1a1a2e; color: #ffa533; }
        .table tbody tr { color: #fff; }
        .table tbody tr:nth-child(even) { background: #23234a; }
        .table td, .table th { vertical-align: middle; }
        @media (max-width: 900px) { .reservations-container { margin-left: 70px; padding: 18px 4px; } }
        /* Center modal vertically and style for dark theme */
        .modal-dialog {
            display: flex;
            align-items: center;
            min-height: calc(100vh - 1rem);
        }
        .modal-content {
            background: #23234a;
            color: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
        }
        .modal-title {
            color: #ffa533;
            font-weight: 600;
        }
        .modal-footer .btn {
            min-width: 100px;
        }
        .modal-body {
            text-align: center;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="reservations-container">
    <div class="reservations-title">All Reservations</div>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success text-center mb-3"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>

    <!-- Collapsible: Active Reservations (default open) -->
    <div class="table-section mb-4">
        <div class="table-title d-flex justify-content-between align-items-center">
            <span>Active Reservations</span>
            <button class="btn btn-link text-warning p-0" type="button" data-bs-toggle="collapse" data-bs-target="#activeReservationsTable" aria-expanded="true" aria-controls="activeReservationsTable">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse show" id="activeReservationsTable">
        <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle table-sm">
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Room Number</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="reservationsTableBody">
            <?php
            $active_sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, rm.room_number FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id WHERE r.status IN ('pending','approved') ORDER BY r.date_created DESC";
            $active_res = mysqli_query($mycon, $active_sql);
            if ($active_res && mysqli_num_rows($active_res) > 0) {
                while ($row = mysqli_fetch_assoc($active_res)) {
                    echo '<tr>';
                    echo '<td>' . $row['reservation_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['room_number']) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
                    $status = $row['status'];
                    echo '<td><span class="badge bg-'.(
                        $status==='approved'?'success':(
                        $status==='cancelled'?'danger':(
                        $status==='denied'?'warning text-dark':(
                        $status==='completed'?'primary':(
                        $status==='cancellation_requested'?'info text-dark':'secondary'))))).'">'.ucfirst($status).'</span></td>';
                    echo '<td>';
                    if ($status === 'approved') {
                        $can_complete = strtotime($row['check_out']) < time();
                        $btn_class = $can_complete ? 'btn-primary' : 'btn-secondary';
                        $checkout_time = date('M d, Y h:i A', strtotime($row['check_out']));
                        $checkout_timestamp = strtotime($row['check_out']);
                        // Debugging: Check the output of strtotime
                        error_log('[DEBUG_PHP] strtotime output for reservation ' . $row['reservation_id'] . ': ' . print_r($checkout_timestamp, true));
                        // Ensure a valid timestamp is outputted, default to 0 if strtotime fails
                        $checkout_timestamp_output = ($checkout_timestamp !== false && $checkout_timestamp !== null && is_numeric($checkout_timestamp)) ? $checkout_timestamp : 0;
                        error_log('[DEBUG_PHP] Output timestamp for reservation ' . $row['reservation_id'] . ': ' . $checkout_timestamp_output);
                        echo '<button class="btn ' . $btn_class . ' btn-sm mark-complete-btn" data-reservation-id="' . $row['reservation_id'] . '" data-checkout-time="' . htmlspecialchars($checkout_time) . '" data-checkout-timestamp="' . $checkout_timestamp_output . '">Mark as Completed</button>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="8" class="text-center text-secondary">No active reservations.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>

    <!-- Collapsible: Pending Cancellation Requests -->
    <div class="table-section mb-4">
        <div class="table-title d-flex justify-content-between align-items-center">
            <span>Pending Cancellation Requests</span>
            <button class="btn btn-link text-warning p-0" type="button" data-bs-toggle="collapse" data-bs-target="#pendingCancellationTable" aria-expanded="false" aria-controls="pendingCancellationTable">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse" id="pendingCancellationTable">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle table-sm">
                    <thead>
                        <tr>
                            <th>Reservation ID</th>
                            <th>Guest</th>
                            <th>Room Type</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Requested On</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="cancellationRequestsTableBody">
                        <?php
                        $cancellation_sql = "SELECT r.reservation_id, r.check_in, r.check_out, g.first_name, g.last_name, rt.type_name, r.date_created as date_canceled, r.status FROM tbl_reservation r JOIN tbl_guest g ON r.guest_id = g.guest_id JOIN tbl_room rm ON r.room_id = rm.room_id JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id WHERE r.status IN ('cancellation_requested', 'cancelled') ORDER BY r.date_created DESC";
                        $cancellation_res = mysqli_query($mycon, $cancellation_sql);

                        if ($cancellation_res && mysqli_num_rows($cancellation_res) > 0) {
                            while ($row = mysqli_fetch_assoc($cancellation_res)) {
                                echo '<tr>';
                                echo '<td>' . $row['reservation_id'] . '</td>';
                                echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                                echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
                                echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
                                echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
                                echo '<td>' . date('M d, Y h:i A', strtotime($row['date_canceled'])) . '</td>';
                                echo '<td>';
                                // Approve Cancellation Button
                                echo '<button class="btn btn-success btn-sm approve-cancellation-btn" data-reservation-id="' . $row['reservation_id'] . '">Approve Cancellation</button>';
                                // Add Deny Cancellation button if needed
                                echo '<button class="btn btn-danger btn-sm deny-cancellation-btn" data-reservation-id="' . $row['reservation_id'] . '">Deny Cancellation</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="7" class="text-center text-secondary">No pending cancellation requests.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Reservation List (with filters and sorting) -->
    <div class="table-section mb-4">
        <div class="table-title d-flex justify-content-between align-items-center">
            <span>Reservation List</span>
            <button class="btn btn-link text-warning p-0" type="button" data-bs-toggle="collapse" data-bs-target="#reservationListTable" aria-expanded="false" aria-controls="reservationListTable">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse" id="reservationListTable">
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" name="guest" class="form-control" placeholder="Filter by Guest Name" value="<?php echo htmlspecialchars($_GET['guest'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <select name="room_type" class="form-select">
                    <option value="">All Room Types</option>
                    <?php
                    $rtypes = mysqli_query($mycon, "SELECT room_type_id, type_name FROM tbl_room_type");
                    while ($rt = mysqli_fetch_assoc($rtypes)) {
                        $selected = (isset($_GET['room_type']) && $_GET['room_type'] == $rt['room_type_id']) ? 'selected' : '';
                        echo '<option value="' . $rt['room_type_id'] . '" ' . $selected . '>' . htmlspecialchars($rt['type_name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-12 text-end">
                <label for="list_sort" class="form-label me-2">Sort by:</label>
                <select name="list_sort" id="list_sort" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                    <option value="date_desc" <?php if (($list_sort ?? '') == 'date_desc') echo 'selected'; ?>>Newest</option>
                    <option value="date_asc" <?php if (($list_sort ?? '') == 'date_asc') echo 'selected'; ?>>Oldest</option>
                    <option value="guest" <?php if (($list_sort ?? '') == 'guest') echo 'selected'; ?>>Guest Name</option>
                    <option value="room" <?php if (($list_sort ?? '') == 'room') echo 'selected'; ?>>Room Type</option>
                </select>
                <button type="submit" class="btn btn-warning ms-2">Filter</button>
                <a href="reservations.php" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
        <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
                    <th>Reference #</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $where = [];
            if (!empty($_GET['guest'])) {
                $guest = mysqli_real_escape_string($mycon, $_GET['guest']);
                $where[] = "(g.first_name LIKE '%$guest%' OR g.last_name LIKE '%$guest%')";
            }
            if (!empty($_GET['room_type'])) {
                $rtype = intval($_GET['room_type']);
                $where[] = "rt.room_type_id = $rtype";
            }
            $list_sort = $_GET['list_sort'] ?? 'date_desc';
            $list_order = 'ORDER BY r.date_created DESC';
            if ($list_sort === 'date_asc') $list_order = 'ORDER BY r.date_created ASC';
            if ($list_sort === 'guest') $list_order = 'ORDER BY g.last_name ASC, g.first_name ASC';
            if ($list_sort === 'room') $list_order = 'ORDER BY rt.type_name ASC';
            $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
            $sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, p.amount, p.payment_method, p.payment_status, p.reference_number FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id $where_sql $list_order";
            $res = mysqli_query($mycon, $sql);
            if ($res && mysqli_num_rows($res) > 0) {
                while ($row = mysqli_fetch_assoc($res)) {
                    echo '<tr>';
                    echo '<td>' . $row['reservation_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
                    $status = $row['status'];
                    echo '<td><span class="badge bg-'.(
                        $status==='approved'?'success':(
                        $status==='cancelled'?'danger':(
                        $status==='denied'?'warning text-dark':(
                        $status==='completed'?'primary':(
                        $status==='cancellation_requested'?'info text-dark':'secondary'))))).'">'.ucfirst($status).'</span></td>';
                    echo '<td>' . (isset($row['amount']) ? '₱' . number_format($row['amount'], 2) : '-') . '</td>';
                    echo '<td>' . htmlspecialchars($row['payment_method'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($row['payment_status'] ?? '-') . '</td>';
                    echo '<td>' . htmlspecialchars($row['reference_number'] ?? '-') . '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="10" class="text-center text-secondary">No reservations found.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>

    <!-- Pending Cancellation Requests, Pending Cash Payment Bookings, and Reservations Pending Admin Approval sections removed (now on dashboard) -->
</div>
<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="approveForm" method="POST" action="process_update_status.php">
      <input type="hidden" name="reservation_id" id="approveReservationId">
      <input type="hidden" name="action" value="approve_reservation">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="approveModalLabel">Approve Reservation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to approve this reservation?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Approve</button>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- Deny Modal -->
<div class="modal fade" id="denyModal" tabindex="-1" aria-labelledby="denyModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="denyForm" method="POST" action="process_update_status.php">
      <input type="hidden" name="reservation_id" id="denyReservationId">
      <input type="hidden" name="new_status" value="denied">
      <input type="hidden" name="action" value="deny">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="denyModalLabel">Deny Reservation</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to deny this reservation?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Deny</button>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- Complete Modal -->
<div class="modal fade" id="completeModal" tabindex="-1" aria-labelledby="completeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="completeForm" method="POST" action="process_update_status.php">
      <input type="hidden" name="reservation_id" id="completeReservationId">
      <input type="hidden" name="action" value="complete">
      <input type="hidden" name="new_status" value="completed">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="completeModalLabel">Mark as Completed</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to mark this reservation as completed?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Complete</button>
        </div>
      </div>
    </form>
  </div>
</div>
<script>
function showApproveModal(reservationId) {
    document.getElementById('approveReservationId').value = reservationId;
    var approveModal = new bootstrap.Modal(document.getElementById('approveModal'));
    approveModal.show();
}
function showDenyModal(reservationId) {
    document.getElementById('denyReservationId').value = reservationId;
    var denyModal = new bootstrap.Modal(document.getElementById('denyModal'));
    denyModal.show();
}
function showCompleteModal(reservationId) {
    document.getElementById('completeReservationId').value = reservationId;
    var completeModal = new bootstrap.Modal(document.getElementById('completeModal'));
    completeModal.show();
}
function refreshReservationsTable() {
    fetch('ajax_reservations_table.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('reservationsTableBody').innerHTML = html;
        });
}
setInterval(refreshReservationsTable, 10000);
document.addEventListener('DOMContentLoaded', refreshReservationsTable);
function refreshCancellationRequestsTable() {
    fetch('ajax_cancellation_requests_table.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('cancellationRequestsTableBody').innerHTML = html;
        });
}
setInterval(refreshCancellationRequestsTable, 10000);
document.addEventListener('DOMContentLoaded', refreshCancellationRequestsTable);
function fetchRoomList(roomTypeId) {
    fetch('ajax_room_status_table.php?room_type_id=' + roomTypeId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('roomListTable' + roomTypeId).innerHTML = html;
            attachRoomEditListeners();
            attachRoomRowFormListeners();
        });
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Approve Payment (Cash)
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('approve-payment-btn')) {
            const reservationId = e.target.getAttribute('data-reservation-id');
            if (confirm('Mark payment as received for this cash booking?')) {
                fetch('process_update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `reservation_id=${reservationId}&action=approve_payment`
                })
                .then(res => res.ok ? location.reload() : alert('Failed to approve payment.'));
            }
        }
    // Approve Reservation (assign room)
        if (e.target.classList.contains('approve-reservation-btn')) {
            const reservationId = e.target.getAttribute('data-reservation-id');
            if (confirm('Approve reservation and assign a room?')) {
                fetch('process_update_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `reservation_id=${reservationId}&action=approve_reservation`
                })
                .then(res => res.ok ? location.reload() : alert('Failed to approve reservation.'));
            }
        }
    // Cancel Reservation (Cash)
        if (e.target.classList.contains('cancel-reservation-btn')) {
            const reservationId = e.target.getAttribute('data-reservation-id');
            if (confirm('Cancel this reservation?')) {
                fetch('process_cancellation.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `reservation_id=${reservationId}&action=approve`
                })
                .then(res => res.ok ? location.reload() : alert('Failed to cancel reservation.'));
            }
        }
    // Mark as Completed button logic
    if (e.target.classList.contains('mark-complete-btn')) {
        var reservationId = e.target.getAttribute('data-reservation-id');
        var checkoutTime = e.target.getAttribute('data-checkout-time');
        var checkoutTimestamp = parseInt(e.target.getAttribute('data-checkout-timestamp'), 10);
        var now = Math.floor(Date.now() / 1000);
        if (now < checkoutTimestamp) {
            // Checkout not finished
            showToast('Unavailable to mark as complete. The guest has not checked out yet.<br>Check-out: <b>' + checkoutTime + '</b>');
            return;
        } else {
            // Checkout finished
            showCompleteModal(reservationId);
        }
    }
});
// Toast notification function
function showToast(message, type = 'warning') {
    var toastContainer = document.getElementById('customToastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'customToastContainer';
        toastContainer.style.position = 'fixed';
        toastContainer.style.top = '30px';
        toastContainer.style.right = '30px';
        toastContainer.style.zIndex = 9999;
        document.body.appendChild(toastContainer);
    }
    var toast = document.createElement('div');
    var bgClass = type === 'success' ? 'text-bg-success' : 'text-bg-warning';
    toast.className = 'toast align-items-center ' + bgClass + ' border-0';
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="toast-header ${type === 'success' ? 'bg-success text-white' : 'bg-warning text-dark'}">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="d-flex">
            <div class="toast-body">${message}</div>
        </div>
    `;
    toastContainer.appendChild(toast);
    var bsToast = new bootstrap.Toast(toast, { delay: 4000 });
    bsToast.show();
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}
document.addEventListener('DOMContentLoaded', function() {
    // Show toast if msg param exists in URL
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg) {
        showToast(decodeURIComponent(msg), 'success');
    }
});
</script>
</body>
</html> 