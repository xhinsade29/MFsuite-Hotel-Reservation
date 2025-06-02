<?php
include('../functions/db_connect.php');
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
$room_type_id = isset($_GET['room_type_id']) ? intval($_GET['room_type_id']) : 0;
$room = null;
$services = [];
if ($room_type_id) {
    $sql = "SELECT rt.*, GROUP_CONCAT(CONCAT(s.service_name, '|', s.service_description) SEPARATOR '||') as services FROM tbl_room_type rt LEFT JOIN tbl_room_services rs ON rt.room_type_id = rs.room_type_id LEFT JOIN tbl_services s ON rs.service_id = s.service_id WHERE rt.room_type_id = $room_type_id GROUP BY rt.room_type_id";
    $result = mysqli_query($mycon, $sql);
    if ($result && $result->num_rows > 0) {
        $room = $result->fetch_assoc();
        if ($room['services']) {
            foreach (explode('||', $room['services']) as $service) {
                list($name, $desc) = explode('|', $service);
                $services[] = ['name' => $name, 'desc' => $desc];
            }
        }
    }
}
$room_images = [
    1 => ['standard.avif', 'standard2.avif', 'standard3.avif', 'standard4.jpg', 'standard5.avif'],
    2 => ['deluxe1.jpg', 'deluxe2.avif', 'deluxe3.avif', 'deluxe4.avif', 'deluxe5.avif'],
    3 => ['superior.jpg', 'superior2.jpg', 'superior3.jpg', 'superior4.jpg', 'superior5.jpg'],
    4 => ['family_suite.jpg', 'family_suite2.jpg', 'family_suite3.jpg', 'family_suite4.jpg', 'family_suite5.jpg'],
    5 => ['executive.jpg', 'executive2.jpg', 'executive3.jpg', 'executive4.jpg', 'executive5.jpg'],
    6 => ['presidential.avif', 'presidential2.jpg', 'presidential3.jpg', 'presidential4.jpg', 'presidential5.jpg']
];
$images = $room_images[$room_type_id] ?? ['standard.avif'];
// Fetch payment types
$payment_types = [];
$payment_result = mysqli_query($mycon, "SELECT payment_type_id, payment_name FROM tbl_payment_types");
if ($payment_result && mysqli_num_rows($payment_result) > 0) {
    while ($row = mysqli_fetch_assoc($payment_result)) {
        $payment_types[] = $row;
    }
}
// Fetch wallet balance if logged in
$wallet_balance = null;
$wallet_id = '';
if (isset($_SESSION['guest_id'])) {
    $guest_id = $_SESSION['guest_id'];
    $sql_wallet = "SELECT wallet_balance, wallet_id FROM tbl_guest WHERE guest_id = ?";
    $stmt_wallet = mysqli_prepare($mycon, $sql_wallet);
    mysqli_stmt_bind_param($stmt_wallet, "i", $guest_id);
    mysqli_stmt_execute($stmt_wallet);
    mysqli_stmt_bind_result($stmt_wallet, $wallet_balance, $wallet_id);
    mysqli_stmt_fetch($stmt_wallet);
    mysqli_stmt_close($stmt_wallet);
}
// 1. Fetch user payment account info if logged in
$user_payment = [
    'bank_account_number' => '',
    'paypal_email' => '',
    'credit_card_number' => '',
    'gcash_number' => ''
];
if (isset($_SESSION['guest_id'])) {
    $guest_id = $_SESSION['guest_id'];
    $sql = "SELECT bank_account_number, paypal_email, credit_card_number, gcash_number FROM tbl_guest WHERE guest_id = ?";
    $stmt = mysqli_prepare($mycon, $sql);
    mysqli_stmt_bind_param($stmt, "i", $guest_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_payment['bank_account_number'], $user_payment['paypal_email'], $user_payment['credit_card_number'], $user_payment['gcash_number']);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$admin_id = 1;
$admin_result = $mycon->query("SELECT admin_id FROM tbl_admin LIMIT 1");
if ($admin_result && $admin_result->num_rows > 0) {
    $row = $admin_result->fetch_assoc();
    $admin_id = $row['admin_id'];
}
// After fetching $room, add PHP check for room availability
$room_fully_booked = false;
if ($room_type_id) {
    // Only check if room type is selected
    $sql_count = "SELECT COUNT(*) as total FROM tbl_room WHERE room_type_id = $room_type_id";
    $result_count = mysqli_query($mycon, $sql_count);
    $total_rooms = 0;
    if ($result_count && $row_count = mysqli_fetch_assoc($result_count)) {
        $total_rooms = $row_count['total'];
    }
    // Default to today for initial check
    $today = date('Y-m-d');
    $sql_booked = "SELECT COUNT(DISTINCT r.room_id) as booked FROM tbl_reservation r WHERE r.room_id IN (SELECT room_id FROM tbl_room WHERE room_type_id = $room_type_id) AND r.status IN ('pending','approved','completed') AND r.check_in < '$today 23:59:59' AND r.check_out > '$today 00:00:00'";
    $result_booked = mysqli_query($mycon, $sql_booked);
    $booked_rooms = 0;
    if ($result_booked && $row_booked = mysqli_fetch_assoc($result_booked)) {
        $booked_rooms = $row_booked['booked'];
    }
    if ($total_rooms > 0 && $booked_rooms >= $total_rooms) {
        $room_fully_booked = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
    <title>Book Room</title>
    <style>
        body { background: #1e1e2f; color: #fff; }
        /* Light mode overrides */
        body.light-mode {
            background: #f8f9fa !important;
            color: #23234a !important;
        }
        body.light-mode .booking-container {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode .booking-room-details {
            background: #f7f7fa !important;
            color: #23234a !important;
        }
        body.light-mode .booking-room-details h2,
        body.light-mode .booking-room-details .price {
            color: #ff8c00 !important;
        }
        body.light-mode .booking-room-details .service-item {
            background: #f1f1f1 !important;
            color: #23234a !important;
        }
        body.light-mode .booking-room-details .service-name {
            color: #ff8c00 !important;
        }
        body.light-mode .booking-room-details .service-description {
            color: #666 !important;
        }
        body.light-mode .booking-form-section {
            background: #fff !important;
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
        body.light-mode .btn-primary, body.light-mode .btn-warning {
            background: linear-gradient(90deg, #ff8c00, #ffa533) !important;
            color: #fff !important;
            border: none !important;
        }
        body.light-mode .btn-outline-secondary {
            border-color: #ff8c00 !important;
            color: #ff8c00 !important;
        }
        body.light-mode .btn-outline-secondary:hover {
            background: #ff8c00 !important;
            color: #fff !important;
        }
        body.light-mode .alert-info {
            background: #ffe5b4 !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .alert-danger {
            background: #fff0e1 !important;
            color: #c0392b !important;
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
        body.light-mode #bookingModalReferenceNumberDisplay,
        body.light-mode #cashModalReferenceNumberDisplay {
            color: #23234a !important;
        }
        /* End light mode overrides */
        .booking-container { display: flex; flex-wrap: wrap; gap: 32px; max-width: 1200px; margin: 40px auto; background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 0; }
        .booking-room-details { flex: 1.2; min-width: 340px; background: #18182f; border-radius: 16px 0 0 16px; padding: 36px 32px; display: flex; flex-direction: column; align-items: flex-start; }
        .booking-room-details h2 { color: #FF8C00; font-weight: 700; margin-bottom: 12px; }
        .booking-room-details .price { color: #FF8C00; font-size: 1.3em; font-weight: bold; margin: 18px 0 10px; }
        .booking-room-details .occupancy { color: #bdbdbd; margin-bottom: 10px; }
        .booking-room-details .services-list { margin-top: 18px; }
        .booking-room-details .service-item { background: rgba(255,255,255,0.05); border-radius: 8px; padding: 10px 14px; margin-bottom: 8px; }
        .booking-room-details .service-name { color: #fff; font-weight: 500; }
        .booking-room-details .service-description { color: #bdbdbd; font-size: 0.95em; }
        .booking-gallery { width: 100%; margin-bottom: 18px; }
        .booking-gallery img { width: 100%; height: 220px; object-fit: cover; border-radius: 10px; }
        .booking-form-section { flex: 1; min-width: 340px; background: #23234a; border-radius: 0 16px 16px 0; padding: 36px 32px; }
        @media (max-width: 991px) { .booking-container { flex-direction: column; border-radius: 16px; } .booking-room-details, .booking-form-section { border-radius: 16px 16px 0 0; min-width: unset; padding: 24px 12px; } }
    </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
<?php include('../components/user_navigation.php'); ?>

<div class="booking-container">
    <div class="booking-room-details">
        <?php if ($room): ?>
            <div class="booking-gallery">
                <img src="../assets/rooms/<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($room['type_name']); ?>">
            </div>
            <h2><?php echo htmlspecialchars($room['type_name']); ?></h2>
            <div class="price">₱<?php echo number_format(isset($room['nightly_rate']) ? $room['nightly_rate'] : 0, 2); ?></div>
            <div class="occupancy"><i class="bi bi-people"></i> Max Occupancy: <?php echo htmlspecialchars($room['max_occupancy']); ?></div>
            <p><?php echo htmlspecialchars($room['description']); ?></p>
            <div class="services-list">
                <h5>Room Inclusions</h5>
                <?php foreach ($services as $service): ?>
                    <div class="service-item">
                        <div class="service-name"><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($service['name']); ?></div>
                        <div class="service-description"><?php echo htmlspecialchars($service['desc']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Room not found.</p>
        <?php endif; ?>
    </div>
    <div class="booking-form-section">
        <h4 class="text-warning fw-bold mb-4">Book this Room</h4>
        <?php if ($room_fully_booked): ?>
            <div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle"></i> All rooms of this type are fully booked or occupied for today. Please select another room type or date.</div>
        <?php endif; ?>
        <?php if (isset($_SESSION['guest_id']) && $wallet_balance !== null): ?>
            <div class="alert alert-info mb-3"><i class="bi bi-wallet2"></i> Your Wallet Balance: <b>₱<?php echo number_format($wallet_balance, 2); ?></b></div>
            <input type="hidden" id="userWalletBalance" value="<?php echo $wallet_balance; ?>">
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?> </div>
        <?php endif; ?>
        <div id="formErrorMsg" class="alert alert-danger d-none"></div>
        <form id="bookingForm" action="../functions/bookings.php" method="POST" class="bg-secondary-subtle p-4 rounded-3 shadow-sm text-dark needs-validation" novalidate <?php if ($room_fully_booked) echo 'style="pointer-events:none;opacity:0.6;"'; ?>>
            <input type="hidden" name="room_type_id" value="<?php echo htmlspecialchars($room_type_id); ?>">
            <div class="row g-3">
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Check-in</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control flatpickr-datetime" name="checkin_datetime" id="checkin_datetime" placeholder="Select check-in date & time" required autocomplete="off">
                        </div>
                        <div class="invalid-feedback">Check-in date and time are required.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Check-out</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control flatpickr-datetime" name="checkout_datetime" id="checkout_datetime" placeholder="Select check-out date & time" required autocomplete="off" readonly>
                        </div>
                        <div class="invalid-feedback">Check-out date and time are required.</div>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Number of Nights</label>
                        <input type="number" class="form-control" name="number_of_nights" id="number_of_nights" min="1" value="1" required>
                        <div class="invalid-feedback">Please enter the number of nights.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Method</label>
                        <select class="form-control" name="payment_id" id="payment_id" required>
                            <option value="">Select Payment Method</option>
                            <?php foreach ($payment_types as $ptype):
                                $pname = strtolower($ptype['payment_name']);
                                $disabled = false;
                                $acc_display = '';
                                if ($pname === 'gcash') {
                                    $disabled = empty($user_payment['gcash_number']);
                                    $acc_display = $user_payment['gcash_number'];
                                } elseif (strpos($pname, 'bank') !== false) {
                                    $disabled = empty($user_payment['bank_account_number']);
                                    $acc_display = $user_payment['bank_account_number'];
                                } elseif ($pname === 'paypal') {
                                    $disabled = empty($user_payment['paypal_email']);
                                    $acc_display = $user_payment['paypal_email'];
                                } elseif ($pname === 'credit card') {
                                    $disabled = empty($user_payment['credit_card_number']);
                                    $acc_display = $user_payment['credit_card_number'];
                                } elseif ($pname === 'wallet') {
                                    $disabled = ($wallet_balance === null || $wallet_balance <= 0);
                                    $acc_display = 'Wallet Balance: ₱' . number_format($wallet_balance, 2);
                                }
                            ?>
                                <option value="<?php echo $ptype['payment_type_id']; ?>" data-name="<?php echo htmlspecialchars($pname); ?>" data-acc="<?php echo htmlspecialchars($acc_display); ?>" <?php echo $disabled ? 'disabled' : ''; ?>><?php echo htmlspecialchars($ptype['payment_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a payment method.</div>
                        <div id="noPaymentAccountsMsg" class="text-danger mt-2 d-none">No payment account/email found. Please update your profile to add a payment method.</div>
                    </div>
                </div>
                <!-- Account info box below Number of Nights -->
                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <div id="selectedPaymentAccountInfo" class="alert alert-info d-none mb-2" style="font-size:1.05em;"></div>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Total Amount</label>
                        <input type="text" class="form-control" name="total_amount" value="<?php echo $room ? number_format(isset($room['nightly_rate']) ? $room['nightly_rate'] : 0, 2) : ''; ?>" readonly>
                    </div>
                </div>
                <div class="mb-2 mt-2">
                    <label class="form-label">Special Requests</label>
                    <textarea class="form-control" name="requests" rows="2" placeholder="Optional"></textarea>
                </div>
                <input type="hidden" id="reference_number" name="reference_number" value="">
                <div class="d-grid mt-3">
                    <button type="button" class="btn btn-warning btn-lg fw-bold shadow-sm" id="openConfirmModalBtn">Book Now</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Booking Confirmation Modal (used for Cash payment) -->
<div class="modal fade" id="confirmBookingModal" tabindex="-1" aria-labelledby="confirmBookingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light rounded-4 shadow-lg border-0">
      <div class="modal-header border-0 pb-0 justify-content-center bg-transparent">
        <h4 class="modal-title w-100 text-center fw-bold text-warning" id="confirmBookingModalLabel">Confirm Booking</h4>
        <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p>Are you sure you want to book this room?</p>
        <div class="mt-4 mb-4">
          <h5 class="text-info">Reference Number:</h5>
          <p class="fs-4 fw-bold text-white" id="cashModalReferenceNumberDisplay"></p>
        </div>
        <div class="d-flex justify-content-center gap-3 mt-4">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-warning fw-bold" id="confirmBookingBtnCash">Yes, Book Now</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Payment Details Modal (used for Non-Cash payments) -->
<div class="modal fade" id="bookingPaymentDetailsModal" tabindex="-1" aria-labelledby="bookingPaymentDetailsModalLabel" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content bg-dark text-light rounded-4 shadow-lg border-0">
  <div class="modal-header border-0 pb-0 justify-content-center bg-transparent">
    <h4 class="modal-title w-100 text-center fw-bold text-warning" id="bookingPaymentDetailsModalLabel">Complete Payment</h4>
    <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
  </div>
  <div class="modal-body text-center">
    <p>Please make your payment using the details below and include the generated reference number:</p>
    <div class="mt-4 mb-4">
      <h5 class="text-info">Reference Number:</h5>
      <p class="fs-4 fw-bold text-white" id="bookingModalReferenceNumberDisplay"></p>
    </div>
    <div id="bookingModalAccountInfo" class="alert alert-info mb-3" style="font-size:1.05em;display:none;"></div>
    <p class="text-muted">You can proceed to payment after confirming. Your booking status will be updated upon admin payment confirmation.</p>
    <div class="d-flex justify-content-center gap-3 mt-4">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      <button type="button" class="btn btn-success fw-bold" id="confirmBookingBtnPayment">Confirm & Proceed</button>
    </div>
  </div>
</div>
</div>
</div>

<!-- Toast Container for booking success -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000; min-width: 320px;">
    <div class="toast toast-success" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000" id="bookingSuccessToast" style="display:none;">
        <div class="toast-header">
            <i class="bi bi-check-circle-fill text-success me-2"></i>
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            Your reservation was placed successfully!
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/en.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var confirmModal = new bootstrap.Modal(document.getElementById('confirmBookingModal'));
    var bookingPaymentDetailsModal = new bootstrap.Modal(document.getElementById('bookingPaymentDetailsModal'));
    var openBtn = document.getElementById('openConfirmModalBtn');
    var confirmBtnCash = document.getElementById('confirmBookingBtnCash');
    var confirmBtnPayment = document.getElementById('confirmBookingBtnPayment');
    var bookingForm = document.getElementById('bookingForm');
    var errorMsg = document.getElementById('formErrorMsg');
    var paymentSelect = document.getElementById('payment_id');
    var walletBalanceInput = document.getElementById('userWalletBalance');
    var totalAmountInput = document.querySelector('input[name="total_amount"]');
    var bookNowBtn = document.getElementById('openConfirmModalBtn');
    var walletWarning = null;
    var referenceNumberInput = document.getElementById('reference_number');
    var bookingModalReferenceNumberDisplay = document.getElementById('bookingModalReferenceNumberDisplay');
    var referenceNumberInputRow = document.getElementById('referenceNumberInputRow');
    var referenceNumberInputField = document.getElementById('reference_number_input');
    var selectedPaymentAccountInfo = document.getElementById('selectedPaymentAccountInfo');

    // Function to generate a booking reference number
    function generateBookingReferenceNumber() {
        return 'BOOK' + Math.random().toString(16).substr(2, 8).toUpperCase() + Math.random().toString(16).substr(2, 4).toUpperCase();
    }

    // --- Date/Nights/Total Calculation Functions ---
    function calculateNights() {
        var checkin = document.getElementById('checkin_datetime').value;
        var checkout = document.getElementById('checkout_datetime').value;
        var nightsInput = document.getElementById('number_of_nights');
        if (checkin && checkout) {
            var checkinDate = new Date(checkin);
            var checkoutDate = new Date(checkout);
            var diffTime = checkoutDate - checkinDate;
            var nights = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
            nightsInput.value = nights;
        }
        updateTotalAmount();
    }

    function updateCheckoutFromNights() {
        var checkin = document.getElementById('checkin_datetime').value;
        var nights = parseInt(document.getElementById('number_of_nights').value) || 1;
        var checkoutInput = document.getElementById('checkout_datetime');
        if (checkin) {
            var checkinDate = new Date(checkin);
            checkinDate.setDate(checkinDate.getDate() + nights);
            // Format date back for flatpickr
            var year = checkinDate.getFullYear();
            var month = ('0' + (checkinDate.getMonth() + 1)).slice(-2);
            var day = ('0' + checkinDate.getDate()).slice(-2);
            var hours = ('0' + checkinDate.getHours()).slice(-2);
            var minutes = ('0' + checkinDate.getMinutes()).slice(-2);
            var ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12; hours = hours ? hours : 12; // the hour '0' should be '12'
            var formatted = `${year}-${month}-${day} ${hours}:${minutes} ${ampm}`;
            checkoutInput.value = formatted;
        }
    }

    function updateTotalAmount() {
        var nights = parseInt(document.getElementById('number_of_nights').value) || 1;
        var nightlyRate = <?php echo isset($room['nightly_rate']) ? floatval($room['nightly_rate']) : 0; ?>;
        var total = nights * nightlyRate;
        // Add selected services if any (implement if needed)
        var totalAmountInput = document.querySelector('input[name="total_amount"]');
        totalAmountInput.value = total.toLocaleString('en-US', {minimumFractionDigits:2});
        // Trigger wallet sufficiency check if present
        if (typeof checkWalletSufficiency === 'function') checkWalletSufficiency();
    }

    // --- Event Listeners ---
    document.getElementById('checkin_datetime').addEventListener('change', function() {
        calculateNights();
        updateCheckoutFromNights();
        checkRoomAvailability(); // Check availability after dates change
    });

    document.getElementById('number_of_nights').addEventListener('input', function() {
        updateTotalAmount();
        updateCheckoutFromNights(); // Update checkout date when nights change
        checkWalletSufficiency(); // Re-check sufficiency after total updates
    });

    document.getElementById('payment_id').addEventListener('change', checkWalletSufficiency); // Re-check wallet sufficiency when payment method changes

    function updateSelectedPaymentAccountInfo() {
        var selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
        var paymentName = selectedOption.getAttribute('data-name');
        var acc = selectedOption.getAttribute('data-acc');
        if (paymentName && paymentName !== 'cash' && paymentName !== 'wallet' && acc) {
            var label = '';
            if (paymentName === 'gcash') label = 'GCash Number';
            else if (paymentName.indexOf('bank') !== -1) label = 'Bank Account Number';
            else if (paymentName === 'paypal') label = 'PayPal Email';
            else if (paymentName === 'credit card') label = 'Credit Card Number';
            else label = 'Account Info';
            selectedPaymentAccountInfo.innerHTML = '<i class="bi bi-info-circle"></i> <strong>' + label + ':</strong> ' + acc;
            selectedPaymentAccountInfo.classList.remove('d-none');
        } else if (paymentName === 'wallet') {
            selectedPaymentAccountInfo.innerHTML = '<i class="bi bi-wallet2"></i> <strong>Wallet ID:</strong> <?php echo htmlspecialchars($wallet_id); ?>';
            selectedPaymentAccountInfo.classList.remove('d-none');
        } else {
            selectedPaymentAccountInfo.textContent = '';
            selectedPaymentAccountInfo.classList.add('d-none');
        }
    }
    paymentSelect.addEventListener('change', updateSelectedPaymentAccountInfo);
    updateSelectedPaymentAccountInfo();

    // --- Book Now button logic ---
    if (openBtn) {
        openBtn.addEventListener('click', function(e) {
            if (!bookingForm.checkValidity()) {
                bookingForm.classList.add('was-validated');
                errorMsg.textContent = 'Please fill in all required fields correctly.';
                errorMsg.classList.remove('d-none');
                return;
            } else {
                errorMsg.classList.add('d-none');
            }
            var selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
            var paymentName = selectedOption.getAttribute('data-name');
            var acc = selectedOption.getAttribute('data-acc');
            // Always generate a booking reference number
            var ref = referenceNumberInput && referenceNumberInput.value ? referenceNumberInput.value : generateBookingReferenceNumber();
            referenceNumberInput.value = ref;
            bookingModalReferenceNumberDisplay.textContent = ref;
            // Show reference number in cash modal as well
            var cashModalRefDisplay = document.getElementById('cashModalReferenceNumberDisplay');
            if (cashModalRefDisplay) cashModalRefDisplay.textContent = ref;
            // Show account info in modal if available (for non-cash)
            var accountInfo = '';
            if (paymentName === 'wallet') {
                accountInfo = '<i class="bi bi-wallet2"></i> <strong>Wallet ID:</strong> <?php echo htmlspecialchars($wallet_id); ?>';
            } else if (acc) {
                var label = '';
                if (paymentName === 'gcash') label = 'GCash Number';
                else if (paymentName.indexOf('bank') !== -1) label = 'Bank Account Number';
                else if (paymentName === 'paypal') label = 'PayPal Email';
                else if (paymentName === 'credit card') label = 'Credit Card Number';
                else label = 'Account Info';
                accountInfo = '<i class="bi bi-info-circle"></i> <strong>' + label + ':</strong> ' + acc;
            }
            var modalAccInfo = document.getElementById('bookingModalAccountInfo');
            if (modalAccInfo) {
                modalAccInfo.innerHTML = accountInfo ? accountInfo : '';
                modalAccInfo.style.display = accountInfo ? '' : 'none';
            }
            if (paymentName === 'cash') {
                confirmModal.show();
            } else {
                bookingPaymentDetailsModal.show();
            }
        });
    }
    // --- End Book Now logic ---

    // On page load, check if all payment methods are disabled
    var allDisabled = true;
    for (var i = 0; i < paymentSelect.options.length; i++) {
        if (!paymentSelect.options[i].disabled && paymentSelect.options[i].value) {
            allDisabled = false;
            break;
        }
    }
    if (allDisabled) {
        document.getElementById('noPaymentAccountsMsg').classList.remove('d-none');
        paymentSelect.setAttribute('disabled', 'disabled');
    }

    // Bootstrap validation
    bookingForm.addEventListener('submit', function(event) {
        if (!bookingForm.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            errorMsg.textContent = 'Please fill in all required fields correctly.';
            errorMsg.classList.remove('d-none');
        } else {
            errorMsg.classList.add('d-none');
        }
        bookingForm.classList.add('was-validated');
    }, false);

    // Flatpickr initialization
    flatpickr("#checkin_datetime", {
        enableTime: true,
        dateFormat: "Y-m-d h:i K",
        minDate: "today",
        time_24hr: false,
        theme: "material_blue"
    });
    flatpickr("#checkout_datetime", {
        enableTime: true,
        dateFormat: "Y-m-d h:i K",
        minDate: "today",
        time_24hr: false,
        theme: "material_blue"
    });

    function checkWalletSufficiency() {
        if (!walletBalanceInput || !totalAmountInput || !bookNowBtn) return;
        var paymentSelect = document.getElementById('payment_id');
        var selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
        var paymentName = selectedOption.getAttribute('data-name');
        var total = parseFloat(totalAmountInput.value.replace(/,/g, ''));
        if (paymentName === 'wallet') {
            // Fetch latest wallet balance from server
            fetch('../functions/get_wallet_balance.php')
                .then(response => response.json())
                .then(data => {
                    var wallet = parseFloat(data.wallet_balance);
                    walletBalanceInput.value = wallet;
                    if (wallet < total) {
                        bookNowBtn.disabled = true;
                        if (!walletWarning) {
                            walletWarning = document.createElement('div');
                            walletWarning.className = 'alert alert-danger mt-2';
                            walletWarning.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Your wallet balance is insufficient for this booking.';
                            bookNowBtn.parentNode.insertBefore(walletWarning, bookNowBtn.nextSibling);
                        }
                    } else {
                        bookNowBtn.disabled = false;
                        if (walletWarning) {
                            walletWarning.remove();
                            walletWarning = null;
                        }
                    }
                })
                .catch(() => {
                    // On error, fallback to previous logic
                    var wallet = parseFloat(walletBalanceInput.value);
                    if (wallet < total) {
                        bookNowBtn.disabled = true;
                        if (!walletWarning) {
                            walletWarning = document.createElement('div');
                            walletWarning.className = 'alert alert-danger mt-2';
                            walletWarning.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Your wallet balance is insufficient for this booking.';
                            bookNowBtn.parentNode.insertBefore(walletWarning, bookNowBtn.nextSibling);
                        }
                    } else {
                        bookNowBtn.disabled = false;
                        if (walletWarning) {
                            walletWarning.remove();
                            walletWarning = null;
                        }
                    }
                });
        } else {
            var wallet = parseFloat(walletBalanceInput.value);
            if (walletWarning) {
                walletWarning.remove();
                walletWarning = null;
            }
            bookNowBtn.disabled = false;
        }
    }
    // Recalculate total and check wallet whenever relevant fields change
    document.getElementById('number_of_nights').addEventListener('input', function(){
        updateTotalAmount();
        checkWalletSufficiency(); // Re-check sufficiency after total updates
    });
     document.getElementById('payment_id').addEventListener('change', checkWalletSufficiency); // Re-check wallet sufficiency when payment method changes

    checkWalletSufficiency(); // Initial check on page load

    // If total amount can change (e.g., with services), listen for changes
    totalAmountInput && totalAmountInput.addEventListener('input', checkWalletSufficiency);
    // Add JS to check room availability after date selection
    function checkRoomAvailability() {
        var checkin = document.getElementById('checkin_datetime').value;
        var checkout = document.getElementById('checkout_datetime').value;
        var roomTypeId = <?php echo json_encode($room_type_id); ?>;
        var formSection = document.querySelector('.booking-form-section');
        var alertDiv = document.getElementById('roomFullAlert');
        if (!checkin || !checkout || !roomTypeId) {
            // If missing dates, remove alert and enable form
            if (alertDiv) alertDiv.remove();
            document.getElementById('bookingForm').style.pointerEvents = '';
            document.getElementById('bookingForm').style.opacity = '';
            // Also disable book button and show wallet warning if dates are cleared after being set
             if (walletWarning) walletWarning.remove();
             bookNowBtn.disabled = true;
             return;
        }
        // Enable book button and remove warning before fetch, assuming dates are valid for check
         bookNowBtn.disabled = false;
         if (walletWarning) walletWarning.remove();

        fetch(`../functions/check_room_availability.php?room_type_id=${roomTypeId}&checkin=${encodeURIComponent(checkin)}&checkout=${encodeURIComponent(checkout)}`)
            .then(res => res.json())
            .then(data => {
                if (data.fully_booked) {
                    if (!alertDiv) {
                        alertDiv = document.createElement('div');
                        alertDiv.id = 'roomFullAlert';
                        alertDiv.className = 'alert alert-danger mb-3';
                        alertDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> All rooms of this type are fully booked or occupied for the selected dates. Please select another room type or date.';
                        formSection.insertBefore(alertDiv, formSection.firstChild.nextSibling);
                    }
                    document.getElementById('bookingForm').style.pointerEvents = 'none';
                    document.getElementById('bookingForm').style.opacity = '0.6';
                     bookNowBtn.disabled = true; // Disable button if room is fully booked
                } else {
                    if (alertDiv) alertDiv.remove();
                    document.getElementById('bookingForm').style.pointerEvents = '';
                    document.getElementById('bookingForm').style.opacity = '';
                    // Re-check wallet sufficiency after availability check passes
                     checkWalletSufficiency();
                }
            })
            .catch(() => {
                // On error, show alert and disable form
                if (!alertDiv) {
                    alertDiv = document.createElement('div');
                    alertDiv.id = 'roomFullAlert';
                    alertDiv.className = 'alert alert-danger mb-3';
                    alertDiv.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Unable to check room availability. Please try again later.';
                    formSection.insertBefore(alertDiv, formSection.firstChild.nextSibling);
                }
                document.getElementById('bookingForm').style.pointerEvents = 'none';
                document.getElementById('bookingForm').style.opacity = '0.6';
                 bookNowBtn.disabled = true; // Disable button on availability check error
            });
    }
    document.getElementById('checkin_datetime').addEventListener('change', function() {
        calculateNights();
        updateCheckoutFromNights();
        checkRoomAvailability(); // Check availability after dates change
    });
    document.getElementById('number_of_nights').addEventListener('input', function() {
        updateTotalAmount();
        updateCheckoutFromNights(); // Update checkout date when nights change
        checkRoomAvailability(); // Check availability after dates change
    });

    // Auto-refresh room availability every 10 seconds (optional, adjust as needed)
    // setInterval(checkRoomAvailability, 10000);
    // Initial check on page load
    checkRoomAvailability();

     // Initial calculation on page load if dates are pre-filled
     calculateNights();
     updateCheckoutFromNights();

    // Add event listeners for confirmation buttons to submit the form
    if (confirmBtnCash) {
        confirmBtnCash.addEventListener('click', function(e) {
            confirmModal.hide();
            setTimeout(function() {
                bookingForm.submit();
            }, 300);
        });
    }
    if (confirmBtnPayment) {
        confirmBtnPayment.addEventListener('click', function(e) {
            bookingPaymentDetailsModal.hide();
            setTimeout(function() {
                bookingForm.submit();
            }, 300);
        });
    }

    // Show toast if ?booking=success is in the URL
    var url = new URL(window.location.href);
    if (url.searchParams.get('booking') === 'success') {
        var toastEl = document.getElementById('bookingSuccessToast');
        if (toastEl) {
            toastEl.style.display = '';
            var toast = new bootstrap.Toast(toastEl, { autohide: true, delay: 3000 });
            toast.show();
        }
    }
});
</script>
</body>
</html> 