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
    mysqli_query($mycon, "UPDATE notifications SET is_read = 1 WHERE notification_id = $nid");
    header('Location: notifications.php');
    exit();
}
// Mark all as read
if (isset($_GET['readall'])) {
    mysqli_query($mycon, "UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    header('Location: notifications.php');
    exit();
}
// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$total = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM notifications"))[0];
$pages = ceil($total / $per_page);

$sql = "SELECT * FROM user_notifications ORDER BY created_at DESC LIMIT $per_page OFFSET $offset";
$res = mysqli_query($mycon, $sql);
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
    // Re-run the query for the actual table rendering
    $res = mysqli_query($mycon, $sql);
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
        .notifications-container { margin-left: 240px; padding: 40px 24px 24px 24px; }
        .notifications-title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; }
        .table-section { background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 32px 18px; }
        .table-title { color: #ffa533; font-size: 1.3rem; font-weight: 600; margin-bottom: 18px; }
        .table thead { background: #1a1a2e; color: #ffa533; }
        .table tbody tr { color: #fff; }
        .table tbody tr.unread { background: #2d2d4a; font-weight: 600; border-left: 5px solid #ffa533; }
        .table tbody tr.read { background: #23234a; }
        .table td, .table th { vertical-align: middle; }
        .badge-type { font-size: 0.95em; }
        .pagination .page-link { color: #ffa533; background: #23234a; border: none; }
        .pagination .active .page-link { background: #ffa533; color: #23234a; }
        .btn-mark-read { font-size: 0.95em; }
        @media (max-width: 900px) { .notifications-container { margin-left: 70px; padding: 18px 4px; } }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="notifications-container">
    <div class="notifications-title d-flex align-items-center justify-content-between">
        <span><i class="bi bi-bell-fill me-2"></i> Notifications</span>
        <a href="?readall=1" class="btn btn-sm btn-success"><i class="bi bi-check2-all me-1"></i> Mark all as read</a>
    </div>
    <div class="table-section mb-4">
        <div class="table-title"><i class="bi bi-bell me-2"></i> All Notifications</div>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $has_unread = false;
                    if ($res && mysqli_num_rows($res) > 0) {
                        while ($row = mysqli_fetch_assoc($res)) {
                            $is_unread = (isset($row['is_read']) && $row['is_read'] == 0);
                            if ($is_unread) $has_unread = true;
                            $badge_class = 'bg-secondary';
                            if ($row['type'] === 'reservation') $badge_class = 'bg-info';
                            if ($row['type'] === 'cancellation') $badge_class = 'bg-danger';
                            if ($row['type'] === 'signup') $badge_class = 'bg-primary';
                            echo '<tr class="' . ($is_unread ? 'unread' : 'read') . '">';
                            echo '<td><span class="badge badge-type ' . $badge_class . '">' . ucfirst($row['type']) . '</span></td>';
                            echo '<td>' . htmlspecialchars($row['message']) . '</td>';
                            echo '<td>' . date('M d, Y h:i A', strtotime($row['created_at'])) . '</td>';
                            echo '<td>' . ($is_unread ? '<span class="badge bg-warning text-dark">Unread</span>' : '<span class="badge bg-success">Read</span>') . '</td>';
                            echo '<td>';
                            if ($is_unread) {
                                echo '<span class="text-secondary">-</span>';
                            } else {
                                echo '<span class="text-secondary">-</span>';
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        if (!$has_unread) {
                            echo '<tr><td colspan="5" class="text-center text-success">All notifications are read.</td></tr>';
                        }
                    } else {
                        echo '<tr><td colspan="5" class="text-center text-secondary">No notifications found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 