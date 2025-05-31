<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit();
}
include_once '../functions/db_connect.php';
include_once '../functions/notify.php';
$guest_id = $_SESSION['guest_id'];
$amount = floatval($_POST['topup_amount'] ?? 0);
$payment_method = trim($_POST['payment_method'] ?? '');
$reference_number = trim($_POST['reference_number'] ?? '');
if ($amount > 0 && $payment_method !== '' && $reference_number !== '') {
    $sql = "UPDATE tbl_guest SET wallet_balance = wallet_balance + ? WHERE guest_id = ?";
    $stmt = mysqli_prepare($mycon, $sql);
    mysqli_stmt_bind_param($stmt, "di", $amount, $guest_id);
    if (mysqli_stmt_execute($stmt)) {
        // Log the top-up in wallet_transactions
        $desc = 'Wallet top-up';
        $log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'topup', ?, ?, ?)";
        $log_stmt = mysqli_prepare($mycon, $log_sql);
        mysqli_stmt_bind_param($log_stmt, "idsss", $guest_id, $amount, $desc, $payment_method, $reference_number);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
        $_SESSION['success'] = 'Wallet topped up successfully!';
    } else {
        $_SESSION['error'] = 'Failed to top up wallet.';
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error'] = 'All fields are required.';
}
header('Location: update_profile.php');
exit(); 