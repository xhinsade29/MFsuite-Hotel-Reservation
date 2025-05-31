<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo 'Forbidden';
    exit();
}
include '../functions/db_connect.php';
$type = $_GET['type'] ?? '';
$html = '';
switch ($type) {
    case 'available_rooms':
        $res = mysqli_query($mycon, "SELECT room_number, room_type_id FROM tbl_room WHERE status = 'Available'");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Room Number</th><th>Room Type</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $type_name = mysqli_fetch_row(mysqli_query($mycon, "SELECT type_name FROM tbl_room_type WHERE room_type_id = {$r['room_type_id']}"))[0];
            $html .= "<tr><td>".htmlspecialchars($r['room_number'])."</td><td>".htmlspecialchars($type_name)."</td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'occupied_rooms':
        $res = mysqli_query($mycon, "SELECT room_number, room_type_id FROM tbl_room WHERE status = 'Occupied'");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Room Number</th><th>Room Type</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $type_name = mysqli_fetch_row(mysqli_query($mycon, "SELECT type_name FROM tbl_room_type WHERE room_type_id = {$r['room_type_id']}"))[0];
            $html .= "<tr><td>".htmlspecialchars($r['room_number'])."</td><td>".htmlspecialchars($type_name)."</td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'total_bookings':
        $res = mysqli_query($mycon, "SELECT r.reservation_id, g.first_name, g.last_name, r.check_in, r.check_out, r.status FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id ORDER BY r.date_created DESC LIMIT 100");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Reservation ID</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['reservation_id'])."</td><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".date('M d, Y h:i A', strtotime($r['check_in']))."</td><td>".date('M d, Y h:i A', strtotime($r['check_out']))."</td><td>".htmlspecialchars($r['status'])."</td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'total_guests':
        $res = mysqli_query($mycon, "SELECT first_name, last_name, user_email, phone_number FROM tbl_guest ORDER BY first_name ASC");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Name</th><th>Email</th><th>Phone</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".htmlspecialchars($r['user_email'])."</td><td>".htmlspecialchars($r['phone_number'])."</td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'total_income':
        $res = mysqli_query($mycon, "SELECT p.amount, p.payment_method, p.payment_status, p.payment_created, g.first_name, g.last_name FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id WHERE p.payment_status = 'Paid' ORDER BY p.payment_created DESC LIMIT 100");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Guest</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>₱".number_format($r['amount'],2)."</td><td>".htmlspecialchars($r['payment_method'])."</td><td>".htmlspecialchars($r['payment_status'])."</td><td>".date('M d, Y h:i A', strtotime($r['payment_created']))."</td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'pending_admin_approval':
        $res = mysqli_query($mycon, "SELECT r.reservation_id, g.first_name, g.last_name, rt.type_name, r.check_in, r.check_out, p.payment_status FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE r.status = 'pending' ORDER BY r.date_created DESC");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Reservation ID</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Payment Status</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['reservation_id'])."</td><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".htmlspecialchars($r['type_name'])."</td><td>".date('M d, Y h:i A', strtotime($r['check_in']))."</td><td>".date('M d, Y h:i A', strtotime($r['check_out']))."</td><td>".htmlspecialchars($r['payment_status'])."</td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    default:
        $html = '<div class="text-danger">Invalid summary type.</div>';
}
echo $html; 