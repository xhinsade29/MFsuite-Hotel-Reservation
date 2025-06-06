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
            <tbody id="activeReservationsTableBody">
            <?php
            // Remove PHP loop here, will be filled by AJAX
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
        </div>
        <div>
            <form id="reservationFilterForm" method="GET" class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="list_status" class="form-label me-2">Filter by Status:</label>
                    <select name="list_status" id="list_status" class="form-select d-inline-block w-auto">
                        <option value="all" <?php if (($list_status ?? '') == 'all') echo 'selected'; ?>>All</option>
                        <option value="completed" <?php if (($list_status ?? '') == 'completed') echo 'selected'; ?>>Completed</option>
                        <option value="cancelled" <?php if (($list_status ?? '') == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                        <option value="denied" <?php if (($list_status ?? '') == 'denied') echo 'selected'; ?>>Denied</option>
                        <option value="approved" <?php if (($list_status ?? '') == 'approved') echo 'selected'; ?>>Approved</option>
                        <option value="pending" <?php if (($list_status ?? '') == 'pending') echo 'selected'; ?>>Pending</option>
                        <option value="cancellation_requested" <?php if (($list_status ?? '') == 'cancellation_requested') echo 'selected'; ?>>Cancellation Requested</option>
                    </select>
                </div>
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
                <tbody id="reservationListTableBody">
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
                
                $filter_status = $_GET['list_status'] ?? 'all';
                if ($filter_status !== 'all') {
                    $status_to_filter = mysqli_real_escape_string($mycon, $filter_status);
                    $where[] = "r.status = '$status_to_filter'";
                }

                $list_order = 'ORDER BY r.date_created DESC';

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

    <div id="reservations-table-container">
        <!-- Main reservations table will be loaded here by AJAX -->
    </div>
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
function refreshActiveReservationsTable() {
    fetch('ajax_active_reservations_table.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('activeReservationsTableBody').innerHTML = html;
        });
}
function refreshCompletedReservationsTable() {
    fetch('ajax_completed_reservations_table.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('completedReservationsTableBody').innerHTML = html;
        });
}
setInterval(refreshActiveReservationsTable, 5000); // Refreshes every 5 seconds
document.addEventListener('DOMContentLoaded', refreshActiveReservationsTable);
document.addEventListener('DOMContentLoaded', refreshCompletedReservationsTable);
function fetchRoomList(roomTypeId) {
    fetch('ajax_room_status_table.php?room_type_id=' + roomTypeId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('roomListTable' + roomTypeId).innerHTML = html;
            attachRoomEditListeners();
            attachRoomRowFormListeners();
        });
}
// AJAX filter/sort for Reservation List
function fetchReservationListAJAX(params) {
    fetch('ajax_reservations_table.php?' + params)
        .then(response => response.text())
        .then(html => {
            document.getElementById('reservationListTableBody').innerHTML = html;
        });
}
document.addEventListener('DOMContentLoaded', function() {
    var filterForm = document.getElementById('reservationFilterForm');
    var sortSelect = document.getElementById('list_sort');
    var resetBtn = document.getElementById('resetReservationFilter');
    var guestInput = filterForm.querySelector('input[name="guest"]');
    var roomTypeSelect = filterForm.querySelector('select[name="room_type"]');
    var typingTimer;
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var params = new URLSearchParams(new FormData(filterForm)).toString();
            fetchReservationListAJAX(params);
        });
    }
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            var params = new URLSearchParams(new FormData(filterForm)).toString();
            fetchReservationListAJAX(params);
        });
    }
    if (roomTypeSelect) {
        roomTypeSelect.addEventListener('change', function() {
            var params = new URLSearchParams(new FormData(filterForm)).toString();
            fetchReservationListAJAX(params);
        });
    }
    if (resetBtn) {
        resetBtn.addEventListener('click', function(e) {
            e.preventDefault();
            filterForm.reset();
            fetchReservationListAJAX('');
        });
    }
    if (guestInput) {
        guestInput.addEventListener('input', function() {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(function() {
                var params = new URLSearchParams(new FormData(filterForm)).toString();
                fetchReservationListAJAX(params);
            }, 350); // debounce for 350ms
        });
    }
});
document.addEventListener('DOMContentLoaded', function() {
    var statusSelect = document.getElementById('list_status');
    var guestInput = document.querySelector('input[name="guest"]');
    var roomTypeSelect = document.querySelector('select[name="room_type"]');
    var tableBody = document.getElementById('reservationListTableBody');

    function fetchTable() {
        var params = new URLSearchParams();
        params.append('list_status', statusSelect.value);
        if (guestInput && guestInput.value) params.append('guest', guestInput.value);
        if (roomTypeSelect && roomTypeSelect.value) params.append('room_type', roomTypeSelect.value);
        fetch('ajax_reservations_table.php?' + params.toString())
            .then(response => response.text())
            .then(html => { tableBody.innerHTML = html; });
    }

    if (statusSelect) statusSelect.addEventListener('change', fetchTable);
    if (guestInput) guestInput.addEventListener('input', function() { setTimeout(fetchTable, 300); });
    if (roomTypeSelect) roomTypeSelect.addEventListener('change', fetchTable);

    // Initial load
    fetchTable();
});
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