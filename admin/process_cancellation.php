<?php
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
        $sql = "SELECT r.*, g.guest_id, p.payment_id, p.amount, p.payment_status, p.payment_method FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE r.reservation_id = ?";
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

        $new_status = $action === 'approve' ? 'cancelled' : 'denied';
        $stmt = mysqli_prepare($mycon, "UPDATE tbl_reservation SET status = ? WHERE reservation_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_status, $reservation_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // If approved and eligible for refund
        if ($action === 'approve' && $payment_status === 'Paid' && $payment_method !== 'Cash') {
            // Refund logic (same as above)
            $stmt = mysqli_prepare($mycon, "UPDATE tbl_payment SET payment_status = 'Refunded' WHERE payment_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $stmt = mysqli_prepare($mycon, "UPDATE tbl_guest SET wallet_balance = wallet_balance + ? WHERE guest_id = ?");
            mysqli_stmt_bind_param($stmt, "di", $amount, $guest_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            $desc = "Refund for cancelled reservation #$reservation_id";
            $log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, payment_method) VALUES (?, ?, 'refund', ?, ?)";
            $log_stmt = mysqli_prepare($mycon, $log_sql);
            mysqli_stmt_bind_param($log_stmt, "idss", $guest_id, $amount, $desc, $payment_method);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);

            add_notification($guest_id, 'wallet', "Refunded ₱" . number_format($amount, 2) . " to your wallet for cancelled reservation #$reservation_id.", $mycon);
        }

        $msg = $action === 'approve' ? 'Cancellation approved.' : 'Cancellation denied.';
        header("Location: dashboard.php?msg=" . urlencode($msg));
        exit();
    }
}
header("Location: dashboard.php?msg=Invalid+request");
exit(); 