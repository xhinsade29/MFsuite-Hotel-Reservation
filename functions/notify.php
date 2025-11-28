<?php
function add_notification($guest_id, $type, $message, $mycon) {
    $sql = "INSERT INTO user_notifications (guest_id, type, message) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($mycon, $sql);
    if (!$stmt) {
        error_log("Notification INSERT prepare failed: " . mysqli_error($mycon));
        return;
    }
    // Set created_at to NOW() by default in the table schema or SQL
    // The current schema has a default of current_timestamp(), so no need to explicitly set it here.
    
    // Bind parameters
    // Check if guest_id is valid before binding (basic check)
    if (!is_numeric($guest_id) || $guest_id <= 0) {
        error_log("Invalid guest_id provided to add_notification: " . $guest_id);
        // Optionally insert a notification without guest_id if you have a system user or similar
        return;
    }
    
    mysqli_stmt_bind_param($stmt, "iss", $guest_id, $type, $message);
    
    // Execute statement
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Notification INSERT execute failed: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
}

// You can add other notification-related functions here later 