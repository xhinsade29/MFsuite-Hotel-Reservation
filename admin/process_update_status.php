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

    if ($reservation_id && in_array($action, ['approve', 'deny', 'refund', 'complete'])) {
        // Get reservation, guest, and payment info
        $sql = "SELECT r.*, g.guest_id, g.first_name, g.last_name, g.user_email, p.payment_id, p.amount, p.payment_method, p.payment_status, p.reference_number FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE r.reservation_id = ?";
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

            add_notification($guest_id, 'user', 'reservation', 'Your reservation has been denied by admin.', $mycon, 0, $admin_id, $reservation_id);
            add_notification($_SESSION['admin_id'], 'admin', 'reservation', 'Reservation #'.$reservation_id.' ('.$guest_name.') has been denied.', $mycon, 0, null, $reservation_id);

            header("Location: reservations.php?msg=Reservation+denied+successfully");
            exit();
        }

        // Refund action
        if ($action === 'refund' && $payment_status === 'Paid' && $payment_method !== 'Cash') {
            // Get current admin wallet balance from admin_payment_accounts
            $admin_wallet_balance = 0;
            $stmt_admin_wallet = mysqli_prepare($mycon, "SELECT balance FROM admin_payment_accounts WHERE admin_id = ? AND account_type = 'wallet' LIMIT 1");
            if ($stmt_admin_wallet) {
                mysqli_stmt_bind_param($stmt_admin_wallet, "i", $admin_id);
                mysqli_stmt_execute($stmt_admin_wallet);
                mysqli_stmt_bind_result($stmt_admin_wallet, $fetched_admin_balance);
                if (mysqli_stmt_fetch($stmt_admin_wallet)) {
                    $admin_wallet_balance = $fetched_admin_balance;
                }
                mysqli_stmt_close($stmt_admin_wallet);
            } else {
                error_log("Failed to prepare statement for admin wallet balance in process_update_status: " . mysqli_error($mycon));
            }

            if ($admin_wallet_balance < $amount) {
                // Auto top-up admin wallet with the required amount
                $topup_amount = $amount - $admin_wallet_balance;
                $stmt = mysqli_prepare($mycon, "UPDATE admin_payment_accounts SET balance = balance + ? WHERE admin_id = ? AND account_type = 'wallet'");
                mysqli_stmt_bind_param($stmt, "di", $topup_amount, $admin_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                // Optionally, log this auto top-up
                $desc = "Auto top-up for refund for reservation #$reservation_id";
                $log_sql = "INSERT INTO wallet_transactions (admin_id, amount, type, description, payment_method) VALUES (?, ?, 'topup', ?, 'System')";
                $log_stmt = mysqli_prepare($mycon, $log_sql);
                mysqli_stmt_bind_param($log_stmt, "ids", $admin_id, $topup_amount, $desc);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            }

            // 1. Update payment status to Refunded
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_payment SET payment_status = 'Refunded' WHERE payment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 2. Credit user's wallet in guest_payment_accounts
            // First, check if the wallet account exists for the guest
            $stmt_check_wallet = mysqli_prepare($mycon, "SELECT account_id FROM guest_payment_accounts WHERE guest_id = ? AND account_type = 'wallet' LIMIT 1");
            mysqli_stmt_bind_param($stmt_check_wallet, "i", $guest_id);
            mysqli_stmt_execute($stmt_check_wallet);
            mysqli_stmt_bind_result($stmt_check_wallet, $guest_wallet_account_id);
            $has_guest_wallet_account = mysqli_stmt_fetch($stmt_check_wallet);
            mysqli_stmt_close($stmt_check_wallet);

            if ($has_guest_wallet_account) {
                // Update existing wallet balance
                $stmt = mysqli_prepare($mycon, "UPDATE guest_payment_accounts SET balance = balance + ? WHERE account_id = ?");
                mysqli_stmt_bind_param($stmt, "di", $amount, $guest_wallet_account_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            } else {
                // Create wallet account if it doesn't exist (should ideally exist from registration)
                error_log("WARNING: Guest wallet account not found for guest_id: " . $guest_id . ". Creating new wallet account.");
                $stmt = mysqli_prepare($mycon, "INSERT INTO guest_payment_accounts (guest_id, account_type, balance) VALUES (?, 'wallet', ?)");
                mysqli_stmt_bind_param($stmt, "id", $guest_id, $amount);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }

            // 2.1 Deduct from admin wallet (deduct from the specific admin payment account used for refund)
            $account_type_admin = strtolower($payment_method);
            $stmt_admin_deduct = mysqli_prepare($mycon, "UPDATE admin_payment_accounts SET balance = balance - ? WHERE admin_id = ? AND account_type = ?");
            mysqli_stmt_bind_param($stmt_admin_deduct, "dis", $amount, $admin_id, $account_type_admin);
            mysqli_stmt_execute($stmt_admin_deduct);
            mysqli_stmt_close($stmt_admin_deduct);

            // Log admin wallet transaction (debit)
            $admin_desc = "Refund issued for reservation #$reservation_id (Guest ID: $guest_id)";
            $admin_wallet_log_sql = "INSERT INTO wallet_transactions (admin_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'debit', ?, ?, ?)";
            $stmt_admin_log = mysqli_prepare($mycon, $admin_wallet_log_sql);
            mysqli_stmt_bind_param($stmt_admin_log, "idsss", $admin_id, $amount, $admin_desc, $payment_method, $ref);
            mysqli_stmt_execute($stmt_admin_log);
            mysqli_stmt_close($stmt_admin_log);

            // 3. Log wallet transaction
            $desc = "Refund for reservation #$reservation_id";
            $log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'refund', ?, ?, ?)";
            $log_stmt = mysqli_prepare($mycon, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "idsss", $guest_id, $amount, $desc, $payment_method, $ref);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);

            // 4. Notify user
            add_notification($guest_id, 'user', 'wallet', "Refunded ₱" . number_format($amount, 2) . " to your wallet for reservation #$reservation_id.", $mycon, 0, $admin_id, $reservation_id);
            add_notification($_SESSION['admin_id'], 'admin', 'payment', 'Refund processed for reservation #'.$reservation_id.' ('.$guest_name.').', $mycon, 0, null, $reservation_id);

            header("Location: reservations.php?msg=Refund+processed+successfully");
            exit();
        }

        // Approve Reservation action
        if ($action === 'approve_reservation') {
            $approved_status = 'approved';
            $room_type_id = $row['room_type_id'];
            $check_in = $row['check_in'];
            $check_out = $row['check_out'];
            error_log("[DEBUG] Approving reservation #$reservation_id for room_type_id=$room_type_id, check_in=$check_in, check_out=$check_out");
            $find_room_sql = "SELECT r.room_id FROM tbl_room r WHERE r.room_type_id = ? AND r.status = 'Available' AND r.room_id NOT IN (
                SELECT res.assigned_room_id FROM tbl_reservation res WHERE res.check_in < ? AND res.check_out > ? AND res.status IN ('pending','approved','completed') AND res.assigned_room_id IS NOT NULL
            ) LIMIT 1";
            $stmt_find = mysqli_prepare($mycon, $find_room_sql);
            if (!$stmt_find) {
                 error_log("[ERROR] Find room prepare failed for reservation #{$reservation_id}: " . mysqli_error($mycon));
                 add_notification($_SESSION['admin_id'], 'admin', 'system', 'Room Assignment Failed: Database error preparing room query for reservation #'.$reservation_id.'.', $mycon, 0, null, $reservation_id);
                 header("Location: reservations.php?msg=Database+error+finding+room");
                 exit();
            }
            mysqli_stmt_bind_param($stmt_find, "iss", $room_type_id, $check_out, $check_in);
            if (!mysqli_stmt_execute($stmt_find)) {
                 error_log("[ERROR] Find room execute failed for reservation #{$reservation_id}: " . mysqli_stmt_error($stmt_find));
                 mysqli_stmt_close($stmt_find); // Close statement before exiting
                 add_notification($_SESSION['admin_id'], 'admin', 'system', 'Room Assignment Failed: Database error executing room query for reservation #'.$reservation_id.'.', $mycon, 0, null, $reservation_id);
                 header("Location: reservations.php?msg=Database+error+finding+room");
                 exit();
            }
            mysqli_stmt_bind_result($stmt_find, $assigned_room_id);
            if (mysqli_stmt_fetch($stmt_find)) {
                error_log("[DEBUG] Room found for reservation #{$reservation_id}: Room ID = " . $assigned_room_id);
                mysqli_stmt_close($stmt_find);
                $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = ?, assigned_room_id = ? WHERE reservation_id = ?");
                mysqli_stmt_bind_param($stmt, "sii", $approved_status, $assigned_room_id, $reservation_id);
                if (!mysqli_stmt_execute($stmt)) {
                    error_log("[ERROR] tbl_reservation update failed for reservation #{$reservation_id}. Error: " . mysqli_stmt_error($stmt));
                    mysqli_stmt_close($stmt);
                    add_notification($_SESSION['admin_id'], 'admin', 'system', 'Reservation Update Failed: Database error updating reservation #'.$reservation_id.'.', $mycon, 0, null, $reservation_id);
                    header("Location: reservations.php?msg=Database+error+updating+reservation");
                    exit();
                }
                error_log("[DEBUG] tbl_reservation update successful for reservation #{$reservation_id}.");
                mysqli_stmt_close($stmt);
                // Mark the room as occupied
                if ($assigned_room_id) {
                    error_log("[DEBUG] Attempting to set room #{$assigned_room_id} to Occupied for reservation #{$reservation_id}.");
                    $stmt = mysqli_prepare($mycon, "UPDATE tbl_room SET status = 'Occupied' WHERE room_id = ?");
                    mysqli_stmt_bind_param($stmt, "i", $assigned_room_id);
                    if (!mysqli_stmt_execute($stmt)) {
                        error_log("[ERROR] tbl_room status update failed for room #{$assigned_room_id}. Error: " . mysqli_stmt_error($stmt));
                        mysqli_stmt_close($stmt);
                        add_notification($_SESSION['admin_id'], 'admin', 'system', 'Room Status Update Failed: Database error updating room status for room #'.$assigned_room_id.'.', $mycon, 0, null, $assigned_room_id);
                        header("Location: reservations.php?msg=Database+error+updating+room");
                        exit();
                    }
                    error_log("[DEBUG] tbl_room status update successful for room #{$assigned_room_id}.");
                    mysqli_stmt_close($stmt);
                } else {
                    error_log("[ERROR] No assigned_room_id after room assignment for reservation #{$reservation_id}.");
                }
                add_notification($guest_id, 'user', 'reservation', 'Your reservation has been approved and a room has been assigned.', $mycon, 0, $admin_id, $reservation_id);
                add_notification($_SESSION['admin_id'], 'admin', 'reservation', 'Reservation #'.$reservation_id.' ('.$guest_name.') has been approved.', $mycon, 0, null, $reservation_id);
                header("Location: reservations.php?msg=Reservation+approved+successfully");
                exit();
            } else {
                error_log("[ERROR] No available room found for reservation #{$reservation_id}.");
                add_notification($_SESSION['admin_id'], 'admin', 'reservation', 'Room Type Fully Booked: No available room for this type and date for reservation #'.$reservation_id.'. The room type is fully booked and cannot accept any more reservations for the selected dates.', $mycon, 0, null, $reservation_id);
                header("Location: reservations.php?msg=Room+type+is+fully+booked+and+cannot+accept+any+more+reservations+for+the+selected+dates");
                exit();
            }
        }
        // Approve Payment action
        if ($action === 'approve_payment' && $payment_id) {
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_payment SET payment_status = 'Paid' WHERE payment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // After approving payment, also approve the reservation and assign a room
            if ($row['status'] !== 'approved') {
                // This logic is duplicated from 'approve_reservation' action.
                // It's better to refactor this into a function if used in more than two places.
                $room_type_id = $row['room_type_id'];
                $check_in = $row['check_in'];
                $check_out = $row['check_out'];

                $find_room_sql = "SELECT r.room_id FROM tbl_room r WHERE r.room_type_id = ? AND r.status = 'Available' AND r.room_id NOT IN (
                    SELECT res.assigned_room_id FROM tbl_reservation res WHERE res.check_in < ? AND res.check_out > ? AND res.status IN ('pending','approved','completed') AND res.assigned_room_id IS NOT NULL
                ) LIMIT 1";
                $stmt_find = mysqli_prepare($mycon, $find_room_sql);
                mysqli_stmt_bind_param($stmt_find, "iss", $room_type_id, $check_out, $check_in);
                mysqli_stmt_execute($stmt_find);
                mysqli_stmt_bind_result($stmt_find, $assigned_room_id);
                
                if (mysqli_stmt_fetch($stmt_find)) {
                    mysqli_stmt_close($stmt_find);
                    
                    $approved_status = 'approved';
                    $stmt_approve = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = ?, assigned_room_id = ? WHERE reservation_id = ?");
                    mysqli_stmt_bind_param($stmt_approve, "sii", $approved_status, $assigned_room_id, $reservation_id);
                    mysqli_stmt_execute($stmt_approve);
                    mysqli_stmt_close($stmt_approve);

                    $stmt_room = mysqli_prepare($mycon, "UPDATE tbl_room SET status = 'Occupied' WHERE room_id = ?");
                    mysqli_stmt_bind_param($stmt_room, "i", $assigned_room_id);
                    mysqli_stmt_execute($stmt_room);
                    mysqli_stmt_close($stmt_room);
                    
                    add_notification($guest_id, 'user', 'reservation', 'Your reservation has been approved and a room has been assigned.', $mycon, 0, $admin_id, $reservation_id);
                } else {
                    mysqli_stmt_close($stmt_find);
                    add_notification($_SESSION['admin_id'], 'admin', 'reservation', 'Room Type Fully Booked: No available room for this type and date for reservation #'.$reservation_id.'.', $mycon, 0, null, $reservation_id);
                }
            }

            // Update admin wallet (if applicable) and notify admin
            $admin_id = 1; // Or $_SESSION['admin_id']
            $payment_method_lower = strtolower($payment_method);
            $amount_float = floatval($amount);

            // Only credit admin for non-cash payments (wallet payments handled separately if it's a reservation type of payment)
            if ($payment_method_lower !== 'cash') {
                $stmt = mysqli_prepare($mycon, "SELECT account_id FROM admin_payment_accounts WHERE admin_id = ? AND account_type = ? LIMIT 1");
                mysqli_stmt_bind_param($stmt, "is", $admin_id, $payment_method_lower);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $admin_account_id);
                $has_admin_account = mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                if ($has_admin_account) {
                    $stmt = mysqli_prepare($mycon, "UPDATE admin_payment_accounts SET balance = balance + ? WHERE account_id = ?");
                    mysqli_stmt_bind_param($stmt, "di", $amount_float, $admin_account_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // Log admin transaction
                    $admin_desc = "Credit for reservation #$reservation_id (Guest ID: $guest_id) via " . ucfirst($payment_method_lower);
                    $admin_log_sql = "INSERT INTO wallet_transactions (admin_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'credit', ?, ?, ?)";
                    $stmt_admin_log = mysqli_prepare($mycon, $admin_log_sql);
                    mysqli_stmt_bind_param($stmt_admin_log, "idsss", $admin_id, $amount_float, $admin_desc, $payment_method, $ref);
                    mysqli_stmt_execute($stmt_admin_log);
                    mysqli_stmt_close($stmt_admin_log);

                    add_notification($admin_id, 'admin', 'payment', "Your " . ucfirst($payment_method_lower) . " account was credited ₱" . number_format($amount_float, 2) . " for reservation #$reservation_id.", $mycon, 0, null, $reservation_id);
                } else {
                    // If no matching account, create one or log an error
                    error_log("Admin payment account for type '" . $payment_method_lower . "' not found for admin_id " . $admin_id . ". Creating new account.");
                    $stmt = mysqli_prepare($mycon, "INSERT INTO admin_payment_accounts (admin_id, account_type, balance) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($stmt, "isd", $admin_id, $payment_method_lower, $amount_float);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // Log admin transaction for newly created account
                    $admin_desc = "Credit for reservation #$reservation_id (Guest ID: $guest_id) via " . ucfirst($payment_method_lower) . " (New account created)";
                    $admin_log_sql = "INSERT INTO wallet_transactions (admin_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'credit', ?, ?, ?)";
                    $stmt_admin_log = mysqli_prepare($mycon, $admin_log_sql);
                    mysqli_stmt_bind_param($stmt_admin_log, "idsss", $admin_id, $amount_float, $admin_desc, $payment_method, $ref);
                    mysqli_stmt_execute($stmt_admin_log);
                    mysqli_stmt_close($stmt_admin_log);

                    add_notification($admin_id, 'admin', 'payment', "New " . ucfirst($payment_method_lower) . " account created and credited ₱" . number_format($amount_float, 2) . " for reservation #$reservation_id.", $mycon, 0, null, $reservation_id);
                }
            }

            // If this is a payment approval (not just a refund action on a paid reservation)
            if ($action === 'approve_payment') {
                add_notification($guest_id, 'user', 'payment', 'Your payment for reservation #' . $reservation_id . ' has been approved.', $mycon, 0, $admin_id, $reservation_id);
                add_notification($_SESSION['admin_id'], 'admin', 'payment', 'Payment for reservation #'.$reservation_id.' ('.$guest_name.') has been approved.', $mycon, 0, null, $reservation_id);
                header("Location: reservations.php?msg=Payment+approved+successfully");
                exit();
            }
        }

        // Handle marking reservation as completed
        if ($reservation_id && $action === 'complete' && $new_status === 'completed') {
            // Update reservation status
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = ? WHERE reservation_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new_status, $reservation_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Optionally, set the room status to 'Available' after completion
            $stmt = mysqli_prepare($mycon, "SELECT assigned_room_id FROM tbl_reservation WHERE reservation_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $reservation_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $assigned_room_id);
            if (mysqli_stmt_fetch($stmt) && $assigned_room_id) {
                mysqli_stmt_close($stmt);
                $stmt_room = mysqli_prepare($mycon, "UPDATE tbl_room SET status = 'Available' WHERE room_id = ?");
                mysqli_stmt_bind_param($stmt_room, "i", $assigned_room_id);
                mysqli_stmt_execute($stmt_room);
                mysqli_stmt_close($stmt_room);
            } else {
                mysqli_stmt_close($stmt);
            }

            // Add notification
            add_notification($guest_id, 'user', 'reservation', 'Your stay has been marked as completed. Thank you!', $mycon, 0, $admin_id, $reservation_id);
            add_notification($_SESSION['admin_id'], 'admin', 'reservation', 'Reservation #'.$reservation_id.' ('.$guest_name.') has been marked as completed.', $mycon, 0, null, $reservation_id);

            header("Location: reservations.php?msg=Reservation+marked+as+completed+successfully");
            exit();
        }

        // Default action for approve/deny of reservation status
        if ($action === 'approve' || $action === 'deny') {
            if (!in_array($new_status, $allowed)) {
                header("Location: dashboard.php?msg=Invalid+status+action");
                exit();
            }
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = ? WHERE reservation_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $new_status, $reservation_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // If approved, set the assigned room to available (if it was a cancellation approval)
            if ($action === 'approve' && $new_status === 'cancelled') {
                // Get the assigned room id
                $assigned_room_id = $row['assigned_room_id'] ?? null;
                if ($assigned_room_id) {
                    $stmt_room = mysqli_prepare($mycon, "UPDATE tbl_room SET status = 'Available' WHERE room_id = ?");
                    mysqli_stmt_bind_param($stmt_room, "i", $assigned_room_id);
                    mysqli_stmt_execute($stmt_room);
                    mysqli_stmt_close($stmt_room);
                }
            }

            if ($action === 'approve' && $new_status === 'approved') {
                // This part of code is for approving reservations that have already been paid (auto_approve_reservation_if_paid logic handles this)
                // If this is reached, it implies a manual approval for an existing reservation, not a new one.
                // It should typically handle room assignment logic here.
                error_log("DEBUG: Manual reservation approval for reservation #{$reservation_id}. Re-checking room assignment.");

                $room_type_id = $row['room_type_id'];
                $check_in = $row['check_in'];
                $check_out = $row['check_out'];

                $find_room_sql = "SELECT r.room_id FROM tbl_room r WHERE r.room_type_id = ? AND r.status = 'Available' AND r.room_id NOT IN (
                    SELECT res.assigned_room_id FROM tbl_reservation res WHERE res.check_in < ? AND res.check_out > ? AND res.status IN ('pending','approved','completed') AND res.assigned_room_id IS NOT NULL
                ) LIMIT 1";
                $stmt_find = mysqli_prepare($mycon, $find_room_sql);
                if (!$stmt_find) {
                     error_log("[ERROR] Find room prepare failed for manual approval of reservation #{$reservation_id}: " . mysqli_error($mycon));
                     add_notification($_SESSION['admin_id'], 'admin', 'system', 'Room Assignment Failed: Database error preparing room query for reservation #'.$reservation_id.'.', $mycon, 0, null, $reservation_id);
                     header("Location: reservations.php?msg=Database+error+finding+room");
                     exit();
                }
                mysqli_stmt_bind_param($stmt_find, "iss", $room_type_id, $check_out, $check_in);
                if (!mysqli_stmt_execute($stmt_find)) {
                     error_log("[ERROR] Find room execute failed for manual approval of reservation #{$reservation_id}: " . mysqli_stmt_error($stmt_find));
                     mysqli_stmt_close($stmt_find); // Close statement before exiting
                     add_notification($_SESSION['admin_id'], 'admin', 'system', 'Room Assignment Failed: Database error executing room query for reservation #'.$reservation_id.'.', $mycon, 0, null, $reservation_id);
                     header("Location: reservations.php?msg=Database+error+finding+room");
                     exit();
                }
                mysqli_stmt_bind_result($stmt_find, $assigned_room_id);
                if (mysqli_stmt_fetch($stmt_find)) {
                    error_log("[DEBUG] Room found for manual approval of reservation #{$reservation_id}: Room ID = " . $assigned_room_id);
                    mysqli_stmt_close($stmt_find);
                    $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = ?, assigned_room_id = ? WHERE reservation_id = ?");
                    mysqli_stmt_bind_param($stmt, "sii", $approved_status, $assigned_room_id, $reservation_id);
                    if (!mysqli_stmt_execute($stmt)) {
                        error_log("[ERROR] tbl_reservation update failed for manual approval of reservation #{$reservation_id}. Error: " . mysqli_stmt_error($stmt));
                        mysqli_stmt_close($stmt);
                        add_notification($_SESSION['admin_id'], 'admin', 'system', 'Reservation Update Failed: Database error updating reservation #'.$reservation_id.'.', $mycon, 0, null, $reservation_id);
                        header("Location: reservations.php?msg=Database+error+updating+reservation");
                        exit();
                    }
                    error_log("[DEBUG] tbl_reservation update successful for manual approval of reservation #{$reservation_id}.");
                    mysqli_stmt_close($stmt);
                    // Mark the room as occupied
                    if ($assigned_room_id) {
                        error_log("[DEBUG] Attempting to set room #{$assigned_room_id} to Occupied for manual approval of reservation #{$reservation_id}.");
                        $stmt = mysqli_prepare($mycon, "UPDATE tbl_room SET status = 'Occupied' WHERE room_id = ?");
                        mysqli_stmt_bind_param($stmt, "i", $assigned_room_id);
                        if (!mysqli_stmt_execute($stmt)) {
                            error_log("[ERROR] tbl_room status update failed for room #{$assigned_room_id}. Error: " . mysqli_stmt_error($stmt));
                            mysqli_stmt_close($stmt);
                            add_notification($_SESSION['admin_id'], 'admin', 'system', 'Room Status Update Failed: Database error updating room status for room #'.$assigned_room_id.'.', $mycon, 0, null, $assigned_room_id);
                            header("Location: reservations.php?msg=Database+error+updating+room");
                            exit();
                        }
                        error_log("[DEBUG] tbl_room status update successful for room #{$assigned_room_id}.");
                        mysqli_stmt_close($stmt);
                    } else {
                        error_log("[ERROR] No assigned_room_id after room assignment for manual approval of reservation #{$reservation_id}.");
                    }
                    add_notification($guest_id, 'user', 'reservation', 'Your reservation has been approved and a room has been assigned.', $mycon, 0, $admin_id, $reservation_id);
                    add_notification($_SESSION['admin_id'], 'admin', 'reservation', 'Reservation #'.$reservation_id.' ('.$guest_name.') has been approved.', $mycon, 0, null, $reservation_id);
                    header("Location: reservations.php?msg=Reservation+approved+successfully");
                    exit();
                } else {
                    error_log("[ERROR] No available room found for manual approval of reservation #{$reservation_id}.");
                    add_notification($_SESSION['admin_id'], 'admin', 'reservation', 'Room Type Fully Booked: No available room for this type and date for reservation #'.$reservation_id.'. The room type is fully booked and cannot accept any more reservations for the selected dates.', $mycon, 0, null, $reservation_id);
                    header("Location: reservations.php?msg=Room+type+is+fully+booked+and+cannot+accept+any+more+reservations+for+the+selected+dates");
                    exit();
                }
            }

            // For cancellation approvals, or denials, simply update status
            if ($action === 'approve' && $new_status === 'cancelled') {
                add_notification($guest_id, 'user', 'cancellation', 'Your reservation cancellation has been approved by the admin.', $mycon, 0, $admin_id, $reservation_id);
                add_notification($_SESSION['admin_id'], 'admin', 'cancellation', 'Reservation #'.$reservation_id.' ('.$guest_name.') has been cancelled.', $mycon, 0, null, $reservation_id);
                header("Location: reservations.php?msg=Cancellation+approved+successfully");
                exit();
            }

            // For general denials (not specifically refund-related)
            if ($action === 'deny' && ($new_status === 'denied' || $new_status === 'cancelled')) {
                add_notification($guest_id, 'user', 'reservation', 'Your reservation has been denied by admin.', $mycon, 0, $admin_id, $reservation_id);
                add_notification($_SESSION['admin_id'], 'admin', 'reservation', 'Reservation #'.$reservation_id.' ('.$guest_name.') has been denied.', $mycon, 0, null, $reservation_id);
                header("Location: reservations.php?msg=Reservation+denied+successfully");
                exit();
            }
        }
    }
}
header("Location: dashboard.php?msg=Invalid+request");
exit(); 