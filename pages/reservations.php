<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';

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
    $sql = "SELECT r.*, r.status AS reservation_status, rt.type_name, rt.description, rt.nightly_rate, 
                   GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services,
                   p.payment_status, p.payment_method, p.amount, rt.room_type_id
            FROM tbl_reservation r
            LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id
            LEFT JOIN reservation_services rs ON r.reservation_id = rs.reservation_id
            LEFT JOIN tbl_services s ON rs.service_id = s.service_id
            LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id
            LEFT JOIN hidden_reservations h ON h.reservation_id = r.reservation_id AND h.guest_id = $guest_id
            WHERE r.guest_id = $guest_id
              AND h.reservation_id IS NULL
            GROUP BY r.reservation_id
            ORDER BY r.date_created DESC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $user_bookings[] = $row;
        }
    }
}

// Fetch hidden bookings for the user
$hidden_bookings = [];
if (isset($_SESSION['guest_id'])) {
    $guest_id = $_SESSION['guest_id'];
    $sql_hidden = "SELECT r.*, r.status AS reservation_status, rt.type_name, rt.description, rt.nightly_rate, 
                   GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services,
                   p.payment_status, p.payment_method, p.amount, rt.room_type_id
            FROM tbl_reservation r
            LEFT JOIN tbl_room_type rt ON r.room_id = rt.room_type_id
            LEFT JOIN reservation_services rs ON r.reservation_id = rs.reservation_id
            LEFT JOIN tbl_services s ON rs.service_id = s.service_id
            LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id
            INNER JOIN hidden_reservations h ON h.reservation_id = r.reservation_id AND h.guest_id = $guest_id
            WHERE r.guest_id = $guest_id
            GROUP BY r.reservation_id
            ORDER BY r.date_created DESC";
    $result_hidden = $conn->query($sql_hidden);
    if ($result_hidden && $result_hidden->num_rows > 0) {
        while ($row = $result_hidden->fetch_assoc()) {
            $hidden_bookings[] = $row;
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

// Add endpoint for trash count
if (isset($_GET['get_trash_count']) && $_GET['get_trash_count'] == 1) {
    $conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
    $guest_id = $_SESSION['guest_id'] ?? 0;
    $sql = "SELECT COUNT(*) as cnt FROM hidden_reservations WHERE guest_id = $guest_id";
    $result = $conn->query($sql);
    $row = $result ? $result->fetch_assoc() : ['cnt' => 0];
    header('Content-Type: application/json');
    echo json_encode(['trash_count' => (int)$row['cnt']]);
    exit();
}

// Add endpoint for trash HTML (AJAX for trash modal)
if (isset($_GET['get_trash_html']) && $_GET['get_trash_html'] == 1) {
    // Only output the trashBookingList content (hidden bookings)
    ob_start();
    echo '<div class="container" id="trashBookingList" style="max-width:900px;padding:0;">';
    if (count($hidden_bookings) > 0) {
        foreach ($hidden_bookings as $booking) {
            echo '<div class="booking-card position-relative" data-reservation-id="' . $booking['reservation_id'] . '">';
            echo '<img src="../assets/rooms/' . htmlspecialchars($room_images[$booking['room_id']] ?? 'standard.avif') . '" alt="Room Image">';
            echo '<div class="booking-details">';
            echo '<h4>' . htmlspecialchars($booking['type_name']) . '</h4>';
            $status = strtolower($booking['reservation_status']);
            $badgeClass = 'bg-secondary';
            if ($status === 'pending') $badgeClass = 'bg-warning text-dark';
            elseif ($status === 'completed') $badgeClass = 'bg-success';
            elseif ($status === 'cancellation_requested') $badgeClass = 'bg-info text-dark';
            elseif ($status === 'cancelled' || $status === 'denied') $badgeClass = 'bg-danger';
            echo '<span class="badge ' . $badgeClass . ' mb-2">' . ucfirst(str_replace('_', ' ', $booking['reservation_status'])) . ' (Deleted)</span>';
            echo '<div class="meta"><strong>Date Booked:</strong> ' . date('Y-m-d h:i A', strtotime($booking['date_created'])) . '</div>';
            echo '<div class="meta"><strong>Number of Nights:</strong> ' . $booking['number_of_nights'] . '</div>';
            echo '<div class="meta"><strong>Total Price:</strong> ₱' . number_format($booking['amount'], 2) . '</div>';
            echo '<div class="d-flex align-items-center gap-2 mt-2">';
            echo '<a href="reservation_details.php?id=' . $booking['reservation_id'] . '" class="btn btn-info btn-sm">View Details</a>';
            echo '<button type="button" class="btn btn-success btn-sm restore-single-trash d-inline-flex align-items-center gap-1"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>';
            echo '<button type="button" class="btn btn-danger btn-sm delete-single-trash d-inline-flex align-items-center gap-1"><i class="bi bi-trash"></i> Delete</button>';
            echo '</div></div></div>';
        }
    } else {
        echo '<div class="alert alert-info text-center">No deleted reservations.</div>';
    }
    echo '</div>';
    echo ob_get_clean();
    exit();
}
?>

<?php include '../components/user_navigation.php'; ?>

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

        /* Light mode overrides */
        body.light-mode {
            background: #f8f9fa !important;
            color: #23234a !important;
        }
        body.light-mode .content {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode h1,
        body.light-mode h2,
        body.light-mode h4 {
            color: #ff8c00 !important;
        }
        body.light-mode .booking-card {
            background: #f7f7fa !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
            box-shadow: 0 4px 20px rgba(255,140,0,0.07);
        }
        body.light-mode .booking-card .booking-details h4 {
            color: #ff8c00 !important;
        }
        body.light-mode .booking-card .desc {
            color: #666 !important;
        }
        body.light-mode .booking-card .badge {
            background: #ffe5b4 !important;
            color: #ff8c00 !important;
        }
        body.light-mode .btn-primary, body.light-mode .btn-info {
            background: linear-gradient(90deg, #ff8c00, #ffa533) !important;
            color: #fff !important;
            border: none !important;
        }
        body.light-mode .btn-outline-secondary, body.light-mode .btn-outline-light {
            border-color: #ff8c00 !important;
            color: #ff8c00 !important;
        }
        body.light-mode .btn-outline-secondary:hover, body.light-mode .btn-outline-light:hover {
            background: #ff8c00 !important;
            color: #fff !important;
        }
        body.light-mode .alert-info {
            background: #ffe5b4 !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .modal-content {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode .modal-header, body.light-mode .modal-footer {
            background: #f7f7fa !important;
            color: #23234a !important;
        }
        body.light-mode .form-label, body.light-mode label {
            color: #23234a !important;
        }
        body.light-mode input, body.light-mode select, body.light-mode textarea {
            background: #fff !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode input:focus, body.light-mode select:focus, body.light-mode textarea:focus {
            border-color: #ff8c00 !important;
            box-shadow: 0 0 0 0.12rem rgba(255,140,0,0.13);
        }
        body.light-mode, body.light-mode .booking-card, body.light-mode .booking-details, body.light-mode .meta, body.light-mode .alert-info, body.light-mode .modal-content, body.light-mode .modal-header, body.light-mode .modal-footer, body.light-mode .form-label, body.light-mode label, body.light-mode input, body.light-mode select, body.light-mode textarea {
            color: #23234a !important;
        }
        body.light-mode .form-check-input:checked {
            background-color: #23234a !important;
            border-color: #23234a !important;
        }
        body.light-mode .form-check-input {
            border: 1.5px solid #23234a !important;
        }
        body.light-mode .modal-content .booking-card, body.light-mode .modal-content .booking-details {
            background: #fff !important;
            color: #23234a !important;
        }
        /* End light mode overrides */

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

        .booking-card {
            background: #23234a;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            margin-bottom: 32px;
            display: flex;
            overflow: hidden;
            min-height: 220px;
        }
        .booking-card img {
            width: 260px;
            height: 220px;
            object-fit: cover;
            border-radius: 16px 0 0 16px;
        }
        .booking-details {
            flex: 1;
            padding: 36px 40px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .booking-details h4 {
            color: #FF8C00;
            font-weight: 700;
            font-size: 1.5em;
        }
        .booking-details .desc {
            color: #bdbdbd;
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        .booking-details .meta {
            margin-bottom: 8px;
        }
        .booking-details .status {
            font-weight: 700;
            font-size: 1.15em;
        }
        .booking-details .cancel-btn {
            margin-top: 12px;
        }
        .badge-waiting { background: #ffc107; color: #23234a; }
        .badge-approved { background: #28a745; }
        .badge-cancel { background: #dc3545; }
        .booking-details .badge {
            font-size: 1.1em;
            padding: 0.7em 1.3em;
            border-radius: 1.5em;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        }
        @media (max-width: 768px) {
            .booking-card, .booking-card img {
                min-height: 140px;
                height: 140px;
            }
            .booking-card img {
                width: 120px;
            }
            .booking-details {
                padding: 18px 10px;
            }
        }
        /* Remove any transform properties from trash modal and trash list */
        #trashBookingList, #trashModal .modal-content, #trashModal .container, #trashModal .booking-card {
            transform: none !important;
        }
        /* Keep reservation list and trash modal containers steady and aligned */
        .content > .mb-5[style], #trashModal .container {
            max-width: 900px !important;
            width: 100% !important;
            margin: 0 auto !important;
            padding: 0 !important;
            position: static !important;
            transition: none !important;
            transform: none !important;
        }
        /* Remove any transition/transform from booking cards */
        .booking-card {
            transition: none !important;
            transform: none !important;
            animation: none !important;
        }
        /* Prevent modal from affecting underlying .content */
        .modal-backdrop {
            z-index: 1050 !important;
        }
    </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
    <?php include '../components/user_navigation.php'; ?>
    
    <div class="content d-flex flex-column align-items-center justify-content-center">
        <h1 class="text-center"><i class="fas fa-calendar-alt"></i> My Reservations</h1>

        <?php if (isset($_SESSION['guest_id'])): ?>
            <!-- Removed Debug message -->
        <?php endif; ?>

        <?php if (isset($_SESSION['guest_id'])): ?>
        <div class="mb-5 w-100" style="max-width: 900px;">
            <h2 class="text-warning mb-3" style="font-size:1.3em;"><i class="bi bi-journal-bookmark"></i> My Bookings</h2>
            <!-- Sorting, Select All, and UI Delete Controls -->
            <div class="d-flex justify-content-between align-items-center mb-3" style="max-width:900px;width:100%;">
                <div class="d-flex align-items-center gap-3">
                    <label for="sortSelect" class="form-label mb-0 me-2">Sort by:</label>
                    <select id="sortSelect" class="form-select form-select-sm d-inline-block" style="width:auto;display:inline-block;">
                        <option value="date_desc">Newest First</option>
                        <option value="date_asc">Oldest First</option>
                        <option value="status">Status</option>
                    </select>
                   
                    <button type="button" class="btn btn-danger btn-sm ms-2" id="deleteSelectedBtn" disabled>Delete Selected</button>
                </div>
                <button id="showTrashBtn" class="btn btn-danger btn-sm ms-2 d-flex align-items-center gap-2" style="font-weight:600;font-size:1.1em;">
                    <i class="bi bi-trash3-fill" style="font-size:1.1em;" id="trashBtnIcon"></i> <span id="trashBtnText">Trash (<?php echo count($hidden_bookings); ?>)</span>
                </button>
            </div>
          
            <?php if (count($user_bookings) > 0): ?>
            <div id="userReservationList">
                <?php foreach ($user_bookings as $booking): ?>
                <?php
                $services = [];
                if (!empty($booking['services'])) {
                    $services = explode(', ', $booking['services']);
                }
                // Fetch included room services for this room type
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
                // Get selected service names for this booking
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
                // Fetch assigned room number if available
                $assigned_room_number = null;
                if (!empty($booking['assigned_room_id'])) {
                    $room_id = intval($booking['assigned_room_id']);
                    $room_num_sql = "SELECT room_number FROM tbl_room WHERE room_id = $room_id";
                    $room_num_result = $conn->query($room_num_sql);
                    if ($room_num_result && $room_num_result->num_rows > 0) {
                        $assigned_room_number = $room_num_result->fetch_assoc()['room_number'];
                    }
                }
                ?>
                <div class="booking-card position-relative" data-reservation-id="<?php echo $booking['reservation_id']; ?>" data-date="<?php echo $booking['date_created']; ?>" data-status="<?php echo strtolower($booking['reservation_status']); ?>">
                    <input type="checkbox" class="form-check-input booking-checkbox position-absolute" style="left:10px;top:10px;z-index:2;">
                    <button class="btn btn-close btn-close-white position-absolute m-2 delete-booking-btn" title="Remove from view" style="right:10px;top:10px;"></button>
                    <img src="../assets/rooms/<?php echo htmlspecialchars($room_images[$booking['room_id']] ?? 'standard.avif'); ?>" alt="Room Image">
                    <div class="booking-details">
                        <h4><?php echo htmlspecialchars($booking['type_name']); ?></h4>
                        <?php
                        // Status badge color
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
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info">You have no bookings yet.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Trash Modal -->
        <div class="modal fade" id="trashModal" tabindex="-1" aria-labelledby="trashModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
              <div class="modal-header border-0">
                <h5 class="modal-title text-danger" id="trashModalLabel"><i class="bi bi-trash3-fill me-2"></i>Trash</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="trashForm">
                <div class="container" id="trashBookingList" style="max-width:900px;padding:0;">
                    <?php if (count($hidden_bookings) > 0): ?>
                        <?php foreach ($hidden_bookings as $booking): ?>
                        <div class="booking-card position-relative" data-reservation-id="<?php echo $booking['reservation_id']; ?>">
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
                                    <?php echo ucfirst(str_replace('_', ' ', $booking['reservation_status'])); ?> (Deleted)
                                </span>
                                <div class="meta"><strong>Date Booked:</strong> <?php echo date('Y-m-d h:i A', strtotime($booking['date_created'])); ?></div>
                                <div class="meta"><strong>Number of Nights:</strong> <?php echo $booking['number_of_nights']; ?></div>
                                <div class="meta"><strong>Total Price:</strong> ₱<?php echo number_format($booking['amount'], 2); ?></div>
                                <div class="d-flex align-items-center gap-2 mt-2">
                                    <a href="reservation_details.php?id=<?php echo $booking['reservation_id']; ?>" class="btn btn-info btn-sm">View Details</a>
                                    <button type="button" class="btn btn-success btn-sm restore-single-trash d-inline-flex align-items-center gap-1"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                    <button type="button" class="btn btn-danger btn-sm delete-single-trash d-inline-flex align-items-center gap-1"><i class="bi bi-trash"></i> Delete</button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info text-center">No deleted reservations.</div>
                    <?php endif; ?>
                </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- Toast for actions -->
        <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1200;">
          <div class="toast align-items-center text-bg-danger border-0" id="trashToast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000" style="display:none;">
            <div class="d-flex">
              <div class="toast-body">
                <i class="bi bi-trash-fill me-2"></i> Reservation moved to trash.
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
          </div>
          <div class="toast align-items-center text-bg-success border-0" id="restoreToast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000" style="display:none;">
            <div class="d-flex">
              <div class="toast-body">
                <i class="bi bi-arrow-counterclockwise me-2"></i> Reservation restored successfully.
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
          </div>
          <div class="toast align-items-center text-bg-danger border-0" id="permanentDeleteToast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000" style="display:none;">
            <div class="d-flex">
              <div class="toast-body">
                <i class="bi bi-trash-fill me-2"></i> Reservation permanently deleted.
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
          </div>
        </div>
        <!-- Modal for confirmation -->
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
              <div class="modal-header border-0">
                <h5 class="modal-title text-danger" id="confirmDeleteModalLabel"><i class="bi bi-exclamation-triangle"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                Are you sure you want to permanently delete the selected reservation(s)? This action cannot be undone.
              </div>
              <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</button>
              </div>
            </div>
          </div>
        </div>
        <!-- Add a confirmation modal for deleting selected bookings -->
        <div class="modal fade" id="confirmDeleteSelectedModal" tabindex="-1" aria-labelledby="confirmDeleteSelectedModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
              <div class="modal-header border-0">
                <h5 class="modal-title text-danger" id="confirmDeleteSelectedModalLabel"><i class="bi bi-exclamation-triangle"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                Are you sure you want to delete the selected reservation(s)? This action will hide them from your bookings.
              </div>
              <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteSelectedBtn">Yes, Delete</button>
              </div>
            </div>
          </div>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <!-- Booking Success Modal -->
    <!-- REMOVE THIS MODAL AND SCRIPT -->
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hide (delete) a single booking
        document.querySelectorAll('.delete-booking-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var card = btn.closest('.booking-card');
                var reservationId = card.getAttribute('data-reservation-id');
                if (confirm('Are you sure you want to delete this reservation?')) {
                    fetch('hide_reservation.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'reservation_id=' + encodeURIComponent(reservationId)
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === 'success') {
                            // Move card to trash list in modal (real-time)
                            var trashList = document.getElementById('trashBookingList');
                            if (trashList) {
                                // Clone the card and update its UI for trash
                                var trashCard = card.cloneNode(true);
                                // Remove the delete-booking-btn and add restore/delete trash buttons
                                var delBtn = trashCard.querySelector('.delete-booking-btn');
                                if (delBtn) delBtn.remove();
                                // Remove the booking-checkbox if present
                                var checkbox = trashCard.querySelector('.booking-checkbox');
                                if (checkbox) checkbox.remove();
                                // Add trash action buttons if not present
                                var details = trashCard.querySelector('.booking-details');
                                if (details && !trashCard.querySelector('.restore-single-trash')) {
                                    var btns = document.createElement('div');
                                    btns.className = 'd-flex align-items-center gap-2 mt-2';
                                    btns.innerHTML = `
                                        <a href="reservation_details.php?id=${reservationId}" class="btn btn-info btn-sm">View Details</a>
                                        <button type="button" class="btn btn-success btn-sm restore-single-trash d-inline-flex align-items-center gap-1"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                        <button type="button" class="btn btn-danger btn-sm delete-single-trash d-inline-flex align-items-center gap-1"><i class="bi bi-trash"></i> Delete</button>
                                    `;
                                    details.appendChild(btns);
                                }
                                trashList.prepend(trashCard);
                            }
                            card.remove();
                            // Show toast
                            var toastEl = document.getElementById('trashToast');
                            if (toastEl) {
                                toastEl.style.display = '';
                                var toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 2000 });
                                toast.show();
                                setTimeout(function() { location.reload(); }, 1800);
                            } else {
                                location.reload();
                            }
                            // Update trash count badge in real time
                            var trashBtnText = document.getElementById('trashBtnText');
                            if (trashBtnText) {
                                // Find the number in parentheses and increment it
                                var match = trashBtnText.textContent.match(/\((\d+)\)/);
                                if (match) {
                                    var count = parseInt(match[1], 10) + 1;
                                    trashBtnText.textContent = 'Trash (' + count + ')';
                                }
                            }
                        } else {
                            alert('Failed to delete reservation.');
                        }
                    });
                }
            });
        });
        // Restore hidden booking
        document.querySelectorAll('.restore-booking-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var card = btn.closest('.booking-card');
                var reservationId = card.getAttribute('data-reservation-id');
                fetch('restore_reservation.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'reservation_id=' + encodeURIComponent(reservationId)
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === 'success') {
                        // Show toast and reload page for real-time update
                        var toastEl = document.getElementById('restoreToast');
                        if (toastEl) {
                            toastEl.style.display = '';
                            var toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 2000 });
                            toast.show();
                            setTimeout(function() { location.reload(); }, 1800);
                        } else {
                            location.reload();
                        }
                        // Update trash count badge in real time
                        var trashBtnText = document.getElementById('trashBtnText');
                        if (trashBtnText) {
                            // Find the number in parentheses and decrement it
                            var match = trashBtnText.textContent.match(/\((\d+)\)/);
                            if (match) {
                                var count = Math.max(0, parseInt(match[1], 10) - 1);
                                trashBtnText.textContent = 'Trash (' + count + ')';
                            }
                        }
                    } else {
                        alert('Failed to restore reservation.');
                    }
                });
            });
        });
        // Toggle deleted reservations section
        var showTrashBtn = document.getElementById('showTrashBtn');
        var trashModal = document.getElementById('trashModal');
        if (showTrashBtn && trashModal) {
            showTrashBtn.addEventListener('click', function() {
                var modal = new bootstrap.Modal(trashModal);
                modal.show();
            });
        }
        // Sorting
        var sortSelect = document.getElementById('sortSelect');
        var bookingList = document.getElementById('userReservationList');
        if (sortSelect && bookingList) {
            sortSelect.addEventListener('change', function() {
                var cards = Array.from(bookingList.querySelectorAll('.booking-card'));
                if (sortSelect.value === 'date_asc') {
                    cards.sort(function(a, b) {
                        return new Date(a.dataset.date) - new Date(b.dataset.date);
                    });
                } else if (sortSelect.value === 'date_desc') {
                    cards.sort(function(a, b) {
                        return new Date(b.dataset.date) - new Date(a.dataset.date);
                    });
                } else if (sortSelect.value === 'status') {
                    cards.sort(function(a, b) {
                        return a.dataset.status.localeCompare(b.dataset.status);
                    });
                }
                cards.forEach(function(card) { bookingList.appendChild(card); });
            });
        }
        // Multi-select Delete Selected logic (no toggle)
        var deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        var confirmDeleteSelectedModal = document.getElementById('confirmDeleteSelectedModal');
        var confirmDeleteSelectedBtn = document.getElementById('confirmDeleteSelectedBtn');
        if (bookingList && deleteSelectedBtn) {
            // Enable/disable Delete Selected button based on checkbox selection
            bookingList.addEventListener('change', function(e) {
                if (e.target.classList.contains('booking-checkbox')) {
                    var checked = bookingList.querySelectorAll('.booking-checkbox:checked');
                    deleteSelectedBtn.disabled = checked.length === 0;
                }
            });
            // Show confirmation modal on Delete Selected click
            deleteSelectedBtn.addEventListener('click', function() {
                var checked = bookingList.querySelectorAll('.booking-checkbox:checked');
                if (checked.length === 0) return;
                var modal = new bootstrap.Modal(confirmDeleteSelectedModal);
                modal.show();
            });
            // Handle confirmed deletion
            if (confirmDeleteSelectedBtn) {
                confirmDeleteSelectedBtn.addEventListener('click', function() {
                    var checked = bookingList.querySelectorAll('.booking-checkbox:checked');
                    var ids = Array.from(checked).map(function(cb) {
                        return cb.closest('.booking-card').getAttribute('data-reservation-id');
                    });
                    if (ids.length === 0) return;
                    // Hide button to prevent double click
                    deleteSelectedBtn.disabled = true;
                    // Delete each selected booking
                    var deletePromises = ids.map(function(reservationId) {
                        return fetch('hide_reservation.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'reservation_id=' + encodeURIComponent(reservationId)
                        })
                        .then(response => response.text())
                        .then(data => {
                            if (data.trim() === 'success') {
                                var card = bookingList.querySelector('.booking-card[data-reservation-id="' + reservationId + '"]');
                                if (card) card.remove();
                            }
                        });
                    });
                    Promise.all(deletePromises).then(function() {
                        // After deletion, reset controls
                        var checkboxes = bookingList.querySelectorAll('.booking-checkbox');
                        checkboxes.forEach(function(cb) { cb.checked = false; });
                        deleteSelectedBtn.disabled = true;
                        var modal = bootstrap.Modal.getInstance(confirmDeleteSelectedModal);
                        if (modal) modal.hide();

                        // Update trash list in real time
                        fetch('reservations.php?get_trash_html=1')
                            .then(response => response.text())
                            .then(html => {
                                var trashList = document.getElementById('trashBookingList');
                                if (trashList) trashList.innerHTML = html;
                            });

                        // Update trash count badge in real time
                        var trashBtnText = document.getElementById('trashBtnText');
                        if (trashBtnText) {
                            var match = trashBtnText.textContent.match(/\((\d+)\)/);
                            if (match) {
                                var count = parseInt(match[1], 10) + ids.length;
                                trashBtnText.textContent = 'Trash (' + count + ')';
                            }
                        }
                        // Show toast for moving to trash (delete selected)
                        var toastEl = document.getElementById('trashToast');
                        if (toastEl) {
                            toastEl.style.display = '';
                            var toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 2000 });
                            toast.show();
                            setTimeout(function() { location.reload(); }, 1800);
                        } else {
                            location.reload();
                        }
                    });
                });
            }
        }
        if (document.getElementById('trashBookingList')) {
          document.getElementById('trashBookingList').addEventListener('click', function(e) {
            if (e.target.closest('.restore-single-trash')) {
              var card = e.target.closest('.booking-card');
              var reservationId = card.getAttribute('data-reservation-id');
              fetch('restore_reservation.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'reservation_id=' + encodeURIComponent(reservationId)
              })
              .then(response => response.text())
              .then(data => {
                if (data.trim() === 'success') {
                  var toastEl = document.getElementById('restoreToast');
                  if (toastEl) {
                    toastEl.style.display = '';
                    var toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 2000 });
                    toast.show();
                    setTimeout(function() { location.reload(); }, 1800);
                  } else {
                    location.reload();
                  }
                } else {
                  alert('Failed to restore reservation.');
                }
              });
            }
            if (e.target.closest('.delete-single-trash')) {
              var card = e.target.closest('.booking-card');
              var reservationId = card.getAttribute('data-reservation-id');
              fetch('delete_forever_reservations.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'reservation_ids=' + encodeURIComponent(JSON.stringify([reservationId]))
              })
              .then(response => response.text())
              .then(data => {
                if (data.trim() === 'success') {
                  var toastEl = document.getElementById('permanentDeleteToast');
                  if (toastEl) {
                    toastEl.style.display = '';
                    var toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 2000 });
                    toast.show();
                    setTimeout(function() { location.reload(); }, 1800);
                  } else {
                    location.reload();
                  }
                } else {
                  alert('Failed to delete reservation.');
                }
              });
            }
          });
        }
    });
    </script>
    <script>
    function fetchUserReservations() {
        fetch('ajax_user_reservations.php')
            .then(response => response.text())
            .then(html => {
                var reservationList = document.getElementById('userReservationList');
                if (reservationList) reservationList.innerHTML = html;
            });
    }
    setInterval(fetchUserReservations, 10000); // every 10 seconds
    // Optionally, fetch immediately on page load
    fetchUserReservations();
    </script>
</body>
</html>