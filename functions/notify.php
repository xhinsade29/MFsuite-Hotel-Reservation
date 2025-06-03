<?php
function add_notification($recipient_id, $type, $message, $mycon, $is_read = 0, $admin_id = 1) {
    if ($type === 'admin') {
        // Admin notification: guest_id should be NULL, admin_id should be set
        $sql = "INSERT INTO user_notifications (guest_id, type, message, is_read, admin_id) VALUES (NULL, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($mycon, $sql);
        if (!$stmt) {
            error_log("Notification INSERT prepare failed: " . mysqli_error($mycon));
            return;
        }
        mysqli_stmt_bind_param($stmt, "ssii", $type, $message, $is_read, $recipient_id); // $recipient_id is admin_id here
    } else {
        // Guest notification: guest_id is set, admin_id is set
        $sql = "INSERT INTO user_notifications (guest_id, type, message, is_read, admin_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($mycon, $sql);
        if (!$stmt) {
            error_log("Notification INSERT prepare failed: " . mysqli_error($mycon));
            return;
        }
        mysqli_stmt_bind_param($stmt, "issii", $recipient_id, $type, $message, $is_read, $admin_id);
    }
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Notification INSERT execute failed: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
}

// You can add other notification-related functions here later 