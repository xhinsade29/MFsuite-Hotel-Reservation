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
    .notif-gmail-card {
        background: #fff;
        color: #23234a;
        border-radius: 10px;
        box-shadow: 0 2px 12px rgba(31, 38, 135, 0.08);
        max-width: 600px;
        margin: 30px auto 0 auto;
        padding: 0;
        border: 1px solid #eee;
    }
    .notif-gmail-header {
        background: #f5f5f5;
        border-bottom: 1px solid #eee;
        padding: 18px 28px;
        font-size: 1.3rem;
        font-weight: 600;
        color: #ff8c00;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .notif-gmail-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .notif-gmail-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 18px 28px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.18s;
        cursor: pointer;
    }
    .notif-gmail-item:last-child { border-bottom: none; }
    .notif-gmail-item:hover {
        background: #f9f6f2;
    }
    .notif-gmail-icon {
        font-size: 1.5rem;
        color: #ff8c00;
        flex-shrink: 0;
    }
    .notif-gmail-text {
        flex: 1;
        font-size: 1.05rem;
        color: #23234a;
        word-break: break-word;
    }
    .notif-gmail-date {
        font-size: 0.98rem;
        color: #888;
        margin-left: 10px;
        white-space: nowrap;
    }
    </style>
    <div class="notif-gmail-card">
        <div class="notif-gmail-header"><i class="bi bi-bell-fill"></i> Notifications</div>
        <ul class="notif-gmail-list">
        <?php
        $count = 0;
        foreach ($lines as $line) {
            if ($count++ >= $limit) break;
            // Parse date and message
            if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}) \| (.+)$/', $line, $matches)) {
                $date = date('M d, Y h:i A', strtotime($matches[1]));
                $msg = $matches[2];
            } else {
                $date = '';
                $msg = $line;
            }
            echo '<li class="notif-gmail-item">';
            echo '<span class="notif-gmail-icon"><i class="bi bi-envelope-open"></i></span>';
            echo '<span class="notif-gmail-text">' . htmlspecialchars($msg) . '</span>';
            echo '<span class="notif-gmail-date">' . htmlspecialchars($date) . '</span>';
            echo '</li>';
        }
        ?>
        </ul>
    </div>
    <?php
}

function add_notification($guest_id, $type, $message, $mycon) {
    $sql = "INSERT INTO user_notifications (guest_id, type, message) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($mycon, $sql);
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($mycon));
        return;
    }
    mysqli_stmt_bind_param($stmt, "iss", $guest_id, $type, $message);
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execute failed: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
}

// --- Notification Page Entry Point ---
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>body { background: #f6f8fa; min-height: 100vh; }</style>
</head>
<body>
    <?php show_log_notifications(); ?>
</body>
</html>
<?php
} 