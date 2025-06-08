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

$sql = "SELECT p.amount, p.payment_method, p.payment_status, p.reference_number, p.created_at, r.reservation_id, r.guest_id, CONCAT(g.first_name, ' ', IFNULL(g.middle_name, ''), ' ', g.last_name) AS guest_name, g.user_email as guest_email, rt.type_name AS room_type FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.assigned_room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id WHERE p.payment_id = ? LIMIT 1";
$stmt = $mycon->prepare($sql);
$stmt->bind_param('i', $payment_id);
$stmt->execute();
$stmt->bind_result($amount, $payment_method, $payment_status, $reference_number, $created_at, $reservation_id, $guest_id, $guest_name, $guest_email, $room_type);
if ($stmt->fetch()) {
    echo '<div class="d-flex justify-content-center align-items-center" style="min-height:60vh;">';
    echo '<div class="card shadow-lg border-0" style="max-width:480px;width:100%;border-radius:22px;background:#fff;">';
    echo '<div class="card-body p-4">';
    echo '<div class="text-center mb-3">'
        .'<div class="mb-2" style="font-size:2.5rem;color:#ffa533;"><i class="bi bi-receipt"></i></div>'
        .'<div class="fw-bold" style="font-size:1.7rem;color:#23234a;letter-spacing:1px;">MF Suites Hotel</div>'
        .'<div class="text-secondary" style="font-size:1.1rem;">Payment Receipt</div>'
    .'</div>';
    echo '<hr style="border-top:2px dashed #ffa533;opacity:.5;">';
    echo '<div class="row mb-2 g-2">';
    echo '<div class="col-6 small text-secondary">Guest</div><div class="col-6 fw-semibold">' . htmlspecialchars($guest_name) . '</div>';
    echo '<div class="col-6 small text-secondary">Email</div><div class="col-6">' . htmlspecialchars($guest_email) . '</div>';
    echo '<div class="col-6 small text-secondary">Reservation ID</div><div class="col-6">' . htmlspecialchars($reservation_id) . '</div>';
    echo '<div class="col-6 small text-secondary">Room Type</div><div class="col-6">' . htmlspecialchars($room_type ?? 'N/A') . '</div>';
    echo '<div class="col-6 small text-secondary">Reference #</div><div class="col-6">' . htmlspecialchars($reference_number) . '</div>';
    echo '<div class="col-6 small text-secondary">Payment Method</div><div class="col-6">' . htmlspecialchars($payment_method) . '</div>';
    echo '<div class="col-6 small text-secondary">Status</div><div class="col-6"><span class="badge bg-'.($payment_status=='Paid'?'success':($payment_status=='Pending'?'warning text-dark':'danger')).' px-3 py-2" style="font-size:1em;">' . htmlspecialchars($payment_status) . '</span></div>';
    echo '<div class="col-6 small text-secondary">Date</div><div class="col-6">' . date('M d, Y h:i A', strtotime($created_at)) . '</div>';
    echo '</div>';
    echo '<hr style="border-top:2px dashed #ffa533;opacity:.5;">';
    echo '<div class="text-center mb-3">'
        .'<div class="small text-secondary">Amount Paid</div>'
        .'<div class="fw-bold text-success" style="font-size:2.2rem;">₱' . number_format($amount,2) . '</div>'
    .'</div>';
    echo '<div class="text-center mb-3">'
        .'<img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=MFsuiteReceipt-'.$payment_id.'" alt="QR Code" class="rounded-3 border" style="background:#fff;" />'
        .'<div class="small text-secondary mt-2">Scan for authenticity</div>'
    .'</div>';
    echo '<div class="text-center text-secondary small" style="font-size:0.98em;">This is an electronically generated receipt.<br>Thank you for your payment!</div>';
    echo '</div></div></div>';
} else {
    echo '<div class="alert alert-danger">Payment not found.</div>';
}
$stmt->close(); 