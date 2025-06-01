<?php
session_start();
if (!isset($_SESSION['guest_id'])) {
    http_response_code(403);
    exit('Not logged in');
}
if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Missing reservation ID');
}
$reservation_id = intval($_GET['id']);
$guest_id = $_SESSION['guest_id'];
$conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$room_images = [
    1 => 'standard.avif',
    2 => 'deluxe1.jpg',
    3 => 'superior.jpg',
    4 => 'family_suite.jpg',
    5 => 'executive.jpg',
    6 => 'presidential.avif'
];
$sql = "SELECT r.*, r.status AS reservation_status, rt.type_name, rt.description, rt.nightly_rate, 
               GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services,
               p.payment_status, p.payment_method, p.amount, rt.room_type_id
        FROM tbl_reservation r
        LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id
        LEFT JOIN reservation_services rs ON r.reservation_id = rs.reservation_id
        LEFT JOIN tbl_services s ON rs.service_id = s.service_id
        LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id
        WHERE r.guest_id = $guest_id AND r.reservation_id = $reservation_id
        GROUP BY r.reservation_id
        LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    $services = [];
    if (!empty($booking['services'])) {
        $services = explode(', ', $booking['services']);
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
    }
    $check_in = isset($booking['check_in']) ? $booking['check_in'] : '';
    $check_out = isset($booking['check_out']) ? $booking['check_out'] : '';
    $nightly_rate = isset($booking['nightly_rate']) ? $booking['nightly_rate'] : (isset($booking['room_price']) ? $booking['room_price'] : 0);
    $number_of_nights = 1;
    if ($check_in && $check_out) {
        $in = new DateTime($check_in);
        $out = new DateTime($check_out);
        $diff = $in->diff($out);
        $number_of_nights = max(1, $diff->days);
    }
    $total_amount = $nightly_rate * $number_of_nights;
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
    // Output the booking card HTML (same as in reservations.php main list)
    ?>
    <div class="booking-card position-relative" data-reservation-id="<?php echo $booking['reservation_id']; ?>" data-date="<?php echo $booking['date_created']; ?>" data-status="<?php echo strtolower($booking['reservation_status']); ?>">
        <input type="checkbox" class="form-check-input booking-checkbox position-absolute" style="left:10px;top:10px;z-index:2;">
        <button class="btn btn-close btn-close-white position-absolute m-2 delete-booking-btn" title="Remove from view" style="right:10px;top:10px;"></button>
        <img src="../assets/rooms/<?php echo htmlspecialchars($room_images[$booking['room_id']] ?? 'standard.avif'); ?>" alt="Room Image">
        <div class="booking-details">
            <h4><?php echo htmlspecialchars($booking['type_name']); ?></h4>
            <?php
            $status = strtolower($booking['reservation_status']);
            $badgeClass = 'bg-secondary';
            if ($status === 'pending') $badgeClass = 'bg-warning text-dark';
            elseif ($status === 'completed') $badgeClass = 'bg-success';
            elseif ($status === 'cancellation_requested') $badgeClass = 'bg-info text-dark';
            elseif ($status === 'cancelled' || $status === 'denied') $badgeClass = 'bg-danger';
            ?>
            <span class="badge <?php echo $badgeClass; ?> mb-2">
                <?php echo ucfirst(str_replace('_', ' ', $booking['reservation_status'])); ?>
            </span>
            <?php if ($assigned_room_number): ?>
            <div class="meta"><strong>Room Number:</strong> <?php echo htmlspecialchars($assigned_room_number); ?></div>
            <?php endif; ?>
            <div class="meta"><strong>Date Booked:</strong> <?php echo date('Y-m-d h:i A', strtotime($booking['date_created'])); ?></div>
            <div class="meta"><strong>Number of Nights:</strong> <?php echo $number_of_nights; ?></div>
            <div class="meta"><strong>Total Price:</strong> ₱<?php echo number_format($total_amount, 2); ?></div>
            <div class="d-flex align-items-center gap-2 mt-2">
                <a href="reservation_details.php?id=<?php echo $booking['reservation_id']; ?>" class="btn btn-info btn-sm">View Details</a>
            </div>
        </div>
    </div>
    <?php
}
$conn->close(); 