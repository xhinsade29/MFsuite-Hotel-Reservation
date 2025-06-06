<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function ($exception) {
    error_log('Uncaught Exception: ' . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine() . '\n' . $exception->getTraceAsString());
    // You could display a generic error message to the user here
    // echo "An unexpected error occurred. Please try again later.";
    // http_response_code(500); // Internal Server Error
});

session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
include '../functions/notify.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($reservation_id && in_array($action, ['approve', 'deny'])) {
        // Get reservation, guest, and payment info
        $sql = "SELECT r.*, g.guest_id, p.payment_id, p.amount, p.payment_status, p.payment_method, p.reference_number FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE r.reservation_id = ?";
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
        $payment_id = $row['payment_id'];
        $amount = $row['amount'];
        $payment_status = $row['payment_status'];
        $payment_method = $row['payment_method'];
        $ref = $row['reference_number'] ?? '';

        $new_status = $action === 'approve' ? 'cancelled' : 'denied';
        $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = ? WHERE reservation_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $reservation_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // If approved, set the assigned room to available
        if ($action === 'approve') {
            // Get the assigned room id
            $assigned_room_id = $row['assigned_room_id'] ?? null;
            if ($assigned_room_id) {
                $stmt_room = mysqli_prepare($mycon, "UPDATE tbl_room SET status = 'Available' WHERE room_id = ?");
                mysqli_stmt_bind_param($stmt_room, "i", $assigned_room_id);
                mysqli_stmt_execute($stmt_room);
                mysqli_stmt_close($stmt_room);
            }
        }

        // Notify user if cancellation is approved
        if ($action === 'approve') {
            $admin_id = 1; // Use your default or actual admin_id here

            // Check if guest_id exists in tbl_guest before sending notification
            $valid_guest = false;
            if (!empty($guest_id) && is_numeric($guest_id) && $guest_id > 0) {
                $check_guest = mysqli_prepare($mycon, "SELECT 1 FROM tbl_guest WHERE guest_id = ? AND is_deleted = 0 LIMIT 1");
                if (!$check_guest) {
                     error_log('ERROR preparing guest_id check statement: ' . mysqli_error($mycon));
                     $valid_guest = false; // Treat preparation failure as invalid guest
                } else {
                    mysqli_stmt_bind_param($check_guest, "i", $guest_id);
                    // Execute the check and handle potential errors, including FK issues
                    try {
                        if (mysqli_stmt_execute($check_guest)) {
                            mysqli_stmt_store_result($check_guest);
                            $valid_guest = mysqli_stmt_num_rows($check_guest) > 0;
                             error_log('DEBUG (cancellation notification): guest_id=' . $guest_id . ', valid_guest=' . ($valid_guest ? 'yes' : 'no'));
                        } else {
                            // This else block might be redundant with the catch, but kept for clarity
                            $valid_guest = false;
                            error_log('ERROR executing guest_id check for notification (non-exception): guest_id=' . var_export($guest_id, true) . ' mysqli_error: ' . mysqli_stmt_error($check_guest));
                        }
                    } catch (\Throwable $e) {
                        // Catch any Throwable (Exception or Error)
                        $valid_guest = false;
                        error_log('CAUGHT EXCEPTION during guest_id check for notification: guest_id=' . var_export($guest_id, true) . ' Exception: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
                        // Note: mysqli_stmt_close is called in the finally block or after this if block
                    } finally {
                         // Close the statement whether execution succeeded or failed
                         mysqli_stmt_close($check_guest);
                    }
                }
            } else {
                error_log('DEBUG (cancellation notification): guest_id is empty or invalid: ' . var_export($guest_id, true));
            }

            if ($valid_guest) {
                 add_notification($guest_id, 'user', 'cancellation', 'Your reservation cancellation has been approved by the admin.', $mycon, 0, $admin_id, $reservation_id);
            } else {
                 error_log('Skipped sending cancellation notification: invalid guest_id=' . var_export($guest_id, true));
            }
        }

        // If approved and eligible for refund
        if ($action === 'approve' && $payment_status === 'Paid' && $payment_method !== 'Cash') {
            $account_type = strtolower($payment_method); // e.g., 'gcash', 'bank', 'paypal', 'credit_card'
            // 1. Deduct from admin's payment account (if enough balance)
            $stmt = mysqli_prepare($mycon, "SELECT account_id, balance FROM admin_payment_accounts WHERE admin_id = ? AND account_type = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "is", $admin_id, $account_type);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $account_id, $account_balance);
            $has_account = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if ($has_account && $account_balance >= $amount) {
                $stmt = mysqli_prepare($mycon, "UPDATE admin_payment_accounts SET balance = balance - ? WHERE account_id = ?");
                mysqli_stmt_bind_param($stmt, "di", $amount, $account_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                // Log refund debit (admin only, does not use guest_id)
                $admin_desc = "Refund issued for cancelled reservation #$reservation_id (Guest ID: $guest_id) via " . ucfirst($account_type);
                $admin_wallet_log_sql = "INSERT INTO wallet_transactions (admin_id, amount, type, description, payment_method) VALUES (?, ?, 'debit', ?, ?)";
                $stmt_admin_log = mysqli_prepare($mycon, $admin_wallet_log_sql);
                mysqli_stmt_bind_param($stmt_admin_log, "idss", $admin_id, $amount, $admin_desc, $account_type);
                mysqli_stmt_execute($stmt_admin_log);
                mysqli_stmt_close($stmt_admin_log);
                // Notify admin of payment account deduction
                $notif_msg = "Your $account_type account was debited ₱" . number_format($amount, 2) . " for refund on cancelled reservation #$reservation_id (Guest ID: $guest_id).";
                add_notification($admin_id, 'admin', 'wallet', $notif_msg, $mycon, 0, null, $guest_id);
            } else {
                error_log("Admin account for $account_type does not exist or insufficient funds for refund.");
                // Optionally, show a message or fallback to another account
            }

            // 2. Mark payment as refunded
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_payment SET payment_status = 'Refunded' WHERE payment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 3. Credit guest's payment account (prefer same type, fallback to wallet)
            $guest_account_type = strtolower($payment_method);
            $stmt = mysqli_prepare($mycon, "SELECT account_id FROM guest_payment_accounts WHERE guest_id = ? AND account_type = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "is", $guest_id, $guest_account_type);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $guest_account_id);
            $has_guest_account = mysqli_stmt_fetch($stmt);
            mysqli_stmt_close($stmt);

            if ($has_guest_account) {
                $stmt = mysqli_prepare($mycon, "UPDATE guest_payment_accounts SET balance = balance + ? WHERE account_id = ?");
                mysqli_stmt_bind_param($stmt, "di", $amount, $guest_account_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                // Log refund to guest
                $desc = "Refund for cancelled reservation #$reservation_id";
                $log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'refund', ?, ?, ?)";
                $log_stmt = mysqli_prepare($mycon, $log_sql);
                mysqli_stmt_bind_param($log_stmt, "idsss", $guest_id, $amount, $desc, $payment_method, $ref);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
                add_notification($guest_id, 'user', 'wallet', "Refunded ₱" . number_format($amount, 2) . " to your $guest_account_type account for cancelled reservation #$reservation_id.", $mycon, 0, $admin_id, $reservation_id);
            } else {
                // Fallback to wallet
                $stmt = mysqli_prepare($mycon, "SELECT account_id FROM guest_payment_accounts WHERE guest_id = ? AND account_type = 'wallet' LIMIT 1");
                mysqli_stmt_bind_param($stmt, "i", $guest_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_bind_result($stmt, $guest_wallet_account_id);
                $has_wallet = mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);

                if ($has_wallet) {
                    $stmt = mysqli_prepare($mycon, "UPDATE guest_payment_accounts SET balance = balance + ? WHERE account_id = ?");
                    mysqli_stmt_bind_param($stmt, "di", $amount, $guest_wallet_account_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                    // Log refund to wallet
                    $desc = "Refund for cancelled reservation #$reservation_id (via wallet fallback)";
                    $log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'refund', ?, 'Wallet', ?)";
                    $log_stmt = mysqli_prepare($mycon, $log_sql);
                    mysqli_stmt_bind_param($log_stmt, "idsss", $guest_id, $amount, $desc, 'Wallet', $ref);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                    add_notification($guest_id, 'user', 'wallet', "Refunded ₱" . number_format($amount, 2) . " to your wallet for cancelled reservation #$reservation_id (original method: " . ucfirst($payment_method) . ").", $mycon, 0, $admin_id, $reservation_id);
                } else {
                    error_log("No guest payment account or wallet found for guest_id=$guest_id. Refund skipped.");
                    // Optionally, show a message or create a wallet account
                }
            }
        }

        $msg = $action === 'approve' ? 'Cancellation approved.' : 'Cancellation denied.';
        header("Location: dashboard.php?msg=" . urlencode($msg));
        exit();
    }
}
header("Location: dashboard.php?msg=Invalid+request");
exit(); 