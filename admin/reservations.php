<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
// Auto-update reservation status to 'completed' for non-cash paid reservations
$auto_update_sql = "UPDATE tbl_reservation r
    LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id
    SET r.status = 'completed'
    WHERE p.payment_status = 'Paid' AND p.payment_method != 'Cash' AND r.status != 'completed'";
mysqli_query($mycon, $auto_update_sql);
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
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="reservations-container">
    <div class="reservations-title">All Reservations</div>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success text-center mb-3"><?php echo htmlspecialchars($_GET['msg']); ?></div>
    <?php endif; ?>
    <!-- Reservation List with Filters -->
    <div class="table-section mb-4">
        <div class="table-title">Reservation List</div>
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" name="guest" class="form-control" placeholder="Filter by Guest Name" value="<?php echo htmlspecialchars($_GET['guest'] ?? ''); ?>">
            </div>
            <div class="col-md-4">
                <select name="payment_type" class="form-select">
                    <option value="">All Payment Types</option>
                    <?php
                    $ptypes = mysqli_query($mycon, "SELECT payment_type_id, payment_name FROM tbl_payment_types");
                    while ($pt = mysqli_fetch_assoc($ptypes)) {
                        $selected = (isset($_GET['payment_type']) && $_GET['payment_type'] == $pt['payment_type_id']) ? 'selected' : '';
                        echo '<option value="' . $pt['payment_type_id'] . '" ' . $selected . '>' . htmlspecialchars($pt['payment_name']) . '</option>';
                    }
                    ?>
                </select>
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
                <button type="submit" class="btn btn-warning">Filter</button>
                <a href="reservations.php" class="btn btn-secondary">Reset</a>
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
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Payment Status</th>
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
            if (!empty($_GET['payment_type'])) {
                $ptype = intval($_GET['payment_type']);
                $where[] = "p.payment_type_id = $ptype";
            }
            if (!empty($_GET['room_type'])) {
                $rtype = intval($_GET['room_type']);
                $where[] = "rt.room_type_id = $rtype";
            }
            $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
            $sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, p.amount, p.payment_method, p.payment_status FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id $where_sql ORDER BY r.date_created DESC";
            $res = mysqli_query($mycon, $sql);
            if ($res && mysqli_num_rows($res) > 0) {
                while ($row = mysqli_fetch_assoc($res)) {
                    echo '<tr>';
                    echo '<td>' . $row['reservation_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
                    echo '<td>₱' . number_format($row['amount'], 2) . '</td>';
                    echo '<td>' . htmlspecialchars($row['payment_method']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['payment_status']) . '</td>';
                    echo '<td>';
                    $status = $row['status'];
                    echo '<span class="badge bg-'.($status==='approved'?'success':($status==='cancelled'?'danger':($status==='denied'?'warning text-dark':($status==='completed'?'primary':($status==='pending'?'secondary':'dark'))))).'">'.ucfirst($status).'</span>';
                    echo '</td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="9" class="text-center text-secondary">No reservations found.</td></tr>';
            }
            ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Active Reservations Table -->
    <div class="table-section mt-4">
        <div class="table-title">Active Reservations</div>
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
                    <th>Payment Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $active_sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, p.payment_status, p.payment_method FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE r.status = 'pending' OR r.status = 'cancellation_requested' OR r.status = 'denied' OR r.status = 'cancelled' OR r.status = 'completed' ORDER BY r.date_created DESC";
            $active_res = mysqli_query($mycon, $active_sql);
            if ($active_res && mysqli_num_rows($active_res) > 0) {
                while ($row = mysqli_fetch_assoc($active_res)) {
                    echo '<tr>';
                    echo '<td>' . $row['reservation_id'] . '</td>';
                    echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
                    echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
                    echo '<td><span class="badge bg-info text-dark">' . ucfirst($row['status']) . '</span></td>';
                    echo '<td>' . htmlspecialchars($row['payment_status']) . '</td>';
                    echo '<td>';
                    if (
                        $row['payment_status'] === 'Paid' && $row['payment_method'] !== 'Cash'
                    ) {
                        echo '<span class="text-success">Completed & Paid</span>';
                    } else if ($row['payment_status'] !== 'Paid' && !in_array($row['status'], ['cancelled', 'denied', 'completed'])) {
                        echo '<form method="POST" action="process_cash_approval.php" style="display:inline-block;">';
                        echo '<input type="hidden" name="reservation_id" value="' . $row['reservation_id'] . '">';
                        echo '<button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve & Mark as Paid</button>';
                        echo '</form>';
                    } else if ($row['status'] === 'cancelled' || $row['status'] === 'denied') {
                        echo '<span class="text-danger">' . ucfirst($row['status']) . '</span>';
                    } else {
                        echo '<span class="text-success">Completed & Paid</span>';
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

    <!-- Transaction History Section -->
    <div class="table-section mt-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="table-title mb-0">Transaction History</div>
            <form method="GET" class="d-flex align-items-center gap-2">
                <label for="filter_period" class="form-label mb-0 me-2">Sort by:</label>
                <select name="filter_period" id="filter_period" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="all" <?php if (!isset($_GET['filter_period']) || $_GET['filter_period'] == 'all') echo 'selected'; ?>>All</option>
                    <option value="day" <?php if (isset($_GET['filter_period']) && $_GET['filter_period'] == 'day') echo 'selected'; ?>>Day</option>
                    <option value="week" <?php if (isset($_GET['filter_period']) && $_GET['filter_period'] == 'week') echo 'selected'; ?>>Week</option>
                    <option value="month" <?php if (isset($_GET['filter_period']) && $_GET['filter_period'] == 'month') echo 'selected'; ?>>Month</option>
                </select>
            </form>
        </div>
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
            $where = '';
            if ($period == 'day') {
                $where = "WHERE DATE(p.payment_created) = CURDATE()";
            } elseif ($period == 'week') {
                $where = "WHERE YEARWEEK(p.payment_created, 1) = YEARWEEK(CURDATE(), 1)";
            } elseif ($period == 'month') {
                $where = "WHERE YEAR(p.payment_created) = YEAR(CURDATE()) AND MONTH(p.payment_created) = MONTH(CURDATE())";
            }
            $history_sql = "SELECT p.payment_id, g.first_name, g.last_name, rt.type_name, p.amount, p.payment_method, p.payment_status, p.payment_created FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id $where ORDER BY p.payment_created DESC";
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
</body>
</html> 