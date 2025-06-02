<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include_once '../functions/notify.php';
$conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$first_name = $_POST['first_name'];
$middle_name = $_POST['middle_name'];
$last_name = $_POST['last_name'];
$phone_number = $_POST['phone_number'];
$address = $_POST['address'];
$room_id = $_POST['room_id'];
$check_in = $_POST['check_in'];
$check_out = $_POST['check_out'];
$guests = $_POST['guests'];
$special_requests = $_POST['special_requests'];
$payment_type = $_POST['payment_type'] ?? '';
$service_ids = isset($_POST['service_id']) ? $_POST['service_id'] : []; // This is an array
$reference_amount = floatval($_POST['reference_amount'] ?? 0);
$reference_number = trim($_POST['reference_number'] ?? '');

// Calculate total amount
$amount = 0;
// Get room price
$room_price = 0;
$room_sql = "SELECT room_price FROM tbl_room_type WHERE room_type_id = ?";
$stmt_room = $conn->prepare($room_sql);
$stmt_room->bind_param("i", $room_id);
$stmt_room->execute();
$stmt_room->bind_result($room_price);
if ($stmt_room->fetch()) {
    $amount += $room_price;
}
$stmt_room->close();
// Add selected services price if applicable
if (!empty($service_ids)) {
    $service_ids_str = implode(',', array_map('intval', $service_ids));
    $sql_services = "SELECT SUM(service_price) FROM tbl_services WHERE service_id IN ($service_ids_str)";
    $result_services = $conn->query($sql_services);
    if ($result_services && $row = $result_services->fetch_row()) {
        $amount += floatval($row[0]);
    }
}

$guest_id = $_SESSION['guest_id'] ?? null;
if (!$guest_id) {
    die("Not logged in.");
}

// If payment method is 'Top Up', add to wallet and log
if (strtolower($payment_type) === 'top up' || strtolower($payment_type) === 'topup') {
    if ($reference_amount > 0 && $reference_number !== '') {
        // Add to wallet
        $sql = "UPDATE tbl_guest SET wallet_balance = wallet_balance + ? WHERE guest_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("di", $reference_amount, $guest_id);
        $stmt->execute();
        $stmt->close();
        // Log the top-up
        $desc = 'Wallet top-up for booking';
        $log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'topup', ?, ?, ?)";
        $log_stmt = $conn->prepare($log_sql);
        $log_stmt->bind_param("idsss", $guest_id, $reference_amount, $desc, $payment_type, $reference_number);
        $log_stmt->execute();
        $log_stmt->close();
    }
}

// WALLET PAYMENT CHECK
$wallet_sql = "SELECT wallet_balance FROM tbl_guest WHERE guest_id = ?";
$stmt_wallet = $conn->prepare($wallet_sql);
$stmt_wallet->bind_param("i", $guest_id);
$stmt_wallet->execute();
$stmt_wallet->bind_result($wallet_balance);
$stmt_wallet->fetch();
$stmt_wallet->close();

if ($wallet_balance < $amount) {
    $_SESSION['error'] = 'Insufficient wallet balance to complete this booking.';
    $conn->close();
    header("Location: /pages/booking_form.php?room_id=$room_id");
    exit;
}

// Deduct from wallet
$update_wallet_sql = "UPDATE tbl_guest SET wallet_balance = wallet_balance - ? WHERE guest_id = ?";
$stmt_update_wallet = $conn->prepare($update_wallet_sql);
$stmt_update_wallet->bind_param("di", $amount, $guest_id);
$stmt_update_wallet->execute();
$stmt_update_wallet->close();

// Log wallet payment (include reference number if top up)
$desc = "Booking payment";
$log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, reference_number) VALUES (?, ?, 'payment', ?, ?)";
$stmt_log = $conn->prepare($log_sql);
$stmt_log->bind_param("idss", $guest_id, $amount, $desc, $reference_number);
$stmt_log->execute();
$stmt_log->close();

