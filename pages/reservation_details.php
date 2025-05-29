<?php
session_start();
$conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$reservation_id = intval($_GET['id'] ?? 0);
if (!$reservation_id) { die("Invalid reservation."); }

// Fetch reservation details
$sql = "SELECT r.*, rt.type_name, rt.description, rt.room_price,
               GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services,
               p.payment_status, p.payment_method, p.amount, rt.room_type_id
        FROM tbl_reservation r
        LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id
        LEFT JOIN reservation_services rs ON r.reservation_id = rs.reservation_id
        LEFT JOIN tbl_services s ON rs.service_id = s.service_id
        LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id
        WHERE r.reservation_id = $reservation_id
        GROUP BY r.reservation_id";
$result = $conn->query($sql);
if (!$result || $result->num_rows == 0) { die("Reservation not found."); }
$booking = $result->fetch_assoc();

// Fetch included room services for this room type
$included_services = [];
$room_type_id = $booking['room_type_id'];
$service_sql = "SELECT s.service_name FROM tbl_room_services rs JOIN tbl_services s ON rs.service_id = s.service_id WHERE rs.room_type_id = $room_type_id";
$service_result = $conn->query($service_sql);
if ($service_result && $service_result->num_rows > 0) {
    while ($row = $service_result->fetch_assoc()) {
        $included_services[] = $row['service_name'];
    }
}
// Fetch user details
$user_sql = "SELECT first_name, middle_name, last_name, user_email, phone_number, address FROM tbl_guest WHERE guest_id = ?";
$stmt_user = $conn->prepare($user_sql);
$stmt_user->bind_param("i", $booking['guest_id']);
$stmt_user->execute();
$stmt_user->bind_result($first_name, $middle_name, $last_name, $user_email, $phone_number, $address);
$stmt_user->fetch();
$stmt_user->close();
// Room images mapping
$room_images = [
    1 => 'standard.avif',
    2 => 'deluxe1.jpg',
    3 => 'superior.jpg',
    4 => 'family_suite.jpg',
    5 => 'executive.jpg',
    6 => 'presidential.avif'
];
$image_file = $room_images[$booking['room_id']] ?? 'standard.avif';
$conn->close();
// Notification logic
$show_cancel_notification = isset($_GET['cancel']) && $_GET['cancel'] === 'requested';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reservation Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; }
        .details-container {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            max-width: 900px;
            margin: 40px auto;
            background: #23234a;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 0;
        }
        .details-image {
            flex: 1.2;
            min-width: 340px;
            background: #18182f;
            border-radius: 16px 0 0 16px;
            padding: 36px 32px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
        }
        .details-image img {
            width: 100%;
            height: 260px;
            object-fit: cover;
            border-radius: 10px;
        }
        .details-content {
            flex: 1;
            min-width: 340px;
            background: #23234a;
            border-radius: 0 16px 16px 0;
            padding: 36px 32px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .details-content h4 { color: #FF8C00; font-weight: 700; margin-bottom: 12px; }
        .details-content .list-group-item { background: #23234a; color: #fff; border: none; }
        .details-content .list-group-item strong { color: #ffa533; }
        .user-details { background: #18182f; border-radius: 10px; padding: 18px 24px; margin-bottom: 18px; width: 100%; }
        .user-details h5 { color: #ffa533; font-weight: 600; margin-bottom: 10px; }
        .user-details .user-info { margin-bottom: 6px; }
        @media (max-width: 991px) {
            .details-container { flex-direction: column; border-radius: 16px; }
            .details-image, .details-content { border-radius: 16px 16px 0 0; min-width: unset; padding: 24px 12px; }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Reservation Details</h2>
    <?php if ($show_cancel_notification): ?>
    <div class="alert alert-info text-center">Your cancellation request has been sent. Please wait for admin approval.</div>
    <?php endif; ?>
    <div class="details-container">
        <div class="details-image">
            <img src="../assets/rooms/<?php echo htmlspecialchars($image_file); ?>" alt="Room Image" onerror="this.src='../assets/rooms/standard.avif'">
        </div>
        <div class="details-content">
            <div class="user-details">
                <h5>User Details</h5>
                <div class="user-info"><strong>Name:</strong> <?php echo htmlspecialchars(trim($first_name . ' ' . $middle_name . ' ' . $last_name)); ?></div>
                <div class="user-info"><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></div>
                <div class="user-info"><strong>Phone:</strong> <?php echo htmlspecialchars($phone_number); ?></div>
                <div class="user-info"><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></div>
            </div>
            <h4><?php echo htmlspecialchars($booking['type_name']); ?></h4>
            <p class="mb-3 text-center"><?php echo htmlspecialchars($booking['description']); ?></p>
            <ul class="list-group list-group-flush w-100 mb-3">
                <li class="list-group-item"><strong>Check-in:</strong> <?php echo htmlspecialchars($booking['check_in']); ?></li>
                <li class="list-group-item"><strong>Check-out:</strong> <?php echo htmlspecialchars($booking['check_out']); ?></li>
                <?php if (!empty($included_services)): ?>
                <li class="list-group-item"><strong>Included Room Services:</strong> <?php echo htmlspecialchars(implode(', ', $included_services)); ?></li>
                <?php endif; ?>
                <?php if (!empty($booking['services'])): ?>
                <li class="list-group-item"><strong>Selected Services:</strong> <?php echo htmlspecialchars($booking['services']); ?></li>
                <?php endif; ?>
                <li class="list-group-item"><strong>Room Price:</strong> ₱<?php echo number_format($booking['room_price'], 2); ?></li>
                <li class="list-group-item"><strong>Total Amount:</strong> ₱<?php echo number_format($booking['amount'], 2); ?></li>
                <li class="list-group-item"><strong>Status:</strong> <?php echo htmlspecialchars($booking['payment_status']); ?></li>
                <li class="list-group-item"><strong>Date Booked:</strong> <?php echo htmlspecialchars($booking['date_created']); ?></li>
            </ul>
            <a href="reservations.php" class="btn btn-warning mb-2">Back to My Reservations</a>
            <?php if (empty($booking['cancel_requested'])): ?>
            <button class="btn btn-danger mb-2" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel Reservation</button>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light rounded-4 shadow-lg border-0">
      <div class="modal-header border-0 pb-0 justify-content-center bg-transparent">
        <h4 class="modal-title w-100 text-center fw-bold text-warning" id="cancelModalLabel">Cancel Reservation</h4>
        <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p>Are you sure you want to cancel this reservation?</p>
        <form method="POST" action="cancel_booking.php">
            <input type="hidden" name="reservation_id" value="<?php echo $booking['reservation_id']; ?>">
            <button type="submit" class="btn btn-danger fw-bold">Yes, Cancel Reservation</button>
            <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">No, Keep Reservation</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 