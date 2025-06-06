<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit();
}
include('../functions/db_connect.php');

$guest_id = $_SESSION['guest_id'];

// --- Sorting/filter logic ---
$sort = $_GET['sort'] ?? 'newest';
$order_by = 'created_at DESC';
$type_filter = '';
$params = [$guest_id];
$types = 'i';
if ($sort === 'oldest') {
    $order_by = 'created_at ASC';
} elseif (in_array($sort, ['reservation', 'payment', 'wallet', 'profile'])) {
    $type_filter = ' AND type = ?';
    $params[] = $sort;
    $types .= 's';
}

// Mark all unread notifications as read
$update_sql = "UPDATE user_notifications SET is_read = 1 WHERE guest_id = ? AND is_read = 0";
$update_stmt = $mycon->prepare($update_sql);
$update_stmt->bind_param("i", $guest_id);
$update_stmt->execute();
$update_stmt->close();

// Fetch all notifications for the user (with sorting/filter)
$sql = "SELECT user_notication_id AS notification_id, type AS title, message, created_at AS date_created, is_read, related_id AS reservation_id FROM user_notifications WHERE guest_id = ?$type_filter ORDER BY $order_by";
$stmt = $mycon->prepare($sql);
if ($types && count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
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
        /* Light mode overrides */
        body.light-mode {
            background: #f8f9fa !important;
            color: #23234a !important;
        }
        body.light-mode .container {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode h2 {
            color: #ff8c00 !important;
        }
        body.light-mode .notif-card {
            background: #f7f7fa !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
            box-shadow: 0 4px 20px rgba(255,140,0,0.07);
        }
        body.light-mode .notif-unread-new {
            border-left: 5px solid #ff8c00 !important;
            background: linear-gradient(90deg, #ffe5b4 80%, #fff 100%) !important;
            box-shadow: 0 4px 18px rgba(255,140,0,0.10);
        }
        body.light-mode .notif-type-reservation {
            color: #ff8c00 !important;
        }
        body.light-mode .notif-type-wallet {
            color: #00c896 !important;
        }
        body.light-mode .notif-type-profile {
            color: #1e90ff !important;
        }
        body.light-mode .notif-date {
            color: #888 !important;
        }
        body.light-mode .badge {
            background: linear-gradient(90deg,#ff8c00 60%,#ffa533 100%) !important;
            color: #fff !important;
        }
        body.light-mode .alert-info {
            background: #ffe5b4 !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .btn-primary {
            background: linear-gradient(90deg, #ff8c00, #ffa533) !important;
            color: #fff !important;
            border: none !important;
        }
        body.light-mode .form-select, body.light-mode input, body.light-mode textarea {
            background: #fff !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .form-select:focus, body.light-mode input:focus, body.light-mode textarea:focus {
            border-color: #ff8c00 !important;
            box-shadow: 0 0 0 0.12rem rgba(255,140,0,0.13);
        }
        /* End light mode overrides */
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
        .notification-card-link {
            text-decoration: none;
            color: inherit;
        }
        .notification-card.clickable:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-left-color: #ff8c00;
        }
        .notification-card.clickable {
            cursor: pointer;
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out, border-left-color 0.2s ease-in-out;
        }
    </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
<?php include '../components/user_navigation.php'; ?>
<?php if (isset($_SESSION['success'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1200;">
  <div class="toast align-items-center text-bg-success border-0 show" id="successToast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3500">
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var toastEl = document.getElementById('successToast');
  if (toastEl) {
    var toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
  }
});
</script>
<?php endif; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-4 text-warning"><i class="bi bi-bell-fill"></i> My Notifications</h2>
        <span class="badge bg-danger" id="notifTrashBadge" style="font-size:1em;display:inline-block;vertical-align:middle;">
            Trash (<span id="notifTrashCount">0</span>)
        </span>
    </div>
    <form method="get" class="mb-3 d-flex align-items-center gap-2">
        <label for="sort" class="form-label mb-0">Sort/Filter by:</label>
        <select name="sort" id="sort" class="form-select w-auto" onchange="this.form.submit()">
            <option value="newest" <?php if ($sort === 'newest') echo 'selected'; ?>>Newest first</option>
            <option value="oldest" <?php if ($sort === 'oldest') echo 'selected'; ?>>Oldest first</option>
            <option value="reservation" <?php if ($sort === 'reservation') echo 'selected'; ?>>Reservation</option>
            <option value="payment" <?php if ($sort === 'payment') echo 'selected'; ?>>Payment</option>
            <option value="wallet" <?php if ($sort === 'wallet') echo 'selected'; ?>>Wallet</option>
            <option value="profile" <?php if ($sort === 'profile') echo 'selected'; ?>>Profile</option>
        </select>
    </form>
    <div class="notification-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <?php
                    $is_clickable = !empty($row['reservation_id']);
                    $card_class = $is_clickable ? 'clickable' : '';
                    $link_href = $is_clickable ? 'reservation_details.php?id=' . $row['reservation_id'] : '#';
                    $tag_open = $is_clickable ? '<a href="' . $link_href . '" class="notification-card-link">' : '';
                    $tag_close = $is_clickable ? '</a>' : '';
                ?>
                <?php echo $tag_open; ?>
                <div class="notification-card <?php echo $card_class; ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-bell-fill notification-icon me-3"></i>
                        <div class="notification-content">
                            <h5 class="notification-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                            <p class="notification-message"><?php echo htmlspecialchars($row['message']); ?></p>
                            <small class="notification-date text-muted"><?php echo date('F j, Y, g:i a', strtotime($row['date_created'])); ?></small>
                        </div>
                    </div>
                </div>
                <?php echo $tag_close; ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">No notifications yet.</div>
        <?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fetch trash count from reservations page or via AJAX
    fetch('reservations.php?get_trash_count=1')
        .then(response => response.json())
        .then(data => {
            if (data && typeof data.trash_count !== 'undefined') {
                document.getElementById('notifTrashCount').textContent = data.trash_count;
            }
        });
});
</script>
<script>
function updateUserNotifBadge() {
    fetch('notifications.php?count=1')
        .then(response => response.json())
        .then(data => {
            var badge = document.getElementById('userNotifBadge');
            if (badge) {
                if (data && data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = '';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
}
setInterval(updateUserNotifBadge, 10000);
document.addEventListener('DOMContentLoaded', updateUserNotifBadge);
</script>
</body>
</html> 