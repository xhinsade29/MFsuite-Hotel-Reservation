<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
// include '../pages/notification.php';
include '../functions/db_connect.php';
include '../functions/notify.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $new_status = $_POST['new_status'] ?? '';
    $allowed = ['pending','approved','cancelled','denied','completed','cancellation_requested'];

    if ($reservation_id) {
        // Get reservation, guest, and payment info
        $sql = "SELECT r.*, g.guest_id, g.first_name, g.last_name, g.user_email, g.wallet_balance, p.payment_id, p.amount, p.payment_status, p.payment_method, p.reference_number FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE r.reservation_id = ?";
        $stmt = mysqli_prepare($mycon, $sql);
        mysqli_stmt_bind_param($stmt, "i", $reservation_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$row) {
            header("Location: dashboard.php?msg=Reservation+not+found");
            exit();
        }

        $guest_id = $row['guest_id'];
        $guest_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $payment_id = $row['payment_id'];
        $amount = $row['amount'];
        $payment_status = $row['payment_status'];
        $payment_method = $row['payment_method'];
        $user_email = $row['user_email'];
        $ref = $row['reference_number'] ?? '';

        $admin_id = 1; // Use your default or actual admin_id here

        // Deny action
        if ($action === 'deny' && in_array($new_status, $allowed)) {
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = ? WHERE reservation_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new_status, $reservation_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            add_notification($guest_id, 'guest', 'Your reservation has been denied by admin.', $mycon, 0, $admin_id);
            // Admin notification
            add_notification($_SESSION['admin_id'], 'admin', 'Reservation #'.$reservation_id.' ('.$guest_name.') has been denied.', $mycon, 0, $admin_id);

            header("Location: reservations.php?msg=Reservation+denied+successfully");
            exit();
        }

        // Refund action
        if ($action === 'refund' && $payment_status === 'Paid' && $payment_method !== 'Cash') {
            // 1. Update payment status to Refunded
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_payment SET payment_status = 'Refunded' WHERE payment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 2. Credit user's wallet
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_guest SET wallet_balance = wallet_balance + ? WHERE guest_id = ?");
            mysqli_stmt_bind_param($stmt, "di", $amount, $guest_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 3. Log wallet transaction
            $desc = "Refund for reservation #$reservation_id";
            $log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'refund', ?, ?, ?)";
            $log_stmt = mysqli_prepare($mycon, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "idsss", $guest_id, $amount, $desc, $payment_method, $ref);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);

            // 4. Notify user
            add_notification($guest_id, 'guest', "Refunded ₱" . number_format($amount, 2) . " to your wallet for reservation #$reservation_id.", $mycon, 0, $admin_id);
            // Admin notification
            add_notification($_SESSION['admin_id'], 'admin', 'Refund processed for reservation #'.$reservation_id.' ('.$guest_name.').', $mycon, 0, $admin_id);

            header("Location: reservations.php?msg=Refund+processed+successfully");
            exit();
        }

        // Approve Reservation action
        if ($action === 'approve_reservation') {
            $approved_status = 'approved';
            $room_type_id = $row['room_id'];
            $check_in = $row['check_in'];
            $check_out = $row['check_out'];
            $find_room_sql = "SELECT r.room_id FROM tbl_room r WHERE r.room_type_id = ? AND r.status = 'Available' AND r.room_id NOT IN (
                SELECT res.assigned_room_id FROM tbl_reservation res WHERE res.check_in < ? AND res.check_out > ? AND res.status IN ('pending','approved','completed') AND res.assigned_room_id IS NOT NULL
            ) LIMIT 1";
            $stmt_find = mysqli_prepare($mycon, $find_room_sql);
            if (!$stmt_find) {
                 error_log("Find room prepare failed for reservation #{$reservation_id}: " . mysqli_error($mycon));
                 add_notification($_SESSION['admin_id'], 'admin', 'Room Assignment Failed', 'Database error preparing room query for reservation #'.$reservation_id.'.', $mycon, 0, $admin_id);
                 header("Location: reservations.php?msg=Database+error+finding+room");
                 exit();
            }
            mysqli_stmt_bind_param($stmt_find, "iss", $room_type_id, $check_out, $check_in);
            if (!mysqli_stmt_execute($stmt_find)) {
                 error_log("Find room execute failed for reservation #{$reservation_id}: " . mysqli_stmt_error($stmt_find));
                 mysqli_stmt_close($stmt_find); // Close statement before exiting
                 add_notification($_SESSION['admin_id'], 'admin', 'Room Assignment Failed', 'Database error executing room query for reservation #'.$reservation_id.'.', $mycon, 0, $admin_id);
                 header("Location: reservations.php?msg=Database+error+finding+room");
                 exit();
            }
            mysqli_stmt_bind_result($stmt_find, $assigned_room_id);
            $assigned_room_id = null;
            if (mysqli_stmt_fetch($stmt_find)) {
                error_log("Room found for reservation #{$reservation_id}: Room ID = " . $assigned_room_id);
                mysqli_stmt_close($stmt_find);
                $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = ?, assigned_room_id = ? WHERE reservation_id = ?");
                mysqli_stmt_bind_param($stmt, "sii", $approved_status, $assigned_room_id, $reservation_id);
                if (!mysqli_stmt_execute($stmt)) {
                    error_log("tbl_reservation update failed for reservation #{$reservation_id}. Error: " . mysqli_stmt_error($stmt));
                    mysqli_stmt_close($stmt);
                    add_notification($_SESSION['admin_id'], 'admin', 'Reservation Update Failed', 'Database error updating reservation #'.$reservation_id.'.', $mycon, 0, $admin_id);
                    header("Location: reservations.php?msg=Database+error+updating+reservation");
                    exit();
                }
                error_log("tbl_reservation update successful for reservation #{$reservation_id}.");
                mysqli_stmt_close($stmt);
                // Mark the room as occupied
                $stmt = mysqli_prepare($mycon, "UPDATE tbl_room SET status = 'Occupied' WHERE room_id = ?");
                mysqli_stmt_bind_param($stmt, "i", $assigned_room_id);
                 if (!mysqli_stmt_execute($stmt)) {
                     error_log("tbl_room status update failed for room #{$assigned_room_id}. Error: " . mysqli_stmt_error($stmt));
                     mysqli_stmt_close($stmt);
                     add_notification($_SESSION['admin_id'], 'admin', 'Room Status Update Failed', 'Database error updating room status for room #'.$assigned_room_id.'.', $mycon, 0, $admin_id);
                     header("Location: reservations.php?msg=Database+error+updating+room");
                     exit();
                 }
                error_log("tbl_room status update successful for room #{$assigned_room_id}.");
                mysqli_stmt_close($stmt);
                add_notification($guest_id, 'guest', 'Your reservation has been approved and a room has been assigned.', $mycon, 0, $admin_id);
                // Admin notification
                add_notification($_SESSION['admin_id'], 'admin', 'Reservation #'.$reservation_id.' ('.$guest_name.') has been approved.', $mycon, 0, $admin_id);
                header("Location: reservations.php?msg=Reservation+approved+successfully");
                exit();
            } else {
                error_log("No available room found for reservation #{$reservation_id}.");
                // Admin notification
                add_notification($_SESSION['admin_id'], 'admin', 'No available room for this type and date for reservation #'.$reservation_id.'.', $mycon, 0, $admin_id);
                header("Location: reservations.php?msg=No+available+room+for+this+type+and+date");
                exit();
            }
        }
        // Approve Payment action
        if ($action === 'approve_payment' && $payment_id) {
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_payment SET payment_status = 'Paid' WHERE payment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            // If payment method is Cash, assign a room and set reservation to approved
            if ($payment_method === 'Cash') {
                $room_type_id = $row['room_id'];
                $check_in = $row['check_in'];
                $check_out = $row['check_out'];
                $find_room_sql = "SELECT r.room_id FROM tbl_room r WHERE r.room_type_id = ? AND r.status = 'Available' AND r.room_id NOT IN (
                    SELECT res.assigned_room_id FROM tbl_reservation res WHERE res.check_in < ? AND res.check_out > ? AND res.status IN ('pending','approved','completed') AND res.assigned_room_id IS NOT NULL
                ) LIMIT 1";
                $stmt_find = mysqli_prepare($mycon, $find_room_sql);
                 if (!$stmt_find) {
                     error_log("Find room prepare failed for reservation #{$reservation_id} (Cash Payment): " . mysqli_error($mycon));
                     add_notification($_SESSION['admin_id'], 'admin', 'Room Assignment Failed', 'Database error preparing room query for reservation #'.$reservation_id.' (Cash Payment).', $mycon, 0, $admin_id);
                     header("Location: reservations.php?msg=Database+error+finding+room");
                     exit();
                 }
                mysqli_stmt_bind_param($stmt_find, "iss", $room_type_id, $check_out, $check_in);
                 if (!mysqli_stmt_execute($stmt_find)) {
                     error_log("Find room execute failed for reservation #{$reservation_id} (Cash Payment): " . mysqli_stmt_error($stmt_find));
                     mysqli_stmt_close($stmt_find); // Close statement before exiting
                     add_notification($_SESSION['admin_id'], 'admin', 'Room Assignment Failed', 'Database error executing room query for reservation #'.$reservation_id.' (Cash Payment).', $mycon, 0, $admin_id);
                     header("Location: reservations.php?msg=Database+error+finding+room");
                     exit();
                 }
                mysqli_stmt_bind_result($stmt_find, $assigned_room_id_fetched);
                $assigned_room_id = null;
                if (mysqli_stmt_fetch($stmt_find) && $assigned_room_id_fetched) {
                    error_log("Room found for reservation #{$reservation_id} (Cash Payment): Room ID = " . $assigned_room_id_fetched);
                    mysqli_stmt_close($stmt_find);
                    // Use the fetched room ID
                    $assigned_room_id = $assigned_room_id_fetched;
                    $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = 'approved', assigned_room_id = ? WHERE reservation_id = ?");
                    mysqli_stmt_bind_param($stmt, "ii", $assigned_room_id, $reservation_id);
                     if (!mysqli_stmt_execute($stmt)) {
                         error_log("tbl_reservation update failed for reservation #{$reservation_id} (Cash Payment). Error: " . mysqli_stmt_error($stmt));
                         mysqli_stmt_close($stmt);
                         add_notification($_SESSION['admin_id'], 'admin', 'Reservation Update Failed', 'Database error updating reservation #'.$reservation_id.' (Cash Payment).', $mycon, 0, $admin_id);
                         header("Location: reservations.php?msg=Database+error+updating+reservation");
                         exit();
                     }
                    error_log("tbl_reservation update successful for reservation #{$reservation_id} (Cash Payment).");
                    mysqli_stmt_close($stmt);
                    // Mark the room as occupied
                    $stmt = mysqli_prepare($mycon, "UPDATE tbl_room SET status = 'Occupied' WHERE room_id = ?");
                    mysqli_stmt_bind_param($stmt, "i", $assigned_room_id);
                     if (!mysqli_stmt_execute($stmt)) {
                          error_log("tbl_room status update failed for room #{$assigned_room_id} (Cash Payment). Error: " . mysqli_stmt_error($stmt));
                          mysqli_stmt_close($stmt);
                          add_notification($_SESSION['admin_id'], 'admin', 'Room Status Update Failed', 'Database error updating room status for room #'.$assigned_room_id.' (Cash Payment).', $mycon, 0, $admin_id);
                          header("Location: reservations.php?msg=Database+error+updating+room");
                          exit();
                     }
                     error_log("tbl_room status update successful for room #{$assigned_room_id} (Cash Payment).");
                    mysqli_stmt_close($stmt);
                    add_notification($guest_id, 'guest', 'Your reservation has been approved and a room has been assigned.', $mycon, 0, $admin_id);
                    // Admin notification
                    add_notification($_SESSION['admin_id'], 'admin', 'Reservation #'.$reservation_id.' ('.$guest_name.') has been approved.', $mycon, 0, $admin_id);
                } else {
                    mysqli_stmt_close($stmt_find);
                    error_log("No available room found for reservation #{$reservation_id} (Cash Payment).");
                    // Admin notification
                    add_notification($_SESSION['admin_id'], 'admin', 'No available room for this type and date for reservation #'.$reservation_id.' (Cash Payment).', $mycon, 0, $admin_id);
                    header("Location: reservations.php?msg=No+available+room+for+this+type+and+date");
                    exit();
                }
            } else {
                // Auto-approve reservation if payment method is not Cash
                $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = 'approved' WHERE reservation_id = ?");
                mysqli_stmt_bind_param($stmt, "i", $reservation_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            // Notify for wallet top-up if payment method is a top-up type
            $topup_methods = ['GCash', 'Bank Transfer', 'PayPal', 'Credit Card'];
            if (in_array($payment_method, $topup_methods)) {
                add_notification($guest_id, 'guest', 'Your wallet top-up via ' . $payment_method . ' has been received and credited. Reference: ' . $ref, $mycon, 0, $admin_id);
            } else {
                $receipt_btn = '<a href="../admin/process_view_receipt.php?payment_id=' . $payment_id . '" class="btn btn-sm btn-primary mt-2" target="_blank">See Receipt</a>';
                add_notification($guest_id, 'guest', 'Your payment for the reservation has been approved. ' . $receipt_btn, $mycon, 0, $admin_id);
            }
            // Admin notification
            add_notification($_SESSION['admin_id'], 'admin', 'Payment for reservation #'.$reservation_id.' ('.$guest_name.') has been approved.', $mycon, 0, $admin_id);
            header("Location: reservations.php?msg=Payment+approved+successfully");
            exit();
        }
        // Approve Cancellation action
        if ($action === 'approve_cancellation') {
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = 'cancelled' WHERE reservation_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $reservation_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Add notifications
            add_notification($guest_id, 'guest', 'Your cancellation request for reservation #'.$reservation_id.' has been approved.', $mycon, 0, $admin_id);
            add_notification($_SESSION['admin_id'], 'admin', 'You have approved the reservation cancellation by '.$guest_name.'.', $mycon, 0, $admin_id);

            // Handle potential refund logic here if applicable (based on your business rules)
            // For now, assuming refund is a separate action or not automatic upon cancellation approval

            header("Location: reservations.php?msg=Cancellation+approved+successfully");
            exit();
        }
        // Restrict marking as completed to after checkout
        if ($action === 'complete' && in_array($new_status, $allowed)) {
            $now = date('Y-m-d H:i:s');
            if ($now < $row['check_out']) {
                header("Location: reservations.php?msg=Cannot+mark+as+completed+before+checkout+time");
                exit();
            }
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = 'completed' WHERE reservation_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $reservation_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            add_notification($guest_id, 'guest', 'Your reservation has been marked as completed.', $mycon, 0, $admin_id);
            // Admin notification
            add_notification($_SESSION['admin_id'], 'admin', 'Reservation #'.$reservation_id.' ('.$guest_name.') has been marked as completed.', $mycon, 0, $admin_id);
            header("Location: reservations.php?msg=Reservation+marked+as+completed");
            exit();
        }
    }
}
header("Location: dashboard.php?msg=Invalid+request");
exit(); 