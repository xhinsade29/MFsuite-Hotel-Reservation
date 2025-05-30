<?php
include('../functions/db_connect.php');
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
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$admin_id = 1;
$admin_result = $mycon->query("SELECT admin_id FROM tbl_admin LIMIT 1");
if ($admin_result && $admin_result->num_rows > 0) {
    $row = $admin_result->fetch_assoc();
    $admin_id = $row['admin_id'];
}
echo "Using admin_id: $admin_id<br>";
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
<body>
<?php include('../components/user_navigation.php'); ?>

<div class="booking-container">
    <div class="booking-room-details">
        <?php if ($room): ?>
            <div class="booking-gallery">
                <img src="../assets/rooms/<?php echo htmlspecialchars($images[0]); ?>" alt="<?php echo htmlspecialchars($room['type_name']); ?>">
            </div>
            <h2><?php echo htmlspecialchars($room['type_name']); ?></h2>
            <div class="price">₱<?php echo number_format($room['room_price'], 2); ?></div>
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
        <div id="formErrorMsg" class="alert alert-danger d-none"></div>
        <form id="bookingForm" action="../functions/bookings.php" method="POST" class="bg-secondary-subtle p-4 rounded-3 shadow-sm text-dark needs-validation" novalidate>
            <input type="hidden" name="room_type_id" value="<?php echo htmlspecialchars($room_type_id); ?>">
            <!-- Hidden fields for reference number and amount -->
            <input type="hidden" name="reference_number" id="reference_number">
            <input type="hidden" name="reference_amount" id="reference_amount">
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
                            <input type="text" class="form-control flatpickr-datetime" name="checkout_datetime" id="checkout_datetime" placeholder="Select check-out date & time" required autocomplete="off">
                        </div>
                        <div class="invalid-feedback">Check-out date and time are required.</div>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Number of Guests</label>
                        <input type="number" class="form-control" name="guests" min="1" max="10" required>
                        <div class="invalid-feedback">Please enter the number of guests (1-10).</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Method</label>
                        <select class="form-control" name="payment_id" id="payment_id" required>
                            <option value="">Select Payment Method</option>
                            <?php foreach ($payment_types as $ptype): ?>
                                <option value="<?php echo $ptype['payment_type_id']; ?>" data-name="<?php echo htmlspecialchars(strtolower($ptype['payment_name'])); ?>"><?php echo htmlspecialchars($ptype['payment_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a payment method.</div>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Total Amount</label>
                        <input type="text" class="form-control" name="total_amount" value="<?php echo $room ? number_format($room['room_price'], 2) : ''; ?>" readonly>
                    </div>
                </div>
                <div class="mb-2 mt-2">
                    <label class="form-label">Special Requests</label>
                    <textarea class="form-control" name="requests" rows="2" placeholder="Optional"></textarea>
                </div>
                <div class="d-grid mt-3">
                    <button type="button" class="btn btn-warning btn-lg fw-bold shadow-sm" id="openConfirmModalBtn">Book Now</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Booking Confirmation Modal -->
<div class="modal fade" id="confirmBookingModal" tabindex="-1" aria-labelledby="confirmBookingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light rounded-4 shadow-lg border-0">
      <div class="modal-header border-0 pb-0 justify-content-center bg-transparent">
        <h4 class="modal-title w-100 text-center fw-bold text-warning" id="confirmBookingModalLabel">Confirm Booking</h4>
        <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p>Are you sure you want to book this room?</p>
        <div class="d-flex justify-content-center gap-3 mt-4">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-warning fw-bold" id="confirmBookingBtn">Yes, Book Now</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Booking Success Modal -->
<div class="modal fade" id="successBookingModal" tabindex="-1" aria-labelledby="successBookingModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light rounded-4 shadow-lg border-0">
      <div class="modal-header border-0 pb-0 justify-content-center bg-transparent">
        <h4 class="modal-title w-100 text-center fw-bold text-success" id="successBookingModalLabel">Booking Successful!</h4>
        <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p>Your booking has been placed successfully.</p>
        <div class="d-flex justify-content-center gap-3 mt-4">
          <a href="reservations.php" class="btn btn-success">Go to Reservations</a>
          <a href="rooms.php" class="btn btn-outline-light">Back to Rooms</a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Reference Modal -->
<div class="modal fade" id="referenceModal" tabindex="-1" aria-labelledby="referenceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light rounded-4 shadow-lg border-0">
      <div class="modal-header border-0 pb-0 justify-content-center bg-transparent">
        <h4 class="modal-title w-100 text-center fw-bold text-warning" id="referenceModalLabel">Enter Payment Reference</h4>
        <button type="button" class="btn-close btn-close-white position-absolute end-0 me-3 mt-2" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <div class="mb-3">
          <label for="modal_reference_number" class="form-label">Reference Number</label>
          <input type="text" class="form-control" id="modal_reference_number" placeholder="Enter reference number">
        </div>
        <div class="mb-3">
          <label for="modal_reference_amount" class="form-label">Amount</label>
          <input type="number" class="form-control" id="modal_reference_amount" placeholder="Enter amount" min="1">
        </div>
        <div class="d-flex justify-content-center gap-3 mt-4">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-warning fw-bold" id="saveReferenceBtn">Save</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/en.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var confirmModal = new bootstrap.Modal(document.getElementById('confirmBookingModal'));
    var successModal = new bootstrap.Modal(document.getElementById('successBookingModal'));
    var openBtn = document.getElementById('openConfirmModalBtn');
    var confirmBtn = document.getElementById('confirmBookingBtn');
    var bookingForm = document.getElementById('bookingForm');
    var errorMsg = document.getElementById('formErrorMsg');
    var paymentSelect = document.getElementById('payment_id');
    var referenceModal = new bootstrap.Modal(document.getElementById('referenceModal'));
    var saveReferenceBtn = document.getElementById('saveReferenceBtn');
    var modalReferenceNumber = document.getElementById('modal_reference_number');
    var modalReferenceAmount = document.getElementById('modal_reference_amount');
    var hiddenReferenceNumber = document.getElementById('reference_number');
    var hiddenReferenceAmount = document.getElementById('reference_amount');
    var lastPaymentType = '';

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

    if (openBtn) {
        openBtn.addEventListener('click', function(e) {
            // Trigger validation before showing modal
            if (!bookingForm.checkValidity()) {
                bookingForm.classList.add('was-validated');
                errorMsg.textContent = 'Please fill in all required fields correctly.';
                errorMsg.classList.remove('d-none');
                return;
            } else {
                errorMsg.classList.add('d-none');
            }
            confirmModal.show();
        });
    }
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function(e) {
            confirmModal.hide();
            setTimeout(function() {
                bookingForm.submit();
            }, 300);
        });
    }

    // Show success modal if ?success=1 in URL
    if (window.location.search.includes('success=1')) {
        setTimeout(function() {
            successModal.show();
        }, 400);
    }

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

    // Show modal if non-cash payment is selected
    paymentSelect.addEventListener('change', function() {
        var selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
        var paymentName = selectedOption.getAttribute('data-name');
        if (paymentName && paymentName !== 'cash') {
            // Only show modal if not already filled for this selection
            if (lastPaymentType !== paymentSelect.value) {
                modalReferenceNumber.value = '';
                modalReferenceAmount.value = '';
                referenceModal.show();
                lastPaymentType = paymentSelect.value;
            }
        } else {
            // Clear hidden fields if cash
            hiddenReferenceNumber.value = '';
            hiddenReferenceAmount.value = '';
        }
    });

    saveReferenceBtn.addEventListener('click', function() {
        if (!modalReferenceNumber.value.trim() || !modalReferenceAmount.value.trim()) {
            modalReferenceNumber.classList.add('is-invalid');
            modalReferenceAmount.classList.add('is-invalid');
            return;
        }
        hiddenReferenceNumber.value = modalReferenceNumber.value.trim();
        hiddenReferenceAmount.value = modalReferenceAmount.value.trim();
        modalReferenceNumber.classList.remove('is-invalid');
        modalReferenceAmount.classList.remove('is-invalid');
        referenceModal.hide();
    });

    // Prevent form submission if reference is required but not filled
    bookingForm.addEventListener('submit', function(event) {
        var selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
        var paymentName = selectedOption.getAttribute('data-name');
        if (paymentName && paymentName !== 'cash') {
            if (!hiddenReferenceNumber.value.trim() || !hiddenReferenceAmount.value.trim()) {
                event.preventDefault();
                referenceModal.show();
                return false;
            }
        }
    });
});
</script>
</body>
</html> 