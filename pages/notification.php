<?php

/**
 * Show a Bootstrap toast notification.
 * @param string $message
 * @param string $type (Bootstrap color: info, success, warning, danger, etc.)
 * @param int $delay (milliseconds)
 */
function show_toast($message, $type = 'info', $delay = 3000) {
    $type_class = [
        'info' => 'text-bg-info',
        'success' => 'text-bg-success',
        'warning' => 'text-bg-warning',
        'danger' => 'text-bg-danger',
        'primary' => 'text-bg-primary',
        'secondary' => 'text-bg-secondary',
        'light' => 'text-bg-light',
        'dark' => 'text-bg-dark',
    ];
    $class = isset($type_class[$type]) ? $type_class[$type] : $type_class['info'];
    ?>
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
      <div id="customToast" class="toast align-items-center <?php echo $class; ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?php echo $message; ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastEl = document.getElementById('customToast');
        if (toastEl) {
            var toast = new bootstrap.Toast(toastEl, { delay: <?php echo $delay; ?> });
            toast.show();
        }
    });
    </script>
    <?php
}

/**
 * Notify a guest about reservation status update (stub for email/logging).
 * @param int $reservation_id
 * @param string $new_status
 * @param string $guest_email
 */
function notify_status_update($reservation_id, $new_status, $guest_email) {
    // You can replace this with actual email logic or logging as needed
    // For now, just log to a file (logs/notifications.log)
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) mkdir($log_dir, 0777, true);
    $log_file = $log_dir . '/notifications.log';
    $msg = date('Y-m-d H:i:s') . " | Reservation #$reservation_id status changed to '$new_status' for $guest_email\n";
    file_put_contents($log_file, $msg, FILE_APPEND);
    // Optionally, send an email here
    // mail($guest_email, "Reservation Status Updated", $msg);
}

/**
 * Set a session notification for reservation denied.
 */
function notify_reservation_denied() {
    $_SESSION['error'] = 'Your reservation was denied by the admin.';
}

/**
 * Set a session notification for successful reservation.
 */
function notify_reservation_success() {
    $_SESSION['success'] = 'Your booking was successful!';
}

/**
 * Set a session notification for successful cancellation.
 */
function notify_reservation_cancelled() {
    $_SESSION['success'] = 'Your reservation was successfully cancelled.';
}

/**
 * Display notifications from the notifications.log file as a list.
 * @param int $limit Number of notifications to show (default 20)
 */
