<?php
session_start();
if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit();
}
include('../functions/db_connect.php');
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';
$where = '';
$params = [];
$types = '';
if ($type_filter !== '') {
    $where .= ($where ? ' AND ' : 'WHERE ') . 'type = ?';
    $params[] = $type_filter;
    $types .= 's';
}
$sql = "SELECT * FROM user_notifications $where ORDER BY created_at DESC";
$stmt = mysqli_prepare($mycon, $sql);
if ($types && count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}
mysqli_stmt_close($stmt);
// Mark all as read for the current user only
$guest_id = $_SESSION['guest_id'];
$mycon->query("UPDATE user_notifications SET is_read = 1 WHERE guest_id = $guest_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; }
        .notif-card { background: #23234a; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.12); margin-bottom: 18px; }
        .notif-type-reservation { color: #FF8C00; }
        .notif-type-wallet { color: #00c896; }
        .notif-type-profile { color: #1e90ff; }
        .notif-date { font-size: 0.95em; color: #bdbdbd; }
        .notif-unread { border-left: 4px solid #FF8C00; }
    </style>
</head>
<body>
<?php include '../components/user_navigation.php'; ?>
<div class="container py-5">
    <h2 class="mb-4 text-warning"><i class="bi bi-bell-fill"></i> My Notifications</h2>
    <form class="row g-3 mb-4" method="get">
        <div class="col-md-4">
            <select name="type" class="form-select">
                <option value="">All Types</option>
                <option value="reservation" <?php if($type_filter==='reservation') echo 'selected'; ?>>Reservation</option>
                <option value="wallet" <?php if($type_filter==='wallet') echo 'selected'; ?>>Wallet</option>
                <option value="profile" <?php if($type_filter==='profile') echo 'selected'; ?>>Profile</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>
    <?php if (count($notifications) > 0): ?>
        <?php foreach ($notifications as $notif): ?>
        <div class="notif-card p-4 mb-3 <?php if(!$notif['is_read']) echo 'notif-unread'; ?>">
            <div class="d-flex align-items-center mb-2">
                <?php if ($notif['type'] === 'reservation'): ?>
                    <i class="bi bi-calendar2-check notif-type-reservation me-2"></i>
                <?php elseif ($notif['type'] === 'wallet'): ?>
                    <i class="bi bi-wallet2 notif-type-wallet me-2"></i>
                <?php elseif ($notif['type'] === 'profile'): ?>
                    <i class="bi bi-person-circle notif-type-profile me-2"></i>
                <?php else: ?>
                    <i class="bi bi-info-circle me-2"></i>
                <?php endif; ?>
                <span class="fw-bold text-capitalize me-2 notif-type-<?php echo htmlspecialchars($notif['type']); ?>">
                    <?php echo htmlspecialchars($notif['type']); ?>
                </span>
                <span class="notif-date ms-auto"><?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?></span>
            </div>
            <div><?php echo htmlspecialchars($notif['message']); ?></div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No notifications yet.</div>
    <?php endif; ?>
    <a href="index.php" class="btn btn-outline-light mt-4">Back to Dashboard</a>
</div>
</body>
</html> 