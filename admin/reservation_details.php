<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$reservation_id = intval($_GET['id'] ?? 0);
if (!$reservation_id) { die("Invalid reservation."); }

// Fetch reservation details
$sql = "SELECT r.status AS reservation_status, r.*, 
               COALESCE(rt.type_name, rtt.type_name) AS type_name, 
               COALESCE(rt.description, rtt.description) AS description, 
               COALESCE(rt.nightly_rate, rtt.nightly_rate) AS nightly_rate,
               GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services,
               p.payment_status, p.payment_method, p.amount, 
               COALESCE(rt.room_type_id, rtt.room_type_id) AS room_type_id
        FROM tbl_reservation r
        LEFT JOIN tbl_room rm ON r.room_id = rm.room_id
        LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id
        LEFT JOIN tbl_room_type rtt ON r.room_type_id = rtt.room_type_id
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
// Fetch user details (no payment columns)
$user_sql = "SELECT first_name, middle_name, last_name, user_email, phone_number, address FROM tbl_guest WHERE guest_id = ?";
$stmt_user = $conn->prepare($user_sql);
$stmt_user->bind_param("i", $booking['guest_id']);
$stmt_user->execute();
$stmt_user->bind_result($first_name, $middle_name, $last_name, $user_email, $phone_number, $address);
$stmt_user->fetch();
$stmt_user->close();
// Fetch payment accounts
$payment_accounts = [];
$acc_sql = "SELECT account_type, account_number, account_email FROM guest_payment_accounts WHERE guest_id = ?";
$stmt_acc = $conn->prepare($acc_sql);
$stmt_acc->bind_param("i", $booking['guest_id']);
$stmt_acc->execute();
$result_acc = $stmt_acc->get_result();
while ($row = $result_acc->fetch_assoc()) {
    $payment_accounts[] = $row;
}
$stmt_acc->close();
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Details (Admin) - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        .details-container { 
            margin-left: 240px; 
            margin-top: 40px; 
            padding: 32px 0 32px 0;
            display: flex;
            justify-content: center;
        }
        .card-details {
            background: linear-gradient(135deg, #23234a 60%, #18182f 100%);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.22);
            padding: 0;
            width: 100%;
            margin-bottom: 0;
            min-height: 480px;
        }
        .card-title {
            color: #ffa533;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 32px;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 22px 32px;
            margin-bottom: 18px;
        }
        .meta-item strong {
            color: #ffa533;
            font-weight: 600;
        }
        .meta-item {
            font-size: 1.13em;
            background: rgba(255,255,255,0.03);
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 0;
        }
        .badge {
            font-size: 1em;
            padding: 0.5em 1em;
            border-radius: 8px;
            margin-right: 8px;
        }
        .badge.bg-info { background: linear-gradient(90deg,#ffa533 60%,#ff8c00 100%); color: #23234a; }
        .badge.bg-success { background: linear-gradient(90deg,#00c896 60%,#1e90ff 100%); color: #fff; }
        .badge.bg-danger { background: linear-gradient(90deg,#ff4d4d 60%,#c0392b 100%); color: #fff; }
        .badge.bg-warning { background: linear-gradient(90deg,#ffe066 60%,#ff8c00 100%); color: #23234a; }
        .badge.bg-secondary { background: #bdbdbd; color: #23234a; }
        .img-fluid { box-shadow: 0 4px 18px rgba(255,140,0,0.10); border-radius: 12px; }
        .alert-danger {
            background: #fff0e1 !important;
            color: #c0392b !important;
            border: 1px solid #ffe5b4 !important;
            font-size: 1.1em;
            margin-top: 18px;
        }
        .btn-warning {
            background: linear-gradient(90deg,#ffa533 60%,#ff8c00 100%);
            color: #fff;
            border: none;
            font-weight: 600;
            font-size: 1.1em;
            border-radius: 8px;
            padding: 10px 28px;
            box-shadow: 0 2px 8px rgba(255,140,0,0.10);
            transition: background 0.2s, color 0.2s;
        }
        .btn-warning:hover {
            background: linear-gradient(90deg,#ff8c00 60%,#ffa533 100%);
            color: #fff;
        }
        .receipt-card {
            background: #23234a;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(255,140,0,0.10);
            color: #fff;
            border: 1px solid #ffa533;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.08em;
        }
        .banner-img {
            width: 100%;
            max-width: 420px;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(255,140,0,0.10);
            margin-bottom: 18px;
        }
        hr.my-4 {
            border-top: 2px dashed #ffa533;
            opacity: .4;
        }
        .badge.bg-success {
            background: linear-gradient(90deg,#00c896 60%,#1e90ff 100%);
            color: #fff;
            font-size: 1em;
            padding: 0.5em 1em;
            border-radius: 8px;
        }
        .section-title {
            color: #ffa533;
            font-size: 1.45rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            margin-bottom: 18px;
        }
        .guest-card, .reservation-card, .inclusions-card {
            box-shadow: 0 6px 32px rgba(0,0,0,0.13);
            border: none;
            background: rgba(35,35,74,0.82);
            position: relative;
            overflow: hidden;
            margin-bottom: 28px;
            padding-left: 0;
            transition: box-shadow 0.2s, background 0.2s;
        }
        .guest-card::before, .reservation-card::before, .inclusions-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 7px;
            background: linear-gradient(180deg,#ffa533 0%,#ff8c00 100%);
            border-radius: 8px 0 0 8px;
            opacity: 0.85;
        }
        .guest-card:hover, .reservation-card:hover, .inclusions-card:hover {
            box-shadow: 0 12px 36px rgba(255,140,0,0.18);
            background: rgba(35,35,74,0.93);
        }
        .guest-card, .reservation-card, .inclusions-card {
            border-radius: 18px;
            padding: 32px 32px 24px 28px;
        }
        .guest-card .mb-2, .reservation-card .mb-2 {
            font-size: 1.09em;
            margin-bottom: 13px !important;
        }
        .inclusions-card ul {
            margin-top: 10px;
            margin-bottom: 0;
            padding-left: 0;
        }
        .inclusions-card li {
            background: rgba(255,255,255,0.04);
            border-radius: 7px;
            padding: 7px 14px;
            margin-bottom: 7px;
            display: flex;
            align-items: center;
            font-size: 1.08em;
            box-shadow: 0 1px 4px rgba(255,140,0,0.04);
        }
        .inclusions-card .badge.bg-success {
            font-size: 1em;
            padding: 0.4em 0.8em;
            margin-right: 8px;
        }
        /* Custom styles for admin action buttons */
        .admin-action-btn {
            font-size: 1.08em;
            font-weight: 600;
            border-radius: 10px;
            padding: 10px 28px;
            margin: 0 6px 8px 0;
            box-shadow: 0 2px 8px rgba(255,140,0,0.10);
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
        }
        .admin-action-btn.approve {
            background: linear-gradient(90deg,#00c896 60%,#1e90ff 100%);
            color: #fff;
            border: none;
        }
        .admin-action-btn.approve:hover {
            background: linear-gradient(90deg,#1e90ff 60%,#00c896 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(0,200,150,0.18);
        }
        .admin-action-btn.deny {
            background: linear-gradient(90deg,#ff4d4d 60%,#c0392b 100%);
            color: #fff;
            border: none;
        }
        .admin-action-btn.deny:hover {
            background: linear-gradient(90deg,#c0392b 60%,#ff4d4d 100%);
            color: #fff;
            box-shadow: 0 4px 16px rgba(255,77,77,0.18);
        }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="details-container">
    <div class="row w-100 g-4">
        <div class="col-lg-7 col-md-12">
            <div class="d-flex justify-content-center">
                <img src="../assets/rooms/<?php echo htmlspecialchars($image_file); ?>" alt="Room Image" class="banner-img mb-4">
            </div>
            <div class="card-details h-100 p-0 bg-transparent shadow-none border-0">
                <div class="guest-card bg-dark bg-opacity-75 rounded-4 p-4 mb-4">
                    <h3 class="section-title mb-3"><i class="bi bi-person me-2"></i>Guest Information</h3>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-person me-1"></i>Name:</span> <?php echo htmlspecialchars(trim(($first_name ?? '') . ' ' . ($last_name ?? ''))); ?></div>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-envelope me-1"></i>Email:</span> <?php echo htmlspecialchars($user_email ?? ''); ?></div>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-telephone me-1"></i>Phone:</span> <?php echo htmlspecialchars($phone_number ?? ''); ?></div>
                </div>
                <div class="reservation-card bg-dark bg-opacity-75 rounded-4 p-4 mb-4">
                    <h3 class="section-title mb-3"><i class="bi bi-door-open me-2"></i>Reservation Details</h3>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-hash me-1"></i>Reservation ID:</span> <?php echo $booking['reservation_id']; ?></div>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-check-circle me-1"></i>Status:</span> 
                    <?php if (strtolower($booking['reservation_status'] ?? '') === 'approved'): ?>
                        <span style="color:#00c896;">Approved</span>
                    <?php else: ?>
                        <span class="badge bg-info" style="font-size:0.95em;padding:0.35em 0.8em;"><?php echo ucfirst($booking['reservation_status'] ?? ''); ?></span>
                    <?php endif; ?>
                    </div>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-receipt me-1"></i>Reference #:</span> <?php echo htmlspecialchars($reference_number ?? ''); ?></div>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-door-open me-1"></i>Room Type:</span> <?php echo htmlspecialchars($booking['type_name'] ?? 'N/A'); ?></div>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-calendar-event me-1"></i>Check-in:</span> <?php echo date('M d, Y h:i A', strtotime($booking['check_in'])); ?></div>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-calendar-check me-1"></i>Check-out:</span> <?php echo date('M d, Y h:i A', strtotime($booking['check_out'])); ?></div>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-key me-1"></i>Assigned Room #:</span> <?php echo $assigned_room_number ? htmlspecialchars($assigned_room_number) : 'Not assigned'; ?></div>
                    <div class="mb-2"><span class="fw-bold text-warning"><i class="bi bi-cash-coin me-1"></i>Nightly Rate:</span> ₱<?php echo number_format($booking['nightly_rate'] ?? 0, 2); ?></div>
                </div>
                <div class="inclusions-card bg-dark bg-opacity-75 rounded-4 p-4 mb-2">
                    <span class="fw-bold text-warning"><i class="bi bi-stars me-1"></i>Inclusions:</span>
                    <?php if (!empty($included_services)): ?>
                        <ul class="list-unstyled mt-2 mb-0">
                            <?php foreach ($included_services as $service): ?>
                                <li class="mb-1"><span class="badge bg-success me-2"><i class="bi bi-check-lg"></i></span> <?php echo htmlspecialchars($service ?? ''); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-secondary">None</span>
                    <?php endif; ?>
                </div>
                <?php if ($cancellation_details): ?>
                    <div class="alert alert-danger mt-3">
                        <strong>Cancellation Reason:</strong> <?php echo htmlspecialchars($cancellation_details['reason_text'] ?? 'Not specified'); ?><br>
                        <strong>Remarks:</strong> <?php echo htmlspecialchars($cancellation_details['remarks'] ?? 'N/A'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-5 col-md-12">
            <?php if (!empty($booking['payment_id'])): ?>
            <div class="receipt-card p-4 mb-3" style="background:#fff;color:#23234a;max-width:480px;margin:auto;border-radius:22px;box-shadow:0 4px 18px rgba(255,140,0,0.10);border:1px solid #ffa533;">
                <div class="text-center mb-3">
                    <div class="mb-2" style="font-size:2.5rem;color:#ffa533;"><i class="bi bi-receipt"></i></div>
                    <div class="fw-bold" style="font-size:1.7rem;color:#23234a;letter-spacing:1px;">MF Suites Hotel</div>
                    <div class="text-secondary" style="font-size:1.1rem;">Payment Receipt</div>
                </div>
                <hr style="border-top:2px dashed #ffa533;opacity:.5;">
                <div class="row mb-2 g-2">
                    <div class="col-6 small text-secondary">Guest</div><div class="col-6 fw-semibold"><?php echo htmlspecialchars(trim(($first_name ?? '') . ' ' . ($last_name ?? ''))); ?></div>
                    <div class="col-6 small text-secondary">Email</div><div class="col-6"><?php echo htmlspecialchars($user_email ?? ''); ?></div>
                    <div class="col-6 small text-secondary">Reservation ID</div><div class="col-6"><?php echo htmlspecialchars($booking['reservation_id'] ?? ''); ?></div>
                    <div class="col-6 small text-secondary">Reference #</div><div class="col-6"><?php echo htmlspecialchars($reference_number ?? ''); ?></div>
                    <div class="col-6 small text-secondary">Payment Method</div><div class="col-6"><?php echo htmlspecialchars($booking['payment_method'] ?? 'N/A'); ?></div>
                    <div class="col-6 small text-secondary">Status</div><div class="col-6"><span class="badge bg-<?php echo ($booking['payment_status']=='Paid'?'success':($booking['payment_status']=='Pending'?'warning text-dark':'danger')); ?> px-3 py-2" style="font-size:1em;"><?php echo htmlspecialchars($booking['payment_status'] ?? 'N/A'); ?></span></div>
                    <div class="col-6 small text-secondary">Date</div><div class="col-6"><?php echo isset($booking['payment_id']) ? date('M d, Y h:i A', strtotime($booking['date_created'])) : '-'; ?></div>
                </div>
                <hr style="border-top:2px dashed #ffa533;opacity:.5;">
                <div class="text-center mb-3">
                    <div class="small text-secondary">Amount Paid</div>
                    <div class="fw-bold text-success" style="font-size:2.2rem;">₱<?php echo number_format($booking['amount'],2); ?></div>
                </div>
                <div class="text-center mb-3">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=MFsuiteReceipt-<?php echo $booking['payment_id']; ?>" alt="QR Code" class="rounded-3 border" style="background:#fff;" />
                    <div class="small text-secondary mt-2">Scan for authenticity</div>
                </div>
                <div class="text-center text-secondary small" style="font-size:0.98em;">This is an electronically generated receipt.<br>Thank you for your payment!</div>
            </div>
            <?php endif; ?>
            <div class="col-12 text-center" id="admin-actions-container">
                <!-- Admin action buttons will be injected here by JavaScript -->
            </div>
        </div>
        <div class="col-12 text-center">
            <a href="reservations.php" class="btn btn-warning mt-3">Back to Reservations</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const status = "<?php echo strtolower($booking['reservation_status'] ?? ''); ?>";
    const paymentMethod = "<?php echo strtolower($booking['payment_method'] ?? ''); ?>";
    const reservationId = <?php echo $reservation_id; ?>;
    const actionsContainer = document.getElementById('admin-actions-container');

    function createActionButtons() {
        let buttonsHtml = '';
        if (status === 'cancellation_requested') {
            buttonsHtml = `
                <div class="card bg-dark bg-opacity-75 rounded-4 p-4 mb-4">
                    <h4 class="text-warning mb-3">Admin Actions</h4>
                    <form class="action-form d-inline-block me-2" data-action="process_cancellation.php">
                        <input type="hidden" name="reservation_id" value="${reservationId}">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="admin-action-btn approve"><i class="bi bi-check-circle me-1"></i> Approve Cancellation</button>
                    </form>
                    <form class="action-form d-inline-block" data-action="process_cancellation.php">
                        <input type="hidden" name="reservation_id" value="${reservationId}">
                        <input type="hidden" name="action" value="deny">
                        <button type="submit" class="admin-action-btn deny"><i class="bi bi-x-circle me-1"></i> Deny Cancellation</button>
                    </form>
                </div>
            `;
        } else if (status === 'pending' && paymentMethod === 'cash') {
            buttonsHtml = `
                <div class="card bg-dark bg-opacity-75 rounded-4 p-4 mb-4">
                    <h4 class="text-warning mb-3">Admin Actions</h4>
                    <form class="action-form d-inline-block me-2" data-action="process_cash_approval.php">
                        <input type="hidden" name="reservation_id" value="${reservationId}">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="admin-action-btn approve"><i class="bi bi-cash-stack me-1"></i> Approve Cash Payment</button>
                    </form>
                    <form class="action-form d-inline-block" data-action="process_cash_approval.php">
                        <input type="hidden" name="reservation_id" value="${reservationId}">
                        <input type="hidden" name="action" value="deny">
                        <button type="submit" class="admin-action-btn deny"><i class="bi bi-x-circle me-1"></i> Deny Cash Payment</button>
                    </form>
                </div>
            `;
        }
        actionsContainer.innerHTML = buttonsHtml;
    }

    function handleFormSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const url = form.dataset.action;
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');

        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';

        fetch(url, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(res => res.json())
        .then(resp => {
            if (resp.success) {
                // Hide the admin actions and show a message
                const actionsContainer = document.getElementById('admin-actions-container');
                actionsContainer.innerHTML = '<div class="alert alert-success mt-3">' + (formData.get('action') === 'approve' ? 'Cash payment approved.' : 'Cash payment denied.') + '</div>';
            } else {
                alert(resp.message || 'Failed to process action.');
                submitButton.disabled = false;
                submitButton.innerHTML = form.querySelector('button[type="submit"]').dataset.originalText || 'Submit';
            }
        })
        .catch(() => {
            alert('An error occurred.');
            submitButton.disabled = false;
            submitButton.innerHTML = form.querySelector('button[type="submit"]').dataset.originalText || 'Submit';
        });
    }

    createActionButtons();

    // Event delegation for forms
    actionsContainer.addEventListener('submit', function(event) {
        if (event.target.classList.contains('action-form')) {
            handleFormSubmit(event);
        }
    });
});
</script>
</body>
</html> 