<?php
session_start();
include '../functions/db_connect.php';
$admin_id = $_SESSION['admin_id'] ?? 0;
$sort = $_GET['sort'] ?? 'newest';
$order_by = 'n.created_at DESC';
$type_filter = '';
$params = [$admin_id];
$types = 'i';
if (isset($_GET['count']) && $_GET['count'] == '1') {
    $count_sql = "SELECT COUNT(*) as cnt FROM admin_notifications WHERE admin_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($mycon, $count_sql);
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    echo json_encode(['count' => (int)($row['cnt'] ?? 0)]);
    exit;
}
if ($sort === 'oldest') {
    $order_by = 'n.created_at ASC';
} else if (in_array($sort, ['reservation', 'payment', 'wallet', 'profile', 'admin'])) {
    $type_filter = ' AND n.type = ?';
    $params[] = $sort;
    $types .= 's';
}
$sql = "SELECT n.* FROM admin_notifications n WHERE n.admin_id = ?$type_filter ORDER BY $order_by LIMIT 20";
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
<?php
    $is_clickable = false;
    $link_href = '#';
    if (!empty($notif['related_id'])) {
        if ($notif['type'] === 'cancellation') {
            $is_clickable = true;
            $link_href = 'dashboard.php#pending-cancellations';
        } elseif ($notif['type'] === 'payment') {
            $is_clickable = true;
            $link_href = 'dashboard.php#pending-cash-approvals';
        } elseif ($notif['type'] === 'reservation' && strpos($notif['message'], 'Pending approval') !== false) {
            $is_clickable = true;
            $link_href = 'dashboard.php#pending-cash-approvals';
        } elseif ($notif['type'] === 'reservation') {
            $is_clickable = true;
            $link_href = 'reservations.php?id=' . $notif['related_id'];
        }
    }
    $tag_open = $is_clickable ? '<a href="' . $link_href . '" class="notification-card-link">' : '<div class="notif-wrapper">';
    $tag_close = $is_clickable ? '</a>' : '</div>';
    $card_class = $is_clickable ? 'clickable' : '';
?>
<?php echo $tag_open; ?>
<div class="notif-card p-4 mb-3 d-flex align-items-start <?php if(!$notif['is_read']) echo 'notif-unread-new'; ?> <?php echo $card_class; ?>">
    <div class="flex-grow-1">
        <div class="d-flex align-items-center mb-2">
            <?php if ($notif['type'] === 'reservation'): ?>
                <i class="bi bi-calendar2-check notif-type-reservation me-2"></i>
            <?php elseif ($notif['type'] === 'cancellation'): ?>
                <i class="bi bi-x-circle notif-type-reservation me-2"></i>
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
    </div>
</div>
<?php echo $tag_close; ?>
<?php endforeach; ?>
<?php if (empty($notifications)): ?>
<div class="alert alert-info">No notifications found for this filter.</div>
<?php endif; ?>
<style>
.notification-card-link {
    text-decoration: none;
    color: inherit;
}
.notif-card.clickable:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-left-color: #ff8c00;
}
.notif-card.clickable {
    cursor: pointer;
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out, border-left-color 0.2s ease-in-out;
}
</style> 