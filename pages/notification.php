<?php
// functions/notification.php
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