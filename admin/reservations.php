<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
require_once '../functions/payment_helpers.php';
// Auto-complete reservations after checkout
$now = date('Y-m-d H:i:s');
$auto_complete_sql = "UPDATE tbl_reservation SET status = 'completed' WHERE status = 'approved' AND check_out < '$now'";
$mycon->query($auto_complete_sql);
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
        .reservations-container { margin-left: 240px; padding: 40px 24px 24px 24px; }
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
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Room Number</th>
                    <th>Room Status</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Payment Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $active_sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, rm.room_number, rm.status AS room_status, p.payment_status, p.payment_method FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE r.status IN ('pending','approved') ORDER BY r.date_created DESC";
            $active_res = mysqli_query($mycon, $active_sql);
            if ($active_res && mysqli_num_rows($active_res) > 0) {
                while ($row = mysqli_fetch_assoc($active_res)) {
                    echo '<tr>';
                    echo '<td>' . $row['reservation_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['room_number']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['room_status']) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
                    $status = $row['status'];
                    echo '<td><span class="badge bg-'.(
                        $status==='approved'?'success':(
                        $status==='cancelled'?'danger':(
                        $status==='denied'?'warning text-dark':(
                        $status==='completed'?'primary':(
                        $status==='cancellation_requested'?'info text-dark':'secondary'))))).'">'.ucfirst($status).'</span></td>';
                    echo '<td>' . htmlspecialchars($row['payment_status']) . '</td>';
                    echo '<td>';
                    // Action buttons logic here (approve, payment, complete, etc.)
                    // Mark as Completed button
                    if ($status === 'approved' && strtotime($row['check_out']) < time()) {
                        echo '<button class="btn btn-primary btn-sm" onclick="showCompleteModal(' . $row['reservation_id'] . ')">Mark as Completed</button>';
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="10" class="text-center text-secondary">No active reservations.</td></tr>';
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
            $sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id $where_sql $list_order";
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
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6" class="text-center text-secondary">No reservations found.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>

    <!-- Transaction History (collapsible, styled like Reservation List) -->
    <div class="table-section mt-4">
        <div class="table-title d-flex justify-content-between align-items-center">
            <span>Transaction History</span>
            <button class="btn btn-link text-warning p-0" type="button" data-bs-toggle="collapse" data-bs-target="#transactionHistoryTable" aria-expanded="false" aria-controls="transactionHistoryTable">
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
        <div class="collapse" id="transactionHistoryTable">
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" name="payment_guest" class="form-control form-control-sm" placeholder="Filter by Guest Name" value="<?php echo htmlspecialchars($_GET['payment_guest'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <select name="payment_room_type" class="form-select form-select-sm">
                    <option value="">All Room Types</option>
                    <?php
                    $rtypes = mysqli_query($mycon, "SELECT room_type_id, type_name FROM tbl_room_type");
                    while ($rt = mysqli_fetch_assoc($rtypes)) {
                        $selected = (isset($_GET['payment_room_type']) && $_GET['payment_room_type'] == $rt['room_type_id']) ? 'selected' : '';
                        echo '<option value="' . $rt['room_type_id'] . '" ' . $selected . '>' . htmlspecialchars($rt['type_name']) . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="col-12 text-end">
                <label for="history_sort" class="form-label me-2">Sort by:</label>
                <select name="history_sort" id="history_sort" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                    <option value="date_desc" <?php if (($history_sort ?? '') == 'date_desc') echo 'selected'; ?>>Newest</option>
                    <option value="date_asc" <?php if (($history_sort ?? '') == 'date_asc') echo 'selected'; ?>>Oldest</option>
                    <option value="guest" <?php if (($history_sort ?? '') == 'guest') echo 'selected'; ?>>Guest Name</option>
                    <option value="room" <?php if (($history_sort ?? '') == 'room') echo 'selected'; ?>>Room Type</option>
                </select>
                <button type="submit" class="btn btn-warning ms-2">Filter</button>
                <a href="reservations.php" class="btn btn-secondary ms-2">Reset</a>
            </div>
        </form>
        <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Guest</th>
                    <th>Room</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $period = isset($_GET['filter_period']) ? $_GET['filter_period'] : 'all';
            $where = [];
            if ($period == 'day') {
                $where[] = "DATE(p.payment_created) = CURDATE()";
            } elseif ($period == 'week') {
                $where[] = "YEARWEEK(p.payment_created, 1) = YEARWEEK(CURDATE(), 1)";
            } elseif ($period == 'month') {
                $where[] = "YEAR(p.payment_created) = YEAR(CURDATE()) AND MONTH(p.payment_created) = MONTH(CURDATE())";
            }
            if (!empty($_GET['payment_guest'])) {
                $guest = mysqli_real_escape_string($mycon, $_GET['payment_guest']);
                $where[] = "(g.first_name LIKE '%$guest%' OR g.last_name LIKE '%$guest%')";
            }
            if (!empty($_GET['payment_room_type'])) {
                $rtype = intval($_GET['payment_room_type']);
                $where[] = "rt.room_type_id = $rtype";
            }
            $history_sort = $_GET['history_sort'] ?? 'date_desc';
            $history_order = 'ORDER BY p.payment_created DESC';
            if ($history_sort === 'date_asc') $history_order = 'ORDER BY p.payment_created ASC';
            if ($history_sort === 'guest') $history_order = 'ORDER BY g.last_name ASC, g.first_name ASC';
            if ($history_sort === 'room') $history_order = 'ORDER BY rt.type_name ASC';
            $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
            $history_sql = "SELECT p.payment_id, g.first_name, g.last_name, rt.type_name, p.amount, p.payment_method, p.payment_status, p.payment_created FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id $where_sql $history_order";
            $history_res = mysqli_query($mycon, $history_sql);
            if ($history_res && mysqli_num_rows($history_res) > 0) {
                while ($row = mysqli_fetch_assoc($history_res)) {
                    echo '<tr>';
                    echo '<td>' . $row['payment_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
                    echo '<td>₱' . number_format($row['amount'], 2) . '</td>';
                    echo '<td>' . htmlspecialchars($row['payment_method']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['payment_status']) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['payment_created'])) . '</td>';
                    echo '</tr>';
                    require_once '../functions/payment_helpers.php';
                    auto_approve_reservation_if_paid($row['payment_id'], $mycon);
                }
            } else {
                echo '<tr><td colspan="7" class="text-center text-secondary">No transactions found.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        </div>
        </div>
    </div>
</div>
<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form id="approveForm" method="POST" action="process_update_status.php">
      <input type="hidden" name="reservation_id" id="approveReservationId">
      <input type="hidden" name="new_status" value="approved">
      <input type="hidden" name="action" value="approve">
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
      <input type="hidden" name="new_status" value="completed">
      <input type="hidden" name="action" value="complete">
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
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 