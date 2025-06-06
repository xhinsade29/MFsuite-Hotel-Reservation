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
// Generate a unique reference number
function generate_reference_number() {
    return strtoupper(bin2hex(random_bytes(8)));
}
$reference_number = generate_reference_number();
if ($amount > 0 && $payment_method !== '') {
    // Find the guest's wallet account
    $stmt_wallet = mysqli_prepare($mycon, "SELECT account_id, balance FROM guest_payment_accounts WHERE guest_id = ? AND account_type = 'wallet' LIMIT 1");
    mysqli_stmt_bind_param($stmt_wallet, "i", $guest_id);
    mysqli_stmt_execute($stmt_wallet);
    mysqli_stmt_bind_result($stmt_wallet, $wallet_account_id, $current_wallet_balance);
    $wallet_exists = mysqli_stmt_fetch($stmt_wallet);
    mysqli_stmt_close($stmt_wallet);

    if ($wallet_exists) {
        // Add the top-up amount to the wallet balance
        $new_wallet_balance = $current_wallet_balance + $amount;
        $stmt_update_wallet = mysqli_prepare($mycon, "UPDATE guest_payment_accounts SET balance = ? WHERE account_id = ?");
        mysqli_stmt_bind_param($stmt_update_wallet, "di", $new_wallet_balance, $wallet_account_id);
        mysqli_stmt_execute($stmt_update_wallet);
        mysqli_stmt_close($stmt_update_wallet);
    } else {
        // If no wallet account exists, create one with the top-up amount
        $stmt_create_wallet = mysqli_prepare($mycon, "INSERT INTO guest_payment_accounts (guest_id, account_type, balance) VALUES (?, 'wallet', ?)");
        mysqli_stmt_bind_param($stmt_create_wallet, "id", $guest_id, $amount);
        mysqli_stmt_execute($stmt_create_wallet);
        $wallet_account_id = mysqli_insert_id($mycon);
        mysqli_stmt_close($stmt_create_wallet);
    }

    // Log the top-up in wallet_transactions
    $desc = 'Wallet top-up';
    $log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'topup', ?, ?, ?)";
    $log_stmt = mysqli_prepare($mycon, $log_sql);
    mysqli_stmt_bind_param($log_stmt, "idsss", $guest_id, $amount, $desc, $payment_method, $reference_number);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
    // Insert notification for the user
    $admin_id = 1; // Use your default or actual admin_id here
    $notif_sql = "INSERT INTO user_notifications (guest_id, type, message, created_at, admin_id) VALUES (?, 'wallet', ?, NOW(), ?)";
    $notif_msg = "Your wallet was topped up with ₱" . number_format($amount, 2) . " via $payment_method. Ref: $reference_number";
    $notif_stmt = mysqli_prepare($mycon, $notif_sql);
    mysqli_stmt_bind_param($notif_stmt, "isi", $guest_id, $notif_msg, $admin_id);
    mysqli_stmt_execute($notif_stmt);
    mysqli_stmt_close($notif_stmt);
    $_SESSION['success'] = 'Wallet topped up successfully!<br>Your Reference Number: <b>' . htmlspecialchars($reference_number) . '</b>';
    header('Location: update_profile.php?topup=success');
    exit();
} else {
    $_SESSION['error'] = 'All fields are required.';
}
header('Location: update_profile.php');
exit(); 