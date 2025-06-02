<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}
include '../functions/db_connect.php';

$payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
if (!$payment_id) {
    echo '<div class="alert alert-danger">Invalid payment ID.</div>';
    exit;
}

$sql = "SELECT p.amount, p.payment_method, p.payment_status, p.reference_number, p.created_at, r.reservation_id, r.guest_id, CONCAT(g.first_name, ' ', IFNULL(g.middle_name, ''), ' ', g.last_name) AS guest_name, g.email as guest_email FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id WHERE p.payment_id = ? LIMIT 1";
$stmt = $mycon->prepare($sql);
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$stmt->bind_result($amount, $payment_method, $payment_status, $reference_number, $created_at, $reservation_id, $guest_id, $guest_name, $guest_email);
if ($stmt->fetch()) {
    echo '<div class="container-fluid">';
    echo '<div class="row mb-3"><div class="col"><h4 class="fw-bold text-warning mb-0">MF Suites Hotel Payment Receipt</h4></div></div>';
    echo '<div class="row mb-2"><div class="col-md-6"><strong>Guest:</strong> ' . htmlspecialchars($guest_name) . '<br><strong>Email:</strong> ' . htmlspecialchars($guest_email) . '</div>';
    echo '<div class="col-md-6"><strong>Reservation ID:</strong> ' . htmlspecialchars($reservation_id) . '<br><strong>Reference #:</strong> ' . htmlspecialchars($reference_number) . '</div></div>';
    echo '<div class="row mb-2"><div class="col-md-6"><strong>Payment Method:</strong> ' . htmlspecialchars($payment_method) . '</div>';
    echo '<div class="col-md-6"><strong>Status:</strong> <span class="badge bg-'.($payment_status=='Paid'?'success':($payment_status=='Pending'?'warning text-dark':'danger')).'">' . htmlspecialchars($payment_status) . '</span></div></div>';
    echo '<div class="row mb-2"><div class="col-md-6"><strong>Amount Paid:</strong> <span class="text-success">₱' . number_format($amount,2) . '</span></div>';
    echo '<div class="col-md-6"><strong>Date of Payment:</strong> ' . date('M d, Y h:i A', strtotime($created_at)) . '</div></div>';
    echo '<hr class="my-3">';
    echo '<div class="row"><div class="col text-end text-secondary" style="font-size:0.95em;">This is an electronically generated receipt. Thank you for your payment!</div></div>';
    echo '</div>';
} else {
    echo '<div class="alert alert-danger">Payment not found.</div>';
}
$stmt->close(); 