<?php
session_start();
include('../functions/db_connect.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['guest_id'])) {
    $_SESSION['error'] = "You must be logged in to book a room.";
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $guest_id = $_SESSION['guest_id'];
    $room_type_id = intval($_POST['room_type_id']);
    function to24Hour($time, $ampm) {
        return date("H:i", strtotime("$time $ampm"));
    }
    $check_in_date = $_POST['checkin'];
    $check_in_time = $_POST['checkin_time'];
    $check_in_ampm = $_POST['checkin_ampm'];
    $check_out_date = $_POST['checkout'];
    $check_out_time = $_POST['checkout_time'];
    $check_out_ampm = $_POST['checkout_ampm'];
    $check_in_time_24 = to24Hour($check_in_time, $check_in_ampm);
    $check_out_time_24 = to24Hour($check_out_time, $check_out_ampm);
    $check_in = isset($_POST['checkin_datetime']) ? $_POST['checkin_datetime'] : '';
    $check_out = isset($_POST['checkout_datetime']) ? $_POST['checkout_datetime'] : '';
    $payment_type_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 1;
    $amount = isset($_POST['reference_amount']) && $_POST['reference_amount'] !== '' ? floatval($_POST['reference_amount']) : (isset($_POST['total_amount']) ? floatval(str_replace(',', '', $_POST['total_amount'])) : 0);
    // Generate a unique reference number
    function generate_reference_number() {
        return strtoupper(bin2hex(random_bytes(8)));
    }
    $reference_number = generate_reference_number();
    $payment_status = 'Pending';
    $payment_method = '';
    // Get payment method name
    $ptype_res = $mycon->query("SELECT payment_name FROM tbl_payment_types WHERE payment_type_id = $payment_type_id");
    if ($ptype_res && $ptype_res->num_rows > 0) {
        $ptype_row = $ptype_res->fetch_assoc();
        $payment_method = $ptype_row['payment_name'];
    }
    $stmt_payment = $mycon->prepare("INSERT INTO tbl_payment (amount, payment_method, payment_status, payment_type_id, reference_number) VALUES (?, ?, ?, ?, ?)");
    $stmt_payment->bind_param("dssis", $amount, $payment_method, $payment_status, $payment_type_id, $reference_number);
    if (!$stmt_payment->execute()) {
        die("Payment insert error: " . $stmt_payment->error);
    }
    $payment_id = $mycon->insert_id;
    // Get a valid admin_id from tbl_admin
    $admin_id = 1;
    $admin_result = $mycon->query("SELECT admin_id FROM tbl_admin LIMIT 1");
    if ($admin_result && $admin_result->num_rows > 0) {
        $row = $admin_result->fetch_assoc();
        $admin_id = $row['admin_id'];
    }
    // Insert reservation
    $stmt = $mycon->prepare("INSERT INTO tbl_reservation (guest_id, payment_id, check_in, check_out, admin_id, room_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissii", $guest_id, $payment_id, $check_in, $check_out, $admin_id, $room_type_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Reservation successful!<br>Your Reference Number: <b>" . htmlspecialchars($reference_number) . "</b>";
        header("Location: ../pages/booking_form.php?success=1");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
        header("Location: ../pages/booking_form.php?room_type_id=" . $room_type_id);
        exit();
    }
} else {
    header("Location: ../pages/rooms.php");
    exit();
} 