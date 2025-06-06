<?php
session_start();
include('db_connect.php');
$balance = 0;
if (isset($_SESSION['guest_id'])) {
    $guest_id = $_SESSION['guest_id'];
    $sql = "SELECT balance FROM guest_payment_accounts WHERE guest_id = ? AND account_type = 'wallet'";
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param('i', $guest_id);
    $stmt->execute();
    $stmt->bind_result($balance);
    $stmt->fetch();
    $stmt->close();
}
echo json_encode(['wallet_balance' => $balance]); 