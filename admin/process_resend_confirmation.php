<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}
include '../functions/db_connect.php';

$payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
if (!$payment_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment ID.']);
    exit;
}

// Fetch payment, guest, and reservation details
$sql = "SELECT p.amount, p.payment_method, p.payment_status, p.reference_number, p.created_at, r.reservation_id, r.guest_id, CONCAT(g.first_name, ' ', IFNULL(g.middle_name, ''), ' ', g.last_name) AS guest_name, g.email as guest_email FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id WHERE p.payment_id = ? LIMIT 1";
$stmt = $mycon->prepare($sql);
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$stmt->bind_result($amount, $payment_method, $payment_status, $reference_number, $created_at, $reservation_id, $guest_id, $guest_name, $guest_email);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Payment not found.']);
    $stmt->close();
    exit;
}
$stmt->close();

// Compose email
$subject = "[MF Suites Hotel] Payment Confirmation & Receipt";
$receipt_html = "<h2 style='color:#ffa533;'>MF Suites Hotel Payment Receipt</h2>"
    . "<p>Dear " . htmlspecialchars($guest_name) . ",<br>Thank you for your payment. Here are your payment details:</p>"
    . "<ul style='font-size:1.1em;'>"
    . "<li><strong>Reservation ID:</strong> " . htmlspecialchars($reservation_id) . "</li>"
    . "<li><strong>Reference #:</strong> " . htmlspecialchars($reference_number) . "</li>"
    . "<li><strong>Payment Method:</strong> " . htmlspecialchars($payment_method) . "</li>"
    . "<li><strong>Status:</strong> " . htmlspecialchars($payment_status) . "</li>"
    . "<li><strong>Amount Paid:</strong> ₱" . number_format($amount,2) . "</li>"
    . "<li><strong>Date of Payment:</strong> " . date('M d, Y h:i A', strtotime($created_at)) . "</li>"
    . "</ul>"
    . "<p style='color:#888;font-size:0.95em;'>This is an electronically generated receipt. Thank you for your payment!</p>";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: MF Suites Hotel <no-reply@mfsuites.com>" . "\r\n";

// Send email (use mail() or placeholder)
$mail_sent = false;
if (filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
    $mail_sent = mail($guest_email, $subject, $receipt_html, $headers);
}

// Insert notification for guest
$notif_sql = "INSERT INTO tbl_notifications (user_id, user_type, title, message, created_at, is_read) VALUES (?, 'guest', ?, ?, NOW(), 0)";
$notif_title = "Payment Confirmation Re-sent";
$notif_message = "Your payment receipt for Reservation ID " . htmlspecialchars($reservation_id) . " has been re-sent to your email (" . htmlspecialchars($guest_email) . ").";
$notif_stmt = $mycon->prepare($notif_sql);
$notif_stmt->bind_param('iss', $guest_id, $notif_title, $notif_message);
$notif_stmt->execute();
$notif_stmt->close();

if ($mail_sent) {
    echo json_encode(['success' => true, 'message' => 'Confirmation email and notification sent to guest.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to send email. Notification sent to guest.']);
} 