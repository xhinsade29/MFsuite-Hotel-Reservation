<?php
session_start();
include '../functions/db_connect.php';
$admin_id = $_SESSION['admin_id'] ?? 0;
$sort = $_GET['sort'] ?? 'newest';
$order_by = 'n.created_at DESC';
$type_filter = '';
$params = [$admin_id];
$types = 'i';
if ($sort === 'oldest') {
    $order_by = 'n.created_at ASC';
} else if (in_array($sort, ['reservation', 'payment', 'wallet', 'profile', 'admin'])) {
    $type_filter = ' AND n.type = ?';
    $params[] = $sort;
    $types .= 's';
}
$sql = "SELECT n.*, g.first_name, g.last_name FROM user_notifications n LEFT JOIN tbl_guest g ON n.guest_id = g.guest_id WHERE n.admin_id = ?$type_filter ORDER BY $order_by LIMIT 20";
$stmt = mysqli_prepare($mycon, $sql);
if ($types && count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$notifications = [];
while ($row = mysqli_fetch_assoc($res)) {
    $notifications[] = $row;
}
mysqli_stmt_close($stmt);
foreach ($notifications as $notif): ?>
<div class="notif-card p-4 mb-3 d-flex align-items-start <?php if(!$notif['is_read']) echo 'notif-unread-new'; ?>">
    <div class="flex-grow-1">
        <div class="d-flex align-items-center mb-2">
            <?php if ($notif['type'] === 'reservation'): ?>
                <i class="bi bi-calendar2-check notif-type-reservation me-2"></i>
            <?php elseif ($notif['type'] === 'wallet'): ?>
                <i class="bi bi-wallet2 notif-type-wallet me-2"></i>
            <?php elseif ($notif['type'] === 'profile'): ?>
                <i class="bi bi-person-circle notif-type-profile me-2"></i>
            <?php elseif ($notif['type'] === 'admin'): ?>
                <i class="bi bi-person-badge notif-type-profile me-2"></i>
            <?php else: ?>
                <i class="bi bi-info-circle me-2"></i>
            <?php endif; ?>
            <span class="fw-bold text-capitalize me-2 notif-type-<?php echo htmlspecialchars($notif['type']); ?>">
                <?php echo htmlspecialchars($notif['type']); ?>
            </span>
            <span class="notif-date ms-auto"><?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?></span>
        </div>
        <div><?php echo htmlspecialchars($notif['message']); ?></div>
        <?php if (!empty($notif['first_name']) || !empty($notif['last_name'])): ?>
            <div class="text-muted small mt-1">Guest: <?php echo htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($notifications)): ?>
<div class="alert alert-info">No notifications found for this filter.</div>
<?php endif; ?> 