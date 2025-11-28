<?php
session_start();
header('Content-Type: application/json');
include('db_connect.php');

if (!isset($_SESSION['guest_id'])) {
    echo json_encode(['reference_number' => '']);
    exit();
}
$guest_id = $_SESSION['guest_id'];
// Find the latest paid wallet top-up for this user
$sql = "SELECT reference_number FROM tbl_payment WHERE payment_method = 'Wallet' AND payment_status = 'Paid' AND reference_number IS NOT NULL AND reference_number != '' AND amount > 0 AND payment_id IN (SELECT payment_id FROM tbl_reservation WHERE guest_id = $guest_id) ORDER BY payment_id DESC LIMIT 1";
$result = $mycon->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(['reference_number' => $row['reference_number']]);
} else {
    echo json_encode(['reference_number' => '']);
} 