<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';

// Mark as read (single)
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $nid = intval($_GET['read']);
    mysqli_query($mycon, "UPDATE admin_notifications SET is_read = 1 WHERE admin_notif_id = $nid AND admin_id = {$_SESSION['admin_id']}");
    header('Location: notifications.php');
    exit();
}
// Mark all as read
if (isset($_GET['readall'])) {
    mysqli_query($mycon, "UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0 AND admin_id = {$_SESSION['admin_id']}");
    header('Location: notifications.php');
    exit();
}
// Sorting
$sort = $_GET['sort'] ?? 'newest';
$order_by = 'n.created_at DESC';
$type_filter = '';
$params = [$_SESSION['admin_id']];
$types = 'i';
if ($sort === 'oldest') {
    $order_by = 'n.created_at ASC';
} else if (in_array($sort, ['reservation', 'payment', 'wallet', 'profile', 'admin'])) {
    $type_filter = ' AND n.type = ?';
    $params[] = $sort;
    $types .= 's';
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$count_sql = "SELECT COUNT(*) FROM admin_notifications n WHERE n.admin_id = ?$type_filter";
$count_stmt = mysqli_prepare($mycon, $count_sql);
if ($types && count($params) > 0) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_row = mysqli_fetch_row($count_result);
$total = $total_row[0];
$pages = ceil($total / $per_page);
mysqli_stmt_close($count_stmt);

$sql = "SELECT n.* FROM admin_notifications n WHERE n.admin_id = ?$type_filter ORDER BY $order_by LIMIT $per_page OFFSET $offset";
$stmt = mysqli_prepare($mycon, $sql);
if ($types && count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if (!$res) {
    echo '<div class="alert alert-danger">MySQL Error: ' . mysqli_error($mycon) . '</div>';
}
// Debug output: print fetched rows as JSON above the table
$debug_rows = [];
if ($res && mysqli_num_rows($res) > 0) {
    mysqli_data_seek($res, 0); // Reset pointer
    while ($row = mysqli_fetch_assoc($res)) {
        $debug_rows[] = $row;
    }
}

// Array of phrases that typically indicate a user-only notification
// Add more phrases here as needed to filter out user-specific messages
$user_only_phrases = [
// ... existing code ...
];

function is_user_only_message($message) {
    global $user_only_phrases;
    foreach ($user_only_phrases as $phrase) {
// ... existing code ...
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Notifications - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        .container { margin-left: 240px; margin-top: 70px; padding: 40px 24px 24px 24px; }
        .notif-card { background: #23234a; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.12); margin-bottom: 18px; }
        .notif-type-reservation { color: #FF8C00; }
        .notif-type-wallet { color: #00c896; }
        .notif-type-profile { color: #1e90ff; }
        .notif-date { font-size: 0.95em; color: #bdbdbd; }
        .notif-unread-new {
            border-left: 5px solid #FF8C00;
            background: linear-gradient(90deg, #2d2d5a 80%, #23234a 100%);
            box-shadow: 0 4px 18px rgba(255,140,0,0.10);
            position: relative;
        }
        @media (max-width: 900px) { .container { margin-left: 70px; padding: 18px 4px; } }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="container py-5">
    <h2 class="mb-4 text-warning"><i class="bi bi-bell-fill"></i> Admin Notifications</h2>
    <form method="get" class="mb-3 d-flex align-items-center gap-2">
        <label for="sort" class="form-label mb-0">Sort/Filter by:</label>
        <select name="sort" id="sort" class="form-select w-auto" onchange="this.form.submit()">
            <option value="newest" <?php if ($sort === 'newest') echo 'selected'; ?>>Newest first</option>
            <option value="oldest" <?php if ($sort === 'oldest') echo 'selected'; ?>>Oldest first</option>
            <option value="reservation" <?php if ($sort === 'reservation') echo 'selected'; ?>>Reservation</option>
            <option value="payment" <?php if ($sort === 'payment') echo 'selected'; ?>>Payment</option>
            <option value="wallet" <?php if ($sort === 'wallet') echo 'selected'; ?>>Wallet</option>
            <option value="profile" <?php if ($sort === 'profile') echo 'selected'; ?>>Profile</option>
            <option value="admin" <?php if ($sort === 'admin') echo 'selected'; ?>>Admin</option>
        </select>
    </form>
    <div id="adminNotifList">
    <?php
    $notifications = [];
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $notifications[] = $row;
        }
    }
    ?>
    <?php if (count($notifications) > 0): ?>
        <?php foreach ($notifications as $notif): ?>
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
                    <div><?php echo $notif['message']; ?></div>
                </div>
            </div>
        </div>
            <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No notifications yet.</div>
    <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Real-time notifications polling
function fetchNotifications() {
    const sort = document.getElementById('sort') ? document.getElementById('sort').value : 'newest';
    fetch('ajax_admin_notifications.php?sort=' + encodeURIComponent(sort))
        .then(response => response.text())
        .then(html => {
            var notifList = document.getElementById('adminNotifList');
            if (notifList) notifList.innerHTML = html;
        });
}
setInterval(fetchNotifications, 10000);
document.addEventListener('DOMContentLoaded', fetchNotifications);
</script>
</body>
</html> 