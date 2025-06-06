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

// Get selected payment method for wallet (from form, e.g., $_POST['wallet_payment_method'])
$wallet_payment_method = strtolower($_POST['wallet_payment_method'] ?? 'gcash'); // default to gcash if not set

// WALLET PAYMENT CHECK (per-account)
$account_balance = 0;
$account_id = null;
$stmt = $conn->prepare("SELECT account_id, balance FROM guest_payment_accounts WHERE guest_id = ? AND account_type = ? LIMIT 1");
$stmt->bind_param("is", $guest_id, $wallet_payment_method);
$stmt->execute();
$stmt->bind_result($account_id, $account_balance);
$stmt->fetch();
$stmt->close();

if ($account_balance < $amount) {
    $_SESSION['error'] = 'Insufficient wallet balance in your selected account to complete this booking.';
    $conn->close();
    header("Location: /pages/booking_form.php?room_id=$room_id");
    exit;
}

// Deduct from selected account
$stmt_update_wallet = $conn->prepare("UPDATE guest_payment_accounts SET balance = balance - ? WHERE account_id = ?");
$stmt_update_wallet->bind_param("di", $amount, $account_id);
$stmt_update_wallet->execute();
$stmt_update_wallet->close();

// Log wallet payment (include reference number if top up)
$desc = "Booking payment";
$log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'payment', ?, ?, ?)";
$stmt_log = $conn->prepare($log_sql);
$stmt_log->bind_param("idsss", $guest_id, $amount, $desc, $wallet_payment_method, $reference_number);
$stmt_log->execute();
$stmt_log->close();

// After logging guest wallet payment, credit admin wallet and log transaction
$admin_id = 1;
$admin_result = $conn->query("SELECT admin_id FROM tbl_admin LIMIT 1");
if ($admin_result && $admin_result->num_rows > 0) {
    $row = $admin_result->fetch_assoc();
    $admin_id = $row['admin_id'];
}

// DEBUG: Log all relevant variables before admin wallet update
error_log('DEBUG: About to update admin wallet. amount=' . $amount . ', admin_id=' . $admin_id);

