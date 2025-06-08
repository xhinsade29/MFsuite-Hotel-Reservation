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
// Add handler for real-time room counts
if ($type === 'room_counts') {
    $available = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_room WHERE status = 'Available'"))[0];
    $occupied = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_room WHERE status = 'Occupied'"))[0];
    echo json_encode(['available' => $available, 'occupied' => $occupied]);
    exit;
}
switch ($type) {
    case 'available_rooms':
        $res = mysqli_query($mycon, "SELECT rm.room_number, rm.room_type_id, rm.status, rt.type_name FROM tbl_room rm LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id WHERE rm.status = 'Available'");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Room Type</th><th>Room Number</th><th>Status</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['type_name'])."</td><td>".htmlspecialchars($r['room_number'])."</td><td><span class='text-success fw-bold'>".htmlspecialchars($r['status'])."</span></td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'occupied_rooms':
        $res = mysqli_query($mycon, "SELECT g.first_name, g.last_name, rm.room_number, rm.status, rt.type_name, r.check_out FROM tbl_room rm LEFT JOIN tbl_reservation r ON rm.room_id = r.assigned_room_id AND r.status IN ('approved','completed') AND r.check_out > NOW() LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id WHERE rm.status = 'Occupied'");
        $html .= "<div class='text-secondary small mb-2'>[DEBUG] Query: Only rooms with status 'Occupied' and joined to current/future approved/completed reservations</div>";
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Guest Name</th><th>Room Type</th><th>Room Number</th><th>Status</th><th>Check-out</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $guest = ($r['first_name'] || $r['last_name']) ? htmlspecialchars($r['first_name'].' '.$r['last_name']) : '<span class="text-secondary">-</span>';
            $checkout = $r['check_out'] ? date('M d, Y h:i A', strtotime($r['check_out'])) : '<span class="text-secondary">-</span>';
            $html .= "<tr><td>".$guest."</td><td>".htmlspecialchars($r['type_name'])."</td><td>".htmlspecialchars($r['room_number'])."</td><td><span class='text-danger fw-bold'>".htmlspecialchars($r['status'])."</span></td><td>".$checkout."</td></tr>";
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
    case 'todays_checkins':
        $res = mysqli_query($mycon, "SELECT g.first_name, g.last_name, rm.room_number FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id WHERE DATE(r.check_in) = CURDATE()");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Guest Name</th><th>Room Number</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".htmlspecialchars($r['room_number'])."</td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'todays_checkouts':
        $res = mysqli_query($mycon, "SELECT g.first_name, g.last_name, rm.room_number FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id WHERE DATE(r.check_out) = CURDATE()");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Guest Name</th><th>Room Number</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".htmlspecialchars($r['room_number'])."</td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'pending_reservations':
        $html .= "<div class='text-center py-4'><p>To manage pending reservations, go to the <strong>Reservations Pending Admin Approval</strong> table below.</p><a href='dashboard.php#pending-approval' class='btn btn-warning'>Go to Pending Reservations Table</a></div>";
        break;
    case 'completed_reservations':
        $res = mysqli_query($mycon, "SELECT g.first_name, g.last_name, r.check_out, r.status FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id WHERE r.status = 'completed' ORDER BY r.check_out DESC");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Guest Name</th><th>Date Completed</th><th>Status</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".date('M d, Y h:i A', strtotime($r['check_out']))."</td><td><span class='text-success fw-bold'>Completed</span></td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'cancelled_requests':
        $res = mysqli_query($mycon, "SELECT g.first_name, g.last_name, cr.reason_text, r.date_created, r.status FROM tbl_reservation r JOIN cancelled_reservation c ON r.reservation_id = c.reservation_id JOIN tbl_guest g ON r.guest_id = g.guest_id JOIN tbl_cancellation_reason cr ON c.reason_id = cr.reason_id WHERE r.status = 'cancelled' ORDER BY r.date_created DESC");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Guest Name</th><th>Reason</th><th>Date Requested</th><th>Status</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".htmlspecialchars($r['reason_text'])."</td><td>".date('M d, Y h:i A', strtotime($r['date_created']))."</td><td><span class='text-danger fw-bold'>Cancelled</span></td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'denied_requests':
        $res = mysqli_query($mycon, "SELECT g.first_name, g.last_name, cr.reason_text, r.date_created, r.status FROM tbl_reservation r JOIN cancelled_reservation c ON r.reservation_id = c.reservation_id JOIN tbl_guest g ON r.guest_id = g.guest_id JOIN tbl_cancellation_reason cr ON c.reason_id = cr.reason_id WHERE r.status = 'denied' ORDER BY r.date_created DESC");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Guest Name</th><th>Date Denied</th><th>Reason</th><th>Status</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".date('M d, Y h:i A', strtotime($r['date_created']))."</td><td>".htmlspecialchars($r['reason_text'])."</td><td><span class='text-warning fw-bold'>Denied</span></td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'approved_cancelled_requests':
        $res = mysqli_query($mycon, "SELECT g.first_name, g.last_name, cr.reason_text, c.date_canceled, r.status FROM tbl_reservation r JOIN cancelled_reservation c ON r.reservation_id = c.reservation_id JOIN tbl_guest g ON r.guest_id = g.guest_id JOIN tbl_cancellation_reason cr ON c.reason_id = cr.reason_id WHERE r.status = 'cancelled' ORDER BY c.date_canceled DESC");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Guest Name</th><th>Reason</th><th>Date Approved</th><th>Status</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".htmlspecialchars($r['reason_text'])."</td><td>".date('M d, Y h:i A', strtotime($r['date_canceled']))."</td><td><span class='text-success fw-bold'>Approved</span></td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'pending_cash_approval':
        $res = mysqli_query($mycon, "SELECT r.reservation_id, g.first_name, g.last_name, rt.type_name, r.check_in, r.check_out, p.amount, p.created_at FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE p.payment_method = 'Cash' AND p.payment_status = 'Pending' ORDER BY p.created_at ASC");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Reservation ID</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Amount</th><th>Date Requested</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['reservation_id'])."</td><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".htmlspecialchars($r['type_name'])."</td><td>".date('M d, Y h:i A', strtotime($r['check_in']))."</td><td>".date('M d, Y h:i A', strtotime($r['check_out']))."</td><td>₱".number_format($r['amount'],2)."</td><td>".date('M d, Y h:i A', strtotime($r['created_at']))."</td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    case 'pending_cancellation_requests':
        $res = mysqli_query($mycon, "SELECT r.reservation_id, g.first_name, g.last_name, rt.type_name, r.check_in, r.check_out, r.date_created FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id WHERE r.status = 'cancellation_requested' ORDER BY r.date_created ASC");
        $html .= "<table class='table table-hover table-bordered align-middle'><thead><tr><th>Reservation ID</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Date Requested</th></tr></thead><tbody>";
        while ($r = mysqli_fetch_assoc($res)) {
            $html .= "<tr><td>".htmlspecialchars($r['reservation_id'])."</td><td>".htmlspecialchars($r['first_name'].' '.$r['last_name'])."</td><td>".htmlspecialchars($r['type_name'])."</td><td>".date('M d, Y h:i A', strtotime($r['check_in']))."</td><td>".date('M d, Y h:i A', strtotime($r['check_out']))."</td><td>".date('M d, Y h:i A', strtotime($r['date_created']))."</td></tr>";
        }
        $html .= '</tbody></table>';
        break;
    default:
        $html = '<div class="text-danger">Invalid summary type.</div>';
}
echo $html; 