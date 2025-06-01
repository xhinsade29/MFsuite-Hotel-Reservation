<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
$conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Fetch all pending cancellation requests
$sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.date_created, r.status, g.first_name, g.middle_name, g.last_name, g.user_email, g.phone_number, g.address, cr.reason_text
        FROM tbl_reservation r
        JOIN cancelled_reservation c ON r.reservation_id = c.reservation_id
        JOIN tbl_guest g ON r.guest_id = g.guest_id
        JOIN tbl_cancellation_reason cr ON c.reason_id = cr.reason_id
        WHERE r.status = 'pending' ORDER BY r.date_created DESC";
$result = $conn->query($sql);

// Count new cancellation requests
$cancellation_count = 0;
$count_sql = "SELECT COUNT(*) as cnt FROM tbl_reservation WHERE status = 'cancellation_requested'";
$count_res = $conn->query($count_sql);
if ($count_res && $row = $count_res->fetch_assoc()) {
    $cancellation_count = (int)$row['cnt'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cancellation Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
<div class="container mt-5">
    <h2 class="mb-4 text-warning">Pending Cancellation Requests
      <?php if ($cancellation_count > 0): ?>
        <span class="badge bg-danger ms-2"><?php echo $cancellation_count; ?></span>
      <?php endif; ?>
    </h2>
    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success"> <?php echo htmlspecialchars($_GET['msg']); ?> </div>
    <?php endif; ?>
    <?php if ($result && $result->num_rows > 0): ?>
    <table class="table table-dark table-bordered table-hover">
        <thead>
            <tr>
                <th>Reservation ID</th>
                <th>User</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Reason</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['reservation_id']; ?></td>
                <td><?php echo htmlspecialchars(trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name'])); ?></td>
                <td><?php echo htmlspecialchars($row['user_email']); ?></td>
                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                <td><?php echo htmlspecialchars($row['check_in']); ?></td>
                <td><?php echo htmlspecialchars($row['check_out']); ?></td>
                <td><?php echo htmlspecialchars($row['reason_text']); ?></td>
                <td>
                    <form method="POST" action="process_cancellation.php" style="display:inline-block;">
                        <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                    </form>
                    <form method="POST" action="process_cancellation.php" style="display:inline-block;">
                        <input type="hidden" name="reservation_id" value="<?php echo $row['reservation_id']; ?>">
                        <button type="submit" name="action" value="deny" class="btn btn-danger btn-sm">Deny</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div class="alert alert-info">No pending cancellation requests.</div>
    <?php endif; ?>
</div>
</body>
</html>
<?php $conn->close(); ?> 