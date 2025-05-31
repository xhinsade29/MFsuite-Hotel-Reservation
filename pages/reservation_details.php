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
// Notification logic
$show_cancel_notification = isset($_GET['cancel']) && $_GET['cancel'] === 'requested';
// Calculate total amount (room price + selected services)
$total_amount = $booking['room_price'];
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
// Fetch cancellation reasons
$reasons = [];
$reason_sql = "SELECT reason_id, reason_text FROM tbl_cancellation_reason ORDER BY reason_text ASC";
$reason_result = $conn->query($reason_sql);
if ($reason_result && $reason_result->num_rows > 0) {
    while ($row = $reason_result->fetch_assoc()) {
        $reasons[] = $row;
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
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reservation Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
    <?php include '../components/user_navigation.php'; ?>
    <!-- Toast for Paid or Completed -->
    <?php if ($booking['payment_status'] === 'Paid' || $booking['status'] === 'completed'): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 1100">
      <div id="statusToast" class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php if ($booking['payment_status'] === 'Paid'): ?>
              Your payment has been received!
            <?php endif; ?>
            <?php if ($booking['status'] === 'completed'): ?>
              Reservation marked as completed. Thank you for staying with us!
            <?php endif; ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastEl = document.getElementById('statusToast');
        if (toastEl) {
            var toast = new bootstrap.Toast(toastEl, { delay: 3500 });
            toast.show();
        }
    });
    </script>
    <?php endif; ?>
    <?php if ($show_cancel_notification): ?>
    <div class="alert alert-info text-center">Your cancellation request has been sent. Please wait for admin approval.</div>
    <!-- Toast notification -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
      <div id="cancelToast" class="toast align-items-center text-bg-info border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <i class="bi bi-info-circle-fill me-2"></i>
            Your cancellation request has been sent!
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toastEl = document.getElementById('cancelToast');
        if (toastEl) {
            var toast = new bootstrap.Toast(toastEl, { delay: 2000 });
            toast.show();
            setTimeout(function() {
                window.location.reload();
            }, 2000); // Refresh after toast
        }
        // Auto-close modal if open
        var cancelModal = document.getElementById('cancelModal');
        if (cancelModal) {
            var modal = bootstrap.Modal.getInstance(cancelModal);
            if (modal) { modal.hide(); }
        }
    });
    </script>
    <?php endif; ?>
    <?php if ($booking['status'] === 'cancellation_requested'): ?>
        <!-- Recurring toast notification only -->
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
          <div id="cancelToast" class="toast align-items-center text-bg-info border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
              <div class="toast-body">
                <i class="bi bi-info-circle-fill me-2"></i>
                Your cancellation request has been sent and is awaiting admin approval.
              </div>
              <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
          </div>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toastEl = document.getElementById('cancelToast');
            if (toastEl) {
                var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
                toast.show();
            }
        });
        </script>
    <?php endif; ?>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="details-container w-100" style="max-width: 900px;">
            <div class="details-image">
                <img src="../assets/rooms/<?php echo htmlspecialchars($image_file); ?>" alt="Room Image" class="img-fluid rounded mb-3" style="max-height:300px;object-fit:cover;">
                <h4 class="text-warning mt-2"><?php echo htmlspecialchars($booking['type_name']); ?></h4>
                <p class="mb-3"><?php echo htmlspecialchars($booking['description']); ?></p>
            </div>
            <div class="details-content">
                <div class="user-details bg-dark rounded p-3 mb-3 w-100">
                    <div><strong>Name:</strong> <?php echo htmlspecialchars(trim($first_name . ' ' . $middle_name . ' ' . $last_name)); ?></div>
                    <div><strong>Email:</strong> <?php echo htmlspecialchars($user_email); ?></div>
                    <div><strong>Phone:</strong> <?php echo htmlspecialchars($phone_number); ?></div>
                    <div><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></div>
                </div>
                <ul class="list-group list-group-flush mb-3 w-100">
                    <li class="list-group-item bg-dark text-light"><strong>Check-in:</strong> <?php echo date('Y-m-d h:i A', strtotime($booking['check_in'])); ?></li>
                    <li class="list-group-item bg-dark text-light"><strong>Check-out:</strong> <?php echo date('Y-m-d h:i A', strtotime($booking['check_out'])); ?></li>
                    <?php if ($assigned_room_number): ?>
                    <li class="list-group-item bg-dark text-light"><strong>Room Number:</strong> <?php echo htmlspecialchars($assigned_room_number); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($included_services)): ?>
                    <li class="list-group-item bg-dark text-light"><strong>Included Room Services:</strong> <?php echo htmlspecialchars(implode(', ', $included_services)); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($booking['services'])): ?>
                    <li class="list-group-item bg-dark text-light"><strong>Selected Services:</strong> <?php echo htmlspecialchars($booking['services']); ?></li>
                    <?php endif; ?>
                    <li class="list-group-item bg-dark text-light"><strong>Total Amount:</strong> ₱<?php echo number_format($total_amount, 2); ?></li>
                    <li class="list-group-item bg-dark text-light"><strong>Reference Number:</strong> <?php echo htmlspecialchars($booking['reference_number'] ?? 'N/A'); ?></li>
                    <li class="list-group-item bg-dark text-light"><strong>Paid via:</strong> <?php echo htmlspecialchars($booking['payment_method']); ?></li>
                    <li class="list-group-item bg-dark text-light"><strong>Payment Status:</strong> <?php echo htmlspecialchars($booking['payment_status']); ?></li>
                    <li class="list-group-item bg-dark text-light"><strong>Date Booked:</strong> <?php echo htmlspecialchars($booking['date_created']); ?></li>
                    <?php if ($booking['status'] === 'approved'): ?>
                    <li class="list-group-item bg-dark text-success"><strong>Reservation Status:</strong> Approved by Admin</li>
                    <?php elseif ($booking['status'] === 'pending'): ?>
                    <li class="list-group-item bg-dark text-warning"><strong>Reservation Status:</strong> Pending Admin Approval</li>
                    <?php elseif ($booking['status'] === 'completed'): ?>
                    <li class="list-group-item bg-dark text-primary"><strong>Reservation Status:</strong> Completed</li>
                    <?php elseif ($booking['status'] === 'cancelled'): ?>
                    <li class="list-group-item bg-dark text-danger"><strong>Reservation Status:</strong> Cancelled</li>
                    <?php elseif ($booking['status'] === 'cancellation_requested'): ?>
                    <li class="list-group-item bg-dark text-info"><strong>Reservation Status:</strong> Cancellation Requested</li>
                    <?php elseif ($booking['status'] === 'denied'): ?>
                    <li class="list-group-item bg-dark text-secondary"><strong>Reservation Status:</strong> Cancellation Denied</li>
                    <?php endif; ?>
                </ul>
                <a href="reservations.php" class="btn btn-warning mb-2">Back to My Reservations</a>
                <?php if ($booking['status'] === 'cancellation_requested'): ?>
                    <span class="badge bg-warning text-dark mb-2">Requested for Cancellation</span>
                <?php elseif ($booking['status'] === 'cancelled'): ?>
                    <span class="badge bg-danger mb-2">Cancelled</span>
                <?php elseif ($booking['status'] === 'denied'): ?>
                    <span class="badge bg-secondary mb-2">Cancellation Denied</span>
                <?php elseif ($booking['status'] === 'completed'): ?>
                    <span class="badge bg-success mb-2">Completed</span>
                <?php else: ?>
                    <button id="cancelBtn" class="btn btn-danger mb-2" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel Reservation</button>
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
            <form method="POST" action="../functions/cancel_booking.php" id="cancelForm">
                <input type="hidden" name="reservation_id" value="<?php echo $booking['reservation_id']; ?>">
                <div class="mb-3 text-start">
                    <label for="reason_id" class="form-label">Reason for cancellation:</label>
                    <select class="form-select" id="reason_id" name="reason_id" required onchange="toggleOtherReason()">
                        <option value="">Select a reason</option>
                        <?php foreach ($reasons as $reason): ?>
                            <option value="<?php echo $reason['reason_id']; ?>"><?php echo htmlspecialchars($reason['reason_text']); ?></option>
                        <?php endforeach; ?>
                        <option value="other">Other (please specify)</option>
                    </select>
                </div>
                <div class="mb-3 text-start" id="otherReasonDiv" style="display:none;">
                    <label for="other_reason" class="form-label">Other reason:</label>
                    <textarea class="form-control" id="other_reason" name="other_reason" rows="3" placeholder="Please provide your reason..."></textarea>
                </div>
                <button type="submit" class="btn btn-danger fw-bold">Yes, Cancel Reservation</button>
                <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">No, Keep Reservation</button>
            </form>
            <script>
            function toggleOtherReason() {
                var select = document.getElementById('reason_id');
                var otherDiv = document.getElementById('otherReasonDiv');
                if (select.value === 'other') {
                    otherDiv.style.display = 'block';
                    document.getElementById('other_reason').setAttribute('required', 'required');
                } else {
                    otherDiv.style.display = 'none';
                    document.getElementById('other_reason').removeAttribute('required');
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                var cancelForm = document.getElementById('cancelForm');
                if (cancelForm) {
                    cancelForm.addEventListener('submit', function() {
                        var btn = document.querySelector('#cancelModal button[type="submit"]');
                        if (btn) {
                            btn.disabled = true;
                            btn.textContent = 'Cancelling...';
                        }
                    });
                }
            });
            </script>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 