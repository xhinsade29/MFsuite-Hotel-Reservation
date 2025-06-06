<?php
session_start();
$conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$reservation_id = intval($_GET['id'] ?? 0);
if (!$reservation_id) { die("Invalid reservation."); }
// Fetch reservation details (same as in reservation_details.php)
$sql = "SELECT r.status AS reservation_status, r.*, rt.type_name, rt.description, rt.nightly_rate,
               GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services,
               p.payment_status, p.payment_method, p.amount, rt.room_type_id
        FROM tbl_reservation r
        LEFT JOIN tbl_room rm ON r.room_id = rm.room_id
        LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id
        LEFT JOIN reservation_services rs ON r.reservation_id = rs.reservation_id
        LEFT JOIN tbl_services s ON rs.service_id = s.service_id
        LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id
        WHERE r.reservation_id = $reservation_id
        GROUP BY r.reservation_id";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) { die("Reservation not found."); }
$booking = $result->fetch_assoc();
$reference_number = $booking['reference_number'] ?? '';
if (empty($reference_number) && isset($booking['payment_id'])) {
    $pay_sql = "SELECT reference_number FROM tbl_payment WHERE payment_id = " . intval($booking['payment_id']);
    $pay_res = $conn->query($pay_sql);
    if ($pay_res && $pay_res->num_rows > 0) {
        $reference_number = $pay_res->fetch_assoc()['reference_number'];
    }
}
$included_services = [];
$room_type_id = $booking['room_type_id'];
if (!empty($room_type_id) && is_numeric($room_type_id)) {
    $service_sql = "SELECT s.service_name FROM tbl_room_services rs JOIN tbl_services s ON rs.service_id = s.service_id WHERE rs.room_type_id = $room_type_id";
    $service_result = $conn->query($service_sql);
    if ($service_result && $service_result->num_rows > 0) {
        while ($row = $service_result->fetch_assoc()) {
            $included_services[] = $row['service_name'];
        }
    }
} else {
    $included_services = [];
}
$user_sql = "SELECT first_name, middle_name, last_name, user_email, phone_number, address FROM tbl_guest WHERE guest_id = ?";
$stmt_user = $conn->prepare($user_sql);
$stmt_user->bind_param("i", $booking['guest_id']);
$stmt_user->execute();
$stmt_user->bind_result($first_name, $middle_name, $last_name, $user_email, $phone_number, $address);
$stmt_user->fetch();
$stmt_user->close();
$room_images = [
    1 => 'standard.avif',
    2 => 'deluxe1.jpg',
    3 => 'superior.jpg',
    4 => 'family_suite.jpg',
    5 => 'executive.jpg',
    6 => 'presidential.avif'
];
$image_file = $room_images[$booking['room_id']] ?? 'standard.avif';
$total_amount = $booking['nightly_rate'];
$service_names = [];
if (!empty($booking['services'])) {
    $service_names = explode(', ', $booking['services']);
}
if (count($service_names) > 0) {
    $service_names_escaped = array_map(function($name) use ($conn) {
        return "'" . $conn->real_escape_string($name) . "'";
    }, $service_names);
    $service_names_str = implode(',', $service_names_escaped);
    $service_price_sql = "SELECT service_price FROM tbl_services WHERE service_name IN ($service_names_str)";
    $service_price_result = $conn->query($service_price_sql);
    if ($service_price_result && $service_price_result->num_rows > 0) {
        while ($row = $service_price_result->fetch_assoc()) {
            $total_amount += $row['service_price'];
        }
    }
}
$assigned_room_number = null;
if (!empty($booking['assigned_room_id'])) {
    $room_id = intval($booking['assigned_room_id']);
    $room_num_sql = "SELECT room_number FROM tbl_room WHERE room_id = $room_id";
    $room_num_result = $conn->query($room_num_sql);
    if ($room_num_result && $room_num_result->num_rows > 0) {
        $assigned_room_number = $room_num_result->fetch_assoc()['room_number'];
    }
}
$cancellation_details = null;
if ($booking['reservation_status'] === 'cancelled') {
    $cancel_sql = "SELECT cr.*, r.reason_text FROM cancelled_reservation cr LEFT JOIN tbl_cancellation_reason r ON cr.reason_id = r.reason_id WHERE cr.reservation_id = ? ORDER BY cr.date_canceled DESC LIMIT 1";
    $stmt_cancel = $conn->prepare($cancel_sql);
    $stmt_cancel->bind_param("i", $reservation_id);
    $stmt_cancel->execute();
    $result_cancel = $stmt_cancel->get_result();
    if ($result_cancel && $result_cancel->num_rows > 0) {
        $cancellation_details = $result_cancel->fetch_assoc();
    }
    $stmt_cancel->close();
}
$conn->close();
?>
<div class="details-image">
    <img src="../assets/rooms/<?php echo htmlspecialchars($image_file); ?>" alt="Room Image" class="img-fluid rounded mb-3" style="max-height:300px;object-fit:cover;">
    <h4 class="text-warning mt-2"><?php echo htmlspecialchars($booking['type_name']); ?></h4>
    <p class="mb-3"><?php echo htmlspecialchars($booking['description']); ?></p>
    <?php if (!empty($included_services)): ?>
    <div class="inclusions-card">
        <h6>Room Inclusions</h6>
        <ul class="ps-3" style="color:#ffa533;">
            <?php foreach ($included_services as $service): ?>
                <li><?php echo htmlspecialchars($service); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    <!-- Back button and Cancel button below image -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <a href="reservations.php" class="btn btn-outline-light d-inline-flex align-items-center" style="gap: 0.5em; font-weight: 500; border-radius: 2em; box-shadow: 0 2px 8px rgba(0,0,0,0.10);">
            <i class="bi bi-arrow-left-circle" style="font-size: 1.3em;"></i> Back to My Reservations
        </a>
        <?php if ($booking['reservation_status'] !== 'cancellation_requested' && $booking['reservation_status'] !== 'cancelled' && $booking['reservation_status'] !== 'denied' && $booking['reservation_status'] !== 'completed'): ?>
        <button id="cancelBtn" class="btn btn-outline-danger d-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cancelModal" style="gap:0.5em; font-weight:500; border-radius:2em; box-shadow:0 2px 8px rgba(0,0,0,0.10);">
            <i class="bi bi-trash3" style="font-size:1.2em;"></i> Cancel Reservation
        </button>
        <?php endif; ?>
    </div>