// After logging guest wallet payment, credit admin wallet and log transaction
$admin_id = 1;
$admin_result = $conn->query("SELECT admin_id FROM tbl_admin LIMIT 1");
if ($admin_result && $admin_result->num_rows > 0) {
    $row = $admin_result->fetch_assoc();
    $admin_id = $row['admin_id'];
}
// Credit admin wallet
$update_admin_wallet_sql = "UPDATE tbl_admin SET wallet_balance = wallet_balance + ? WHERE admin_id = ?";
$stmt_admin_wallet = $conn->prepare($update_admin_wallet_sql);
$stmt_admin_wallet->bind_param("di", $amount, $admin_id);
$stmt_admin_wallet->execute();
$stmt_admin_wallet->close();
// Log admin wallet transaction
$desc_admin = "Received payment from guest (ID: $guest_id) for reservation.";
$log_sql_admin = "INSERT INTO wallet_transactions (admin_id, amount, type, description, reference_number, created_at) VALUES (?, ?, 'credit', ?, ?, NOW())";
$stmt_log_admin = $conn->prepare($log_sql_admin);
$stmt_log_admin->bind_param("idss", $admin_id, $amount, $desc_admin, $reference_number);
$stmt_log_admin->execute();
$stmt_log_admin->close();

// Fetch admin payment account info for the selected payment method
$admin_account_info = '';
if (in_array(strtolower($payment_type), ['gcash','bank','paypal','credit_card'])) {
    $sql = "SELECT account_type, account_number, account_email FROM admin_payment_accounts WHERE admin_id = ? AND account_type = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $admin_id, strtolower($payment_type));
    $stmt->execute();
    $stmt->bind_result($atype, $anumber, $aemail);
    if ($stmt->fetch()) {
        if ($atype === 'paypal') {
            $admin_account_info = $aemail;
        } else {
            $admin_account_info = $anumber;
        }
    }
    $stmt->close();
}

$payment_status = 'Paid';
$payment_method = 'Wallet';
$stmt = $conn->prepare("INSERT INTO tbl_payment (amount, payment_method, payment_status, reference_number, description) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("dssss", $amount, $payment_method, $payment_status, $reference_number, $admin_account_info);
$stmt->execute();
$payment_id = $stmt->insert_id;
$stmt->close();

// Check if there is an available room for the selected type and date range
$room_type_id = intval($room_id); // $room_id is actually room_type_id
$check_in = $check_in;
$check_out = $check_out;
$find_room_sql = "SELECT r.room_id FROM tbl_room r WHERE r.room_type_id = ? AND r.room_id NOT IN (
    SELECT res.assigned_room_id FROM tbl_reservation res WHERE res.check_in < ? AND res.check_out > ? AND res.status IN ('pending','approved','completed') AND res.assigned_room_id IS NOT NULL
) LIMIT 1";
$stmt_find = $conn->prepare($find_room_sql);
$stmt_find->bind_param("iss", $room_type_id, $check_out, $check_in);
$stmt_find->execute();
$stmt_find->bind_result($assigned_room_id);
$assigned_room_id = null;
if ($stmt_find->fetch()) {
    // Room available, proceed
    $stmt_find->close();
} else {
    // No available room
    $stmt_find->close();
    $_SESSION['error'] = 'Sorry, this room type is fully booked for your selected dates.';
    $conn->close();
    header("Location: booking_form.php?room_type_id=$room_type_id");
    exit;
}

// Insert reservation
$stmt = $conn->prepare("INSERT INTO tbl_reservation (guest_id, payment_id, check_in, check_out, room_id, guests, special_requests, date_created, status, assigned_room_id) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending', ?)");
$stmt->bind_param("iisssisi", $guest_id, $payment_id, $check_in, $check_out, $room_id, $guests, $special_requests, $assigned_room_id);
$stmt->execute();
$reservation_id = $stmt->insert_id;
$stmt->close();

// Insert selected services
if (!empty($service_ids)) {
    $stmt = $conn->prepare("INSERT INTO reservation_services (reservation_id, service_id) VALUES (?, ?)");
    foreach ($service_ids as $service_id) {
        $stmt->bind_param("ii", $reservation_id, $service_id);
        $stmt->execute();
    }
    $stmt->close();
}

// Add admin notification for new reservation
$guest_name = $first_name . ' ' . $last_name;
$notif_msg = "New reservation by $guest_name (Reservation ID: $reservation_id)";
$notif_sql = "INSERT INTO notifications (type, message, related_id, related_type) VALUES ('reservation', ?, ?, 'reservation')";
$stmt_notif = $conn->prepare($notif_sql);
$stmt_notif->bind_param("si", $notif_msg, $reservation_id);
$stmt_notif->execute();
$stmt_notif->close();

// After successful booking and wallet deduction
add_notification($guest_id, 'reservation', 'Your reservation was successful!', $conn);
add_notification($guest_id, 'wallet', 'Wallet payment made for reservation.', $conn);

$conn->close();
$_SESSION['success'] = 'Booking successful and paid via wallet!';
header("Location: /pages/reservations.php?success=1");
exit;
?> 