// Credit admin wallet (use direct query for reliability)
$direct_update_sql = "UPDATE tbl_admin SET wallet_balance = wallet_balance + $amount WHERE admin_id = $admin_id";
$conn->query($direct_update_sql);
if ($conn->error) {
    error_log('Direct query failed: ' . $conn->error);
} else {
    error_log('Direct query succeeded for admin wallet update.');
}
// Update admin payment account balance for the payment method used
$admin_account_type = strtolower($payment_type ?: $wallet_payment_method);
error_log('DEBUG: payment_type=' . $payment_type . ', admin_account_type=' . $admin_account_type);
if (!in_array($admin_account_type, ['wallet', 'cash', ''])) {
    $stmt = $conn->prepare("SELECT account_id FROM admin_payment_accounts WHERE admin_id = ? AND account_type = ? LIMIT 1");
    $stmt->bind_param("is", $admin_id, $admin_account_type);
    $stmt->execute();
    $stmt->bind_result($admin_account_id);
    $has_admin_account = $stmt->fetch();
    $stmt->close();
    if ($has_admin_account) {
        // Add to the selected admin account's balance
        $stmt = $conn->prepare("UPDATE admin_payment_accounts SET balance = balance + ? WHERE account_id = ?");
        $stmt->bind_param("di", $amount, $admin_account_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Create the admin account if it doesn't exist
        $stmt = $conn->prepare("INSERT INTO admin_payment_accounts (admin_id, account_type, balance) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $admin_id, $admin_account_type, $amount);
        $stmt->execute();
        $admin_account_id = $stmt->insert_id;
        $stmt->close();
    }
    // Log admin payment account transaction
    $admin_acc_desc = "Received payment for reservation via $admin_account_type (Guest ID: $guest_id)";
    $admin_acc_log_sql = "INSERT INTO wallet_transactions (admin_id, amount, type, description, payment_method, reference_number) VALUES (?, ?, 'credit', ?, ?, ?)";
    $stmt_admin_acc_log = $conn->prepare($admin_acc_log_sql);
    $stmt_admin_acc_log->bind_param("idsss", $admin_id, $amount, $admin_acc_desc, $admin_account_type, $reference_number);
    $stmt_admin_acc_log->execute();
    $stmt_admin_acc_log->close();
    // Notify admin of payment account update
    $notif_msg = "Your $admin_account_type account was credited with ₱" . number_format($amount, 2) . " for a new reservation (Guest ID: $guest_id, Ref: $reference_number).";
    add_notification($admin_id, 'admin', 'wallet', $notif_msg, $conn, 0, null, $guest_id);
}
// Fetch and log the new admin wallet balance
$check_balance = $conn->prepare("SELECT wallet_balance FROM tbl_admin WHERE admin_id = ?");
if (!$check_balance) {
    error_log('DEBUG: Prepare failed for balance check: ' . $conn->error);
} else {
    $check_balance->bind_param("i", $admin_id);
    $check_balance->execute();
    $check_balance->bind_result($new_balance);
    $check_balance->fetch();
    error_log('Admin new wallet balance: ' . $new_balance);
    $check_balance->close();
}

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
) ORDER BY (
    SELECT COUNT(*) FROM tbl_reservation res2 WHERE res2.guest_id = ? AND res2.assigned_room_id = r.room_id
) ASC LIMIT 1";
$stmt_find = $conn->prepare($find_room_sql);
$stmt_find->bind_param("issi", $room_type_id, $check_out, $check_in, $guest_id);
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

// Insert notification for the user
$notif_sql = "INSERT INTO user_notifications (guest_id, type, message, created_at, admin_id) VALUES (?, 'reservation', ?, NOW(), ?)";
$notif_msg = "Your reservation has been placed successfully. Ref: " . htmlspecialchars($reference_number) . ".";

// Fetch guest name for admin notification
$guest_name = '';
$guest_name_sql = "SELECT first_name, last_name FROM tbl_guest WHERE guest_id = ? LIMIT 1";
$stmt_guest_name = $conn->prepare($guest_name_sql);
$stmt_guest_name->bind_param("i", $guest_id);
$stmt_guest_name->execute();
$stmt_guest_name->bind_result($first_name, $last_name);
if ($stmt_guest_name->fetch()) {
    $guest_name = trim($first_name . ' ' . $last_name);
}
$stmt_guest_name->close();

// Prepare admin notification message (admin only)
$admin_notif_msg = '';
if ($assigned_room_id !== NULL) {
    // Fetch room number to include in notification if assigned
    $room_num_sql = "SELECT room_number FROM tbl_room WHERE room_id = ? LIMIT 1";
    $stmt_room_num = $conn->prepare($room_num_sql);
    $stmt_room_num->bind_param("i", $assigned_room_id);
    $stmt_room_num->execute();
    $stmt_room_num->bind_result($room_number);
    $stmt_room_num->fetch();
    $stmt_room_num->close();
    if (!empty($room_number)) {
        $notif_msg .= " Assigned Room Number: " . htmlspecialchars($room_number) . ".";
        $admin_notif_msg = "New reservation placed by $guest_name. Ref: $reference_number. Approved. Assigned Room Number: $room_number.";
    } else {
        $admin_notif_msg = "New reservation placed by $guest_name. Ref: $reference_number. Approved.";
    }
} else {
    $admin_notif_msg = "New reservation placed by $guest_name. Ref: $reference_number. Approved.";
}

// Use the updated add_notification function for user notification
include_once __DIR__ . '/notify.php'; // Ensure function is available
add_notification($guest_id, 'user', 'reservation', $notif_msg, $conn, 0, $admin_id, $reservation_id); // Use $conn for the database connection

// Use the updated add_notification function for admin notification
// Check if an admin is logged in to assign the notification
$current_admin_id = $_SESSION['admin_id'] ?? 1; // Default to admin 1 if no admin logged in (e.g., system action)
add_notification($current_admin_id, 'admin', 'reservation', $admin_notif_msg, $conn, 0, null, $reservation_id); // Use $conn for the database connection

$_SESSION['success'] = "Reservation successful!<br>Your Reference Number: <b>" . htmlspecialchars($reference_number) . "</b>";

// Add assigned room number to success message if applicable
if ($assigned_room_id !== NULL) {
    $_SESSION['success'] .= "<br>Assigned Room Number: " . htmlspecialchars($assigned_room_id);
}

$conn->close();
header("Location: /pages/reservations.php?success=1");
exit;
?> 