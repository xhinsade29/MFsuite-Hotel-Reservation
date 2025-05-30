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
    $sql = "SELECT r.*, r.status AS reservation_status, rt.type_name, rt.description, rt.room_price, 
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
    </style>
</head>
<body>
    <?php include '../components/user_navigation.php'; ?>
    
    <div class="content d-flex flex-column align-items-center justify-content-center">
        <h1 class="text-center"><i class="fas fa-calendar-alt"></i> My Reservations</h1>
        
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
                    <div class="form-check ms-3">
                        <input class="form-check-input" type="checkbox" id="selectAllBookings">
                        <label class="form-check-label" for="selectAllBookings">Select All</label>
                    </div>
                    <button id="deleteSelectedBtn" class="btn btn-danger btn-sm ms-2" disabled>Delete Selected</button>
                </div>
                <div class="text-end text-muted" style="font-size:0.95em;">* Deleting a booking here only hides it from your view</div>
            </div>
            <?php if (count($user_bookings) > 0): ?>
            <div class="container py-5" id="bookingList">
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
                $total_amount = $booking['room_price'];
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
                ?>
                <div class="booking-card position-relative" data-date="<?php echo $booking['date_created']; ?>" data-status="<?php echo strtolower($booking['reservation_status']); ?>">
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
                        <div class="meta"><strong>Date Booked:</strong> <?php echo date('Y-m-d h:i A', strtotime($booking['date_created'])); ?></div>
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
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <!-- Booking Success Modal -->
    <div class="modal fade" id="bookingSuccessModal" tabindex="-1" aria-labelledby="bookingSuccessLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="bookingSuccessLabel">Booking Successful!</h5>
          </div>
          <div class="modal-body">
            Your booking was successful. Where would you like to go next?
          </div>
          <div class="modal-footer">
            <a href="reservations.php" class="btn btn-primary">My Bookings</a>
            <a href="../index.php" class="btn btn-secondary">Back to Home</a>
          </div>
        </div>
      </div>
    </div>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('bookingSuccessModal'));
        modal.show();
        setTimeout(function() {
          window.location.href = 'reservations.php';
        }, 3000); // 3 seconds
      });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // UI-only delete (single)
        document.querySelectorAll('.delete-booking-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var card = btn.closest('.booking-card');
                if (card) card.remove();
            });
        });
        // Sorting
        var sortSelect = document.getElementById('sortSelect');
        var bookingList = document.getElementById('bookingList');
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
        // Select All and Delete Selected
        var selectAll = document.getElementById('selectAllBookings');
        var deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        function updateDeleteBtn() {
            var checked = bookingList.querySelectorAll('.booking-checkbox:checked');
            deleteSelectedBtn.disabled = checked.length === 0;
        }
        selectAll.addEventListener('change', function() {
            var checkboxes = bookingList.querySelectorAll('.booking-checkbox');
            checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
            updateDeleteBtn();
        });
        bookingList.addEventListener('change', function(e) {
            if (e.target.classList.contains('booking-checkbox')) {
                var all = bookingList.querySelectorAll('.booking-checkbox');
                var checked = bookingList.querySelectorAll('.booking-checkbox:checked');
                selectAll.checked = all.length === checked.length;
                updateDeleteBtn();
            }
        });
        deleteSelectedBtn.addEventListener('click', function() {
            var checked = bookingList.querySelectorAll('.booking-checkbox:checked');
            checked.forEach(function(cb) {
                var card = cb.closest('.booking-card');
                if (card) card.remove();
            });
            updateDeleteBtn();
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>