</div>
<div class="details-content">
    <div class="card-modern">
        <h2 class="fw-bold mb-4" style="color:#ffa533; letter-spacing:1px;">Reservation & Payment Details</h2>
        <div class="details-section">
            <div class="section-title"><i class="bi bi-person icon"></i>Guest Information</div>
            <div class="info-row"><strong>Name:</strong>&nbsp;<?php echo htmlspecialchars(trim($first_name . ' ' . $middle_name . ' ' . $last_name)); ?></div>
            <div class="info-row"><strong>Email:</strong>&nbsp;<?php echo htmlspecialchars($user_email); ?></div>
            <div class="info-row"><strong>Phone:</strong>&nbsp;<?php echo htmlspecialchars($phone_number); ?></div>
            <div class="info-row"><strong>Address:</strong>&nbsp;<?php echo htmlspecialchars($address); ?></div>
        </div>
        <div class="divider"></div>
        <div class="details-section">
            <div class="section-title"><i class="bi bi-calendar-check icon"></i>Reservation Information</div>
            <div class="info-row"><strong>Reservation ID:</strong>&nbsp;<?php echo htmlspecialchars($booking['reservation_id']); ?></div>
            <div class="info-row"><strong>Reference Number:</strong>&nbsp;<?php echo htmlspecialchars($reference_number ?: '-'); ?></div>
            <div class="info-row"><strong>Check-in:</strong>&nbsp;<?php echo date('Y-m-d h:i A', strtotime($booking['check_in'])); ?></div>
            <div class="info-row"><strong>Check-out:</strong>&nbsp;<?php echo date('Y-m-d h:i A', strtotime($booking['check_out'])); ?></div>
            <div class="info-row"><strong>Number of Nights:</strong>&nbsp;<?php
                $nights = (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / (60*60*24);
                echo intval(round($nights));
            ?></div>
            <div class="info-row"><strong>Room Number:</strong>&nbsp;<?php echo $assigned_room_number ? htmlspecialchars($assigned_room_number) : '<span class="text-secondary">Not assigned</span>'; ?></div>
            <div class="info-row">
                <strong>Status:</strong>&nbsp;
                <?php $status = trim($booking['reservation_status']); ?>
                <?php if ($status === 'approved'): ?>
                    <span style="color:#28a745; font-weight:600;">Approved by Admin</span>
                <?php elseif ($status === 'pending'): ?>
                    <span style="color:#ffc107; font-weight:600;">Pending Admin Approval</span>
                <?php elseif ($status === 'completed'): ?>
                    <span style="color:#0d6efd; font-weight:600;">Completed</span>
                <?php elseif ($status === 'cancelled'): ?>
                    <span style="color:#dc3545; font-weight:600;">Cancelled</span>
                <?php elseif ($status === 'cancellation_requested'): ?>
                    <span style="color:#17a2b8; font-weight:600;">Cancellation Requested</span>
                <?php elseif ($status === 'denied'): ?>
                    <span style="color:#6c757d; font-weight:600;">Cancellation Denied</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($booking['services'])): ?>
            <div class="info-row"><strong>Selected Services:</strong>&nbsp;<?php echo htmlspecialchars($booking['services']); ?></div>
            <?php endif; ?>
        </div>
        <?php if ($status === 'cancelled' && ($cancellation_details || $booking['payment_status'] === 'Refunded')): ?>
            <div class="details-section">
                <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start; margin-top:18px;">
                    <?php if ($cancellation_details): ?>
                        <div style="flex:1 1 260px; min-width:220px;">
                            <hr style="border:0; border-top:2.5px solid #dc3545; margin:0 0 10px 0;">
                            <div class="alert alert-danger mt-2" style="background:rgba(255,0,0,0.08);color:#ffb3b3;border:none; font-size:0.92em; line-height:1.3; padding:10px 16px;">
                                <strong style="font-size:1em;">Cancellation Details:</strong><br>
                                <span><strong>Reason:</strong> <?php echo htmlspecialchars($cancellation_details['reason_text'] ?? '-'); ?></span><br>
                                <span><strong>Cancelled By:</strong> <?php echo htmlspecialchars($cancellation_details['canceled_by'] ?? '-'); ?></span><br>
                                <span><strong>Date Cancelled:</strong> <?php echo htmlspecialchars(date('Y-m-d h:i A', strtotime($cancellation_details['date_canceled'] ?? ''))); ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($booking['payment_status'] === 'Refunded'): ?>
                        <div style="flex:1 1 260px; min-width:220px;">
                            <hr style="border:0; border-top:2.5px solid #28a745; margin:0 0 10px 0;">
                            <div class="alert alert-success mt-2" style="background:rgba(0,255,0,0.08);color:#b3ffb3;border:none; font-size:0.92em; line-height:1.3; padding:10px 16px;">
                                <strong style="font-size:1em;">Refunded:</strong> The payment for this reservation has been refunded and added to your wallet.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="divider"></div>
        <div class="details-section">
            <div class="section-title"><i class="bi bi-credit-card icon"></i>Payment Information</div>
            <div class="info-row"><strong>Paid via:</strong>&nbsp;<?php echo htmlspecialchars($booking['payment_method']); ?></div>
            <div class="info-row"><strong>Total Amount:</strong>&nbsp;₱<?php echo number_format($total_amount, 2); ?></div>
            <div class="info-row"><strong>Payment Status:</strong>&nbsp;
            <?php $pay_status = trim($booking['payment_status']); ?>
            <?php if ($pay_status === 'Paid'): ?>
                <span style="color:#28a745; font-weight:600;">Paid</span>
            <?php elseif ($pay_status === 'Pending'): ?>
                <span style="color:#ffc107; font-weight:600;">Pending</span>
            <?php elseif ($pay_status === 'Failed'): ?>
                <span style="color:#dc3545; font-weight:600;">Failed</span>
            <?php elseif ($pay_status === 'Refunded'): ?>
                <span style="color:#dc3545; font-weight:600;">Refunded</span>
            <?php else: ?>
                <span style="color:#6c757d; font-weight:600;"><?php echo htmlspecialchars($pay_status); ?></span>
            <?php endif; ?>
            </div>
            <div class="info-row"><strong>Date Booked:</strong>&nbsp;<?php echo htmlspecialchars($booking['date_created']); ?></div>
        </div>
    </div>
</div> 