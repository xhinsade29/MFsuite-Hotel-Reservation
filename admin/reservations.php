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
    <div class="table-section">
        <div class="table-title">Reservation List</div>
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
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, p.amount, p.payment_method, p.payment_status FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id ORDER BY r.date_created DESC";
            $res = mysqli_query($mycon, $sql);
            if ($res && mysqli_num_rows($res) > 0) {
                $status_options = [
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'cancelled' => 'Cancelled',
                    'denied' => 'Denied',
                    'completed' => 'Completed',
                    'cancellation_requested' => 'Cancellation Requested'
                ];
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
                    echo '<td>';
                    // Status update dropdown
                    echo '<form method="POST" action="process_update_status.php" class="d-flex align-items-center" style="gap:6px;">';
                    echo '<input type="hidden" name="reservation_id" value="' . $row['reservation_id'] . '">';
                    echo '<select name="new_status" class="form-select form-select-sm" style="width:auto;display:inline-block;">';
                    foreach ($status_options as $val => $label) {
                        $selected = ($val === $status) ? 'selected' : '';
                        echo '<option value="' . $val . '" ' . $selected . '>' . $label . '</option>';
                    }
                    echo '</select>';
                    echo '<button type="submit" class="btn btn-primary btn-sm">Update</button>';
                    echo '</form>';
                    echo '</td>';
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
</body>
</html> 