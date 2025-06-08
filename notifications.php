<?php
// Count unread reservation and payment notifications for user
$unread_reservation_count = 0;
$unread_payment_count = 0;
$user_id = $_SESSION['guest_id'] ?? 0;
if ($user_id) {
    $res_count = mysqli_query($mycon, "SELECT type, COUNT(*) as cnt FROM notifications WHERE recipient_id = $user_id AND recipient_type = 'user' AND is_read = 0 AND type IN ('reservation','payment') GROUP BY type");
    if ($res_count) {
        while ($row = mysqli_fetch_assoc($res_count)) {
            if ($row['type'] === 'reservation') $unread_reservation_count = $row['cnt'];
            if ($row['type'] === 'payment') $unread_payment_count = $row['cnt'];
        }
    }
}
?>
<div class="mb-3">
    <?php if ($unread_reservation_count > 0): ?>
        <span class="badge bg-warning text-dark me-2">Unread Reservation: <?php echo $unread_reservation_count; ?></span>
    <?php endif; ?>
    <?php if ($unread_payment_count > 0): ?>
        <span class="badge bg-success me-2">Unread Payment: <?php echo $unread_payment_count; ?></span>
    <?php endif; ?>
</div>
<?php if (!empty($notifications)): ?>
    <?php foreach ($notifications as $notif): ?>
        <?php
            // Add badge for notification type with distinct colors
            $type_badge = '';
            if ($notif['type'] === 'reservation') {
                $type_badge = '<span class="badge bg-warning text-dark me-2">Reservation</span>';
            } elseif ($notif['type'] === 'payment') {
                $type_badge = '<span class="badge bg-success me-2">Payment</span>';
            } elseif ($notif['type'] === 'profile') {
                $type_badge = '<span class="badge bg-primary me-2">Profile</span>';
            } elseif ($notif['type'] === 'wallet') {
                $type_badge = '<span class="badge bg-purple me-2" style="background:#8e44ad;">Wallet</span>';
            } elseif ($notif['type'] === 'cancellation') {
                $type_badge = '<span class="badge bg-danger me-2">Cancellation</span>';
            }
        ?>
        <div class="notif-card p-4 mb-3 d-flex align-items-start <?php if(!$notif['is_read']) echo 'notif-unread-new'; ?>">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-2">
                    <?php echo $type_badge; ?>
                    <span class="fw-bold text-capitalize me-2 notif-type-<?php echo htmlspecialchars($notif['type']); ?>">
                        <?php echo htmlspecialchars($notif['type']); ?>
                    </span>
                    <span class="notif-date ms-auto"><?php echo date('F j, Y, g:i a', strtotime($notif['created_at'])); ?></span>
                </div>
                <div><?php echo $notif['message']; ?></div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
// ... existing code ... 