<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch available room types
$rooms_sql = "SELECT room_type_id, type_name FROM tbl_room_type";
$rooms_result = $conn->query($rooms_sql);

// Fetch available service types
$services_sql = "SELECT service_id, service_name FROM tbl_services";
$services_result = $conn->query($services_sql);

// Fetch available payment types
$payments_sql = "SELECT payment_id, payment_method FROM tbl_payment";
$payments_result = $conn->query($payments_sql);

// Fetch user bookings if logged in
$user_bookings = [];
$room_images = [
    1 => 'standard.avif',
    2 => 'deluxe1.jpg',
    3 => 'superior.jpg',
    4 => 'family_suite.jpg',
    5 => 'executive.jpg',
    6 => 'presidential.avif'
];
if (isset($_SESSION['guest_id'])) {
    $guest_id = $_SESSION['guest_id'];
    $sql = "SELECT r.*, rt.type_name, rt.description, rt.room_price, 
                   GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services,
                   p.payment_status, p.payment_method, p.amount, rt.room_type_id
            FROM tbl_reservation r
            LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id
            LEFT JOIN reservation_services rs ON r.reservation_id = rs.reservation_id
            LEFT JOIN tbl_services s ON rs.service_id = s.service_id
            LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id
            WHERE r.guest_id = $guest_id
            GROUP BY r.reservation_id
            ORDER BY r.date_created DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $user_bookings[] = $row;
        }
    }
}

// Admin-only: Display all transactions
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $all_reservations = [];
    $sql_admin = "SELECT * FROM tbl_reservation ORDER BY date_created DESC";
    $result_admin = $conn->query($sql_admin);
    if ($result_admin && $result_admin->num_rows > 0) {
        while ($row = $result_admin->fetch_assoc()) {
            $all_reservations[] = $row;
        }
    }
}
?>

<?php include '../components/user_navigation.php'; ?>
<?php include '../components/footer.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Reservations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #FF8C00;
            --secondary: #11101d;
            --text-light: #ffffff;
            --text-dim: rgba(255, 255, 255, 0.7);
            --header-height: 70px;
            --sidebar-width: 240px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #1e1e2f;
            color: var(--text-light);
            padding-top: var(--header-height);
        }

        .content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
            max-width: 1200px;
            margin: 0 auto;
            margin-left: var(--sidebar-width);
        }

        h1 {
            text-align: center;
            margin-bottom: 40px;
            font-weight: 600;
            color: var(--text-light);
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .reservation-form {
            background: var(--secondary);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-section {
            margin-bottom: 35px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .section-title {
            font-size: 1.2em;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 140, 0, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-light);
        }

        input[type="text"],
        input[type="datetime-local"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 140, 0, 0.25);
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, var(--primary), #ffa533);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-submit:hover {
            background: linear-gradient(45deg, #e67e00, #ff8c00);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
            .content {
                margin-left: 0;
                padding: 20px;
            }
        }

        .booking-card { background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); margin-bottom: 32px; display: flex; overflow: hidden; }
        .booking-card img { width: 220px; height: 180px; object-fit: cover; border-radius: 16px 0 0 16px; }
        .booking-details { flex: 1; padding: 24px 32px; color: #fff; }
        .booking-details h4 { color: #FF8C00; font-weight: 700; }
        .booking-details .desc { color: #bdbdbd; font-size: 1em; margin-bottom: 10px; }
        .booking-details .meta { margin-bottom: 8px; }
        .booking-details .status { font-weight: 600; }
        .booking-details .cancel-btn { margin-top: 12px; }
        .badge-waiting { background: #ffc107; color: #23234a; }
        .badge-approved { background: #28a745; }
        .badge-cancel { background: #dc3545; }
    </style>
</head>
<body>
    <?php include '../components/user_navigation.php'; ?>
    <?php include '../components/footer.php'; ?>
    
    <div class="content d-flex flex-column align-items-center justify-content-center">
        <h1 class="text-center"><i class="fas fa-calendar-alt"></i> My Reservations</h1>
        
        <?php if (isset($_SESSION['guest_id'])): ?>
        <div class="mb-5 w-100" style="max-width: 900px;">
            <h2 class="text-warning mb-3" style="font-size:1.3em;"><i class="bi bi-journal-bookmark"></i> My Bookings</h2>
            <?php if (count($user_bookings) > 0): ?>
            <div class="container py-5">
                <?php foreach ($user_bookings as $booking): ?>
                <?php
                $services = [];
                if (!empty($booking['services'])) {
                    $services = explode(', ', $booking['services']);
                }
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
                ?>
                <div class="booking-card">
                    <img src="../assets/rooms/<?php echo htmlspecialchars($room_images[$booking['room_id']] ?? 'standard.avif'); ?>" alt="Room Image">
                    <div class="booking-details">
                        <h4><?php echo htmlspecialchars($booking['type_name']); ?></h4>
                        <div class="desc mb-2"><?php echo htmlspecialchars($booking['description']); ?></div>
                        <div class="meta"><strong>Check-in:</strong> <?php echo htmlspecialchars($booking['check_in']); ?></div>
                        <div class="meta"><strong>Check-out:</strong> <?php echo htmlspecialchars($booking['check_out']); ?></div>
                        <div class="meta"><strong>Payment:</strong> <?php echo htmlspecialchars($booking['payment_method']); ?> (<?php echo htmlspecialchars($booking['payment_status']); ?>)</div>
                        <div class="meta"><strong>Amount:</strong> ₱<?php echo number_format($booking['amount'], 2); ?></div>
                        <div class="meta"><strong>Room Price:</strong> ₱<?php echo number_format($booking['room_price'], 2); ?></div>
                        <div class="meta"><strong>Date Booked:</strong> <?php echo htmlspecialchars($booking['date_created']); ?></div>
                        <?php if (!empty($included_services)): ?>
                        <div class="meta"><strong>Included Room Services:</strong> <?php echo htmlspecialchars(implode(', ', $included_services)); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($services)): ?>
                        <div class="meta"><strong>Selected Services:</strong> <?php echo htmlspecialchars(implode(', ', $services)); ?></div>
                        <?php endif; ?>
                        <div class="meta"><strong>Status:</strong> 
                            <?php 
                            if (isset($booking['cancel_requested']) && $booking['cancel_requested']) {
                                echo '<span class="badge badge-cancel">Cancellation Requested</span>';
                            } else if (isset($booking['approved']) && $booking['approved']) {
                                echo '<span class="badge badge-approved">Approved</span>';
                            } else {
                                echo '<span class="badge badge-waiting">Waiting for Approval</span>';
                            }
                            ?>
                        </div>
                        <?php if (empty($booking['cancel_requested'])): ?>
                        <form method="POST" action="cancel_booking.php" class="cancel-btn">
                            <input type="hidden" name="reservation_id" value="<?php echo $booking['reservation_id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Cancel Booking</button>
                        </form>
                        <?php endif; ?>
                        <a href="reservation_details.php?id=<?php echo $booking['reservation_id']; ?>" class="btn btn-primary btn-sm mt-2">View Details</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info">You have no bookings yet.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>