function show_log_notifications($limit = 20) {
    $log_file = __DIR__ . '/../logs/notifications.log';
    if (!file_exists($log_file)) {
        echo '<div class="alert alert-info">No notifications found.</div>';
        return;
    }
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // Show latest first
    if (empty($lines)) {
        echo '<div class="alert alert-info">No notifications found.</div>';
        return;
    }
    ?>
    <style>
    :root {
        --notif-bg: #23234a;
        --notif-header-bg: #2d2d5a;
        --notif-header-color: #ff8c00;
        --notif-text: #fff;
        --notif-date: #bdbdbd;
        --notif-card-shadow: 0 2px 12px rgba(0,0,0,0.12);
        --notif-border: 1px solid rgba(255,255,255,0.08);
    }
    body.light-mode {
        --notif-bg: #fff;
        --notif-header-bg: #f5f5f5;
        --notif-header-color: #ff8c00;
        --notif-text: #23234a;
        --notif-date: #666;
        --notif-card-shadow: 0 2px 12px rgba(0,0,0,0.06);
        --notif-border: 1px solid #eee;
    }
    body { 
        background: var(--notif-bg);
        color: var(--notif-text);
        font-family: 'Poppins', sans-serif; 
        transition: background 0.3s, color 0.3s;
    }
    .notif-card { 
        background: #23234a; 
        border-radius: 12px; 
        box-shadow: 0 2px 12px rgba(0,0,0,0.12); 
        margin-bottom: 18px; 
    }

    .notif-type-reservation { color: #FF8C00; }
    .notif-type-wallet { color: #00c896; }
    .notif-type-profile { color: #1e90ff; }
    .notif-type-payment { color: #00c896; }
    .notif-type-cancellation { color: #ff4d4d; }

    .notif-date { 
        font-size: 0.95em; 
        color: #bdbdbd; 
    }

    .notif-unread-new {
        border-left: 5px solid #FF8C00;
        background: linear-gradient(90deg, #2d2d5a 80%, #23234a 100%);
        box-shadow: 0 4px 18px rgba(255,140,0,0.10);
        position: relative;
    }

    .notification-badge {
        font-size: 0.85rem;
        padding: 0.5em 0.85em;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .notification-badge i {
        font-size: 0.9rem;
    }

    .notification-badge.badge-reservation {
        background: linear-gradient(90deg,#ffa533 60%,#ff8c00 100%);
        color: #23234a;
    }

    .notification-badge.badge-payment {
        background: linear-gradient(90deg,#00c896 60%,#1e90ff 100%);
        color: #fff;
    }

    .notification-badge.badge-profile {
        background: linear-gradient(90deg,#0d6efd 60%,#1e90ff 100%);
        color: #fff;
    }

    .notification-badge.badge-wallet {
        background: linear-gradient(90deg,#00c896 60%,#1e90ff 100%);
        color: #fff;
    }

    .notification-badge.badge-cancellation {
        background: linear-gradient(90deg,#ff4d4d 60%,#c0392b 100%);
        color: #fff;
    }

    .notif-gmail-card {
        background: var(--notif-bg);
        color: var(--notif-text);
        border-radius: 12px;
        box-shadow: var(--notif-card-shadow);
        max-width: 800px;
        margin: 30px auto 0 auto;
        padding: 0;
        border: var(--notif-border);
    }

    .notif-gmail-header {
        background: var(--notif-header-bg);
        border-bottom: var(--notif-border);
        padding: 18px 28px;
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--notif-header-color);
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: space-between;
    }

    .notif-gmail-header .mode-toggle-btn {
        background: none;
        border: none;
        color: var(--notif-header-color);
        font-size: 1.2rem;
        cursor: pointer;
        transition: color 0.3s;
    }

    .notif-gmail-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .notif-gmail-item {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 18px 28px;
        border-bottom: var(--notif-border);
        transition: background 0.18s;
    }

    .notif-gmail-item:last-child { 
        border-bottom: none; 
    }

    .notif-gmail-item:hover {
        background: var(--notif-header-bg);
    }

    .notif-gmail-icon {
        font-size: 1.5rem;
        color: #ff8c00;
        flex-shrink: 0;
    }

    .notif-gmail-content {
        flex: 1;
    }

    .notif-gmail-text {
        font-size: 1.05rem;
        color: var(--notif-text);
        word-break: break-word;
    }

    .notif-gmail-date {
        font-size: 0.98rem;
        color: var(--notif-date);
        margin-left: 10px;
        white-space: nowrap;
    }
    </style>
    <div class="container py-5">
        <div class="notif-gmail-card">
            <div class="notif-gmail-header">
                <span><i class="bi bi-bell-fill"></i> Notifications</span>
                <button class="mode-toggle-btn" id="modeToggleBtn" title="Toggle light/dark mode">
                    <i class="bi bi-moon"></i>
                </button>
            </div>
            <ul class="notif-gmail-list">
            <?php
            $count = 0;
            foreach ($lines as $line) {
                if ($count++ >= $limit) break;
                // Parse date and message
                if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \| (.+)$/', $line, $matches)) {
                    $date = date('M d, Y h:i A', strtotime($matches[1]));
                    $msg = $matches[2];
                    // Determine notification type from message
                    $type = 'notification';
                    if (stripos($msg, 'reservation') !== false) $type = 'reservation';
                    else if (stripos($msg, 'payment') !== false) $type = 'payment';
                    else if (stripos($msg, 'profile') !== false) $type = 'profile';
                    else if (stripos($msg, 'wallet') !== false) $type = 'wallet';
                    else if (stripos($msg, 'cancel') !== false) $type = 'cancellation';
                } else {
                    $date = '';
                    $msg = $line;
                    $type = 'notification';
                }
                ?>
                <li class="notif-gmail-item">
                    <span class="notif-gmail-icon">
                        <?php if ($type === 'reservation'): ?>
                            <i class="bi bi-calendar2-check"></i>
                        <?php elseif ($type === 'payment'): ?>
                            <i class="bi bi-credit-card"></i>
                        <?php elseif ($type === 'profile'): ?>
                            <i class="bi bi-person-circle"></i>
                        <?php elseif ($type === 'wallet'): ?>
                            <i class="bi bi-wallet2"></i>
                        <?php elseif ($type === 'cancellation'): ?>
                            <i class="bi bi-x-circle"></i>
                        <?php else: ?>
                            <i class="bi bi-bell"></i>
                        <?php endif; ?>
                    </span>
                    <div class="notif-gmail-content">
                        <?php echo get_notification_type_badge($type); ?>
                        <span class="fw-bold me-2 notif-type-<?php echo htmlspecialchars($type); ?>" style="letter-spacing:1px;">
                            <?php echo strtoupper(htmlspecialchars($type)); ?>
                        </span>
                        <div class="notif-gmail-text mt-2"><?php echo htmlspecialchars($msg); ?></div>
                    </div>
                    <span class="notif-gmail-date"><?php echo htmlspecialchars($date); ?></span>
                </li>
                <?php
            }
            ?>
            </ul>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Theme toggle logic
    function setTheme(mode) {
        if (mode === 'light') {
            document.body.classList.add('light-mode');
            document.getElementById('modeToggleBtn').innerHTML = '<i class="bi bi-brightness-high"></i>';
        } else {
            document.body.classList.remove('light-mode');
            document.getElementById('modeToggleBtn').innerHTML = '<i class="bi bi-moon"></i>';
        }
        localStorage.setItem('notifTheme', mode);
    }
    function getPreferredTheme() {
        if (localStorage.getItem('notifTheme')) {
            return localStorage.getItem('notifTheme');
        }
        return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
    }
    document.addEventListener('DOMContentLoaded', function() {
        setTheme(getPreferredTheme());
        document.getElementById('modeToggleBtn').addEventListener('click', function() {
            const isLight = document.body.classList.contains('light-mode');
            setTheme(isLight ? 'dark' : 'light');
        });
    });
    </script>
    <?php
}

/**
 * Get unread notification counts for a user
 * @param int $user_id The ID of the user
 * @return array Array containing counts for different notification types
 */
function get_unread_notification_counts($user_id) {
    global $mycon;
    $counts = [
        'reservation' => 0,
        'payment' => 0
    ];
    
    if ($user_id) {
        $res_count = mysqli_query($mycon, "SELECT type, COUNT(*) as cnt 
            FROM notifications 
            WHERE recipient_id = $user_id 
            AND recipient_type = 'user' 
            AND is_read = 0 
            AND type IN ('reservation','payment') 
            GROUP BY type");
            
        if ($res_count) {
            while ($row = mysqli_fetch_assoc($res_count)) {
                $counts[$row['type']] = $row['cnt'];
            }
        }
    }
    return $counts;
}

/**
 * Display notification badges for unread notifications
 * @param int $user_id The ID of the user
 */
function show_notification_badges($user_id) {
    $counts = get_unread_notification_counts($user_id);
    if ($counts['reservation'] > 0) {
        echo '<span class="notification-badge badge-reservation"><i class="bi bi-calendar-check"></i>RESERVATION: ' . $counts['reservation'] . '</span>';
    }
    if ($counts['payment'] > 0) {
        echo '<span class="notification-badge badge-payment"><i class="bi bi-credit-card"></i>PAYMENT: ' . $counts['payment'] . '</span>';
    }
}

/**
 * Get badge HTML for a specific notification type
 * @param string $type The notification type
 * @return string The HTML for the badge
 */
function get_notification_type_badge($type) {
    $badges = [
        'reservation' => [
            'class' => 'badge-reservation',
            'icon' => 'bi-calendar-check',
            'text' => 'RESERVATION'
        ],
        'payment' => [
            'class' => 'badge-payment',
            'icon' => 'bi-credit-card',
            'text' => 'PAYMENT'
        ],
        'profile' => [
            'class' => 'badge-profile',
            'icon' => 'bi-person',
            'text' => 'PROFILE'
        ],
        'wallet' => [
            'class' => 'badge-wallet',
            'icon' => 'bi-wallet2',
            'text' => 'WALLET'
        ],
        'cancellation' => [
            'class' => 'badge-cancellation',
            'icon' => 'bi-x-circle',
            'text' => 'CANCELLATION'
        ]
    ];

    if (!isset($badges[$type])) {
        return '<span class="notification-badge badge-secondary"><i class="bi bi-bell"></i>NOTIFICATION</span>';
    }

    $badge = $badges[$type];
    return sprintf(
        '<span class="notification-badge %s"><i class="bi %s"></i>%s</span>',
        $badge['class'],
        $badge['icon'],
        $badge['text']
    );
}

// --- Notification Page Entry Point ---
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
    <?php show_log_notifications(); ?>
</body>
</html>
<?php
} 