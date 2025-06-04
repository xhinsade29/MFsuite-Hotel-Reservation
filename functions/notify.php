<?php
function add_notification($recipient_id, $recipient_type, $type, $message, $mycon, $is_read = 0, $associated_admin_id = null, $related_id = null) {
    if ($recipient_type === 'admin') {
        // Insert into admin_notifications table
        $sql = "INSERT INTO admin_notifications (admin_id, type, message, is_read, related_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($mycon, $sql);
        if (!$stmt) {
            error_log("Admin Notification INSERT prepare failed: " . mysqli_error($mycon));
            return false; // Return false on failure
        }
        // Bind parameters: admin_id, type, message, is_read, related_id
        mysqli_stmt_bind_param($stmt, "issii", $recipient_id, $type, $message, $is_read, $related_id);

    } else { // Assuming 'user' or 'guest'
        // Insert into user_notifications table
        // The user_notifications table structure included guest_id and admin_id
        // $recipient_id is the guest_id here
        // $associated_admin_id is the admin_id potentially responsible or related
        $sql = "INSERT INTO user_notifications (guest_id, type, message, is_read, admin_id, related_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($mycon, $sql);
        if (!$stmt) {
            error_log("User Notification INSERT prepare failed: " . mysqli_error($mycon));
            return false; // Return false on failure
        }
        // Bind parameters: guest_id, type, message, is_read, admin_id, related_id
        mysqli_stmt_bind_param($stmt, "issiii", $recipient_id, $type, $message, $is_read, $associated_admin_id, $related_id);
    }

    $success = mysqli_stmt_execute($stmt);
    if (!$success) {
        error_log("Notification INSERT execute failed: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);
    return $success; // Return true on success, false on failure
}

// You can add other notification-related functions here later 