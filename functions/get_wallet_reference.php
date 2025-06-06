<?php
session_start();
header('Content-Type: application/json');
include('db_connect.php');

if (!isset($_SESSION['guest_id'])) {
    echo json_encode(['reference_number' => '']);
    exit();
}
$guest_id = $_SESSION['guest_id'];
// Find the latest paid wallet top-up for this user from wallet_transactions
$sql = "SELECT reference_number FROM wallet_transactions WHERE guest_id = ? AND type = 'topup' AND reference_number IS NOT NULL AND reference_number != '' ORDER BY created_at DESC LIMIT 1";
$stmt = $mycon->prepare($sql);
$stmt->bind_param("i", $guest_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(['reference_number' => $row['reference_number']]);
} else {
    echo json_encode(['reference_number' => '']);
}
$stmt->close(); 