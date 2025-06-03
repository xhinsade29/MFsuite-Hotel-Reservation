<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
$conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$reservation_id = intval($_GET['id'] ?? 0);
if (!$reservation_id) { die("Invalid reservation."); }

// Fetch reservation details
$sql = "SELECT r.*, rt.type_name, rt.description, rt.nightly_rate,
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

// Ensure reference number is always available
$reference_number = $booking['reference_number'] ?? '';
if (empty($reference_number) && isset($booking['payment_id'])) {
    $pay_sql = "SELECT reference_number FROM tbl_payment WHERE payment_id = " . intval($booking['payment_id']);
    $pay_res = $conn->query($pay_sql);
    if ($pay_res && $pay_res->num_rows > 0) {
        $reference_number = $pay_res->fetch_assoc()['reference_number'];
    }
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
// Fetch user details including wallet_id
$user_sql = "SELECT first_name, middle_name, last_name, user_email, phone_number, address, bank_account_number, paypal_email, credit_card_number, gcash_number, wallet_id FROM tbl_guest WHERE guest_id = ?";
$stmt_user = $conn->prepare($user_sql);
$stmt_user->bind_param("i", $booking['guest_id']);
$stmt_user->execute();
$stmt_user->bind_result($first_name, $middle_name, $last_name, $user_email, $phone_number, $address, $bank_account_number, $paypal_email, $credit_card_number, $gcash_number, $wallet_id);
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
// Fetch cancellation details if cancelled
$cancellation_details = null;
if ($booking['status'] === 'cancelled') {
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

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reservation Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #23234a 0%, #1e1e2f 100%); color: #fff; font-family: 'Segoe UI', Arial, sans-serif; }
        body.light-mode {
            background: #f8f9fa !important;
            color: #23234a !important;
        }
        body.light-mode .details-container, body.light-mode .details-image, body.light-mode .details-content, body.light-mode .card-modern {
            background: #fff !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .details-image img {
            box-shadow: 0 4px 18px rgba(255,140,0,0.10);
        }
        body.light-mode .inclusions-card {
            background: #f7f7fa !important;
            color: #23234a !important;
        }
        body.light-mode .inclusions-card h6,
        body.light-mode .section-title {
            color: #ff8c00 !important;
        }
        body.light-mode .badge {
            background: #ffe5b4 !important;
            color: #ff8c00 !important;
        }
        body.light-mode .alert-danger {
            background: #fff0e1 !important;
            color: #c0392b !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .alert-success {
            background: #eaffea !important;
            color: #27ae60 !important;
            border: 1px solid #b3ffb3 !important;
        }
        body.light-mode .modal-content {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode .modal-header, body.light-mode .modal-footer {
            background: #f7f7fa !important;
            color: #23234a !important;
        }
        body.light-mode .btn-outline-light {
            border-color: #ff8c00 !important;
            color: #23234a !important;
            background: #fff !important;
        }
        body.light-mode .btn-outline-light:hover {
            background: #ff8c00 !important;
            color: #fff !important;
            border-color: #ff8c00 !important;
        }
        .details-container {
            display: flex;
            flex-direction: row;
            gap: 0;
            max-width: 1200px;
            margin: 32px auto 16px auto;
            background: none;
            border-radius: 22px;
            box-shadow: none;
            padding: 0;
        }
        .details-image {
            background: linear-gradient(135deg, #18182f 60%, #23234a 100%);
            border-radius: 22px 0 0 22px;
            padding: 36px 32px 32px 32px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
            min-width: 380px;
            max-width: 420px;
            min-height: 100%;
            box-shadow: none;
        }
        .details-image img {
            width: 100%;
            height: 260px;
            object-fit: cover;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.18);
        }
        .inclusions-card {
            background: rgba(35, 35, 74, 0.95);
            border-radius: 14px;
            padding: 18px 22px 12px 22px;
            margin-top: 18px;
            margin-bottom: 18px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.10);
        }
        .inclusions-card h6 {
            color: #ffa533;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .inclusions-card ul {
            margin-bottom: 0;
        }
        .details-content {
            background: linear-gradient(135deg, #23234a 60%, #18182f 100%);
            border-radius: 0 22px 22px 0;
            padding: 40px 40px 36px 40px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            min-width: 0;
            flex: 1 1 0%;
            min-height: 100%;
            box-shadow: none;
            width: 100%;
        }
        .card-modern {
            background: rgba(24,24,47,0.98);
            border-radius: 18px;
            padding: 36px 36px 28px 36px;
            box-shadow: 0 2px 18px rgba(0,0,0,0.13);
            margin-bottom: 0;
            width: 100%;
            margin-top: 0;
        }
        .card-modern h2.fw-bold {
            color: #ffa533;
            letter-spacing: 1px;
            margin-bottom: 1.2rem;
            font-size: 1.08em;
            font-weight: 600;
        }
        .divider {
            border-bottom: 1.5px solid #35356a;
            margin: 18px 0 24px 0;
        }
        .details-section {
            margin-bottom: 18px;
        }
        .details-section:last-child {
            margin-bottom: 0;
        }
        .details-section .section-title {
            color: #ffa533;
            font-weight: 600;
            font-size: 1.08em;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }
        .details-section .icon {
            color: #ffa533;
            margin-right: 8px;
            font-size: 1.1em;
        }
        .details-section .info-row {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        .details-section .info-row:last-child {
            margin-bottom: 0;
        }
        .badge { font-size: 1em; padding: 0.6em 1.2em; border-radius: 1em; }
        @media (max-width: 991px) {
            .details-container { flex-direction: column; border-radius: 22px; max-width: 98vw; }
            .details-image, .details-content { border-radius: 22px 22px 0 0; min-width: unset; max-width: unset; padding: 28px 12px; }
            .card-modern { padding: 24px 10px 18px 10px; }
        }
    </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
    <?php include '../components/user_navigation.php'; ?>
    <?php if ($booking['status'] === 'cancellation_requested'): ?>
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
            // Auto-close modal if open
            var cancelModal = document.getElementById('cancelModal');
            if (cancelModal) {
                var modal = bootstrap.Modal.getInstance(cancelModal);
                if (modal) { modal.hide(); }
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
                    <?php if ($booking['status'] !== 'cancellation_requested' && $booking['status'] !== 'cancelled' && $booking['status'] !== 'denied' && $booking['status'] !== 'completed'): ?>
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
                            echo $nights;
                        ?></div>
                        <div class="info-row"><strong>Room Number:</strong>&nbsp;<?php echo $assigned_room_number ? htmlspecialchars($assigned_room_number) : '<span class="text-secondary">Not assigned</span>'; ?></div>
                        <?php if (!empty($booking['services'])): ?>
                        <div class="info-row"><strong>Selected Services:</strong>&nbsp;<?php echo htmlspecialchars($booking['services']); ?></div>
                        <?php endif; ?>
                        <div class="info-row">
                            <strong>Status:</strong>&nbsp;
                            <?php if ($booking['status'] === 'approved'): ?>
                                <span class="badge bg-success">Approved by Admin</span>
                            <?php elseif ($booking['status'] === 'pending'): ?>
                                <span class="badge bg-warning text-dark">Pending Admin Approval</span>
                            <?php elseif ($booking['status'] === 'completed'): ?>
                                <span class="badge bg-primary">Completed</span>
                            <?php elseif ($booking['status'] === 'cancelled'): ?>
                                <span class="badge bg-danger mb-2">Cancelled</span>
                                <?php if ($cancellation_details): ?>
                                <div class="alert alert-danger mt-2" style="background:rgba(255,0,0,0.08);color:#ffb3b3;border:none;">
                                    <strong>Cancellation Details:</strong><br>
                                    <span><strong>Reason:</strong> <?php echo htmlspecialchars($cancellation_details['reason_text'] ?? '-'); ?></span><br>
                                    <span><strong>Cancelled By:</strong> <?php echo htmlspecialchars($cancellation_details['canceled_by'] ?? '-'); ?></span><br>
                                    <span><strong>Date Cancelled:</strong> <?php echo htmlspecialchars(date('Y-m-d h:i A', strtotime($cancellation_details['date_canceled'] ?? ''))); ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($booking['payment_status'] === 'Refunded'): ?>
                                <div class="alert alert-success mt-2" style="background:rgba(0,255,0,0.08);color:#b3ffb3;border:none;">
                                    <strong>Refunded:</strong> The payment for this reservation has been refunded and added to your wallet.
                                </div>
                                <?php endif; ?>
                            <?php elseif ($booking['status'] === 'cancellation_requested'): ?>
                                <span class="badge bg-info text-dark">Cancellation Requested</span>
                            <?php elseif ($booking['status'] === 'denied'): ?>
                                <span class="badge bg-secondary">Cancellation Denied</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="divider"></div>
                    <div class="details-section">
                        <div class="section-title"><i class="bi bi-credit-card icon"></i>Payment Information</div>
                        <div class="info-row"><strong>Paid via:</strong>&nbsp;<?php echo htmlspecialchars($booking['payment_method']); ?></div>
                        <?php
                        $acc_info = '-';
                        $pm = strtolower($booking['payment_method']);
                        if (strpos($pm, 'bank') !== false) {
                            $acc_info = $bank_account_number ?: '-';
                        } elseif (strpos($pm, 'gcash') !== false) {
                            $acc_info = $gcash_number ?: '-';
                        } elseif (strpos($pm, 'paypal') !== false) {
                            $acc_info = $paypal_email ?: '-';
                        } elseif (strpos($pm, 'credit') !== false) {
                            $acc_info = $credit_card_number ?: '-';
                        } elseif ($pm === 'wallet') {
                            $acc_info = $wallet_id ? $wallet_id : 'Paid via Wallet';
                        } elseif ($pm === 'cash') {
                            $acc_info = 'Pay at front desk';
                        }
                        ?>
                        <div class="info-row"><strong>Account Info:</strong>&nbsp;<?php echo htmlspecialchars($acc_info); ?></div>
                        <div class="info-row"><strong>Total Amount:</strong>&nbsp;₱<?php echo number_format($total_amount, 2); ?></div>
                        <div class="info-row"><strong>Payment Status:</strong>&nbsp;<?php echo htmlspecialchars($booking['payment_status']); ?></div>
                        <div class="info-row"><strong>Date Booked:</strong>&nbsp;<?php echo htmlspecialchars($booking['date_created']); ?></div>
                    </div>
                </div>
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
            <form method="POST" action="cancel_booking.php" id="cancelForm">
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
    <script>
    setInterval(updateNotifTrashCount, 2000); // for every 2 seconds
    var trashModal = document.getElementById('trashModal');
    trashModal.addEventListener('hidden.bs.modal', function () {
        document.body.focus(); // or focus another visible element
    });
    </script>
</body>
</html> 