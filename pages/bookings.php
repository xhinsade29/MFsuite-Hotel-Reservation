<?php
session_start();
include('../functions/db_connect.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['guest_id'])) {
    $_SESSION['error'] = "You must be logged in to book a room.";
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $guest_id = $_SESSION['guest_id'];
    $room_type_id = intval($_POST['room_type_id']);
    $check_in = $_POST['checkin'];
    $check_out = $_POST['checkout'];
    // Get a valid admin_id from tbl_admin
    $admin_id = 1;
    $admin_result = $mycon->query("SELECT admin_id FROM tbl_admin LIMIT 1");
    if ($admin_result && $admin_result->num_rows > 0) {
        $row = $admin_result->fetch_assoc();
        $admin_id = $row['admin_id'];
    }

    // Insert payment record
    $payment_type_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 1; // This is payment_type_id from the form
    $amount = 0; // You can set this to the room price or total amount if you want
    $payment_method = 'N/A'; // Or get from form if you want
    $payment_status = 'Pending';
    $stmt_payment = $mycon->prepare("INSERT INTO tbl_payment (amount, payment_method, payment_status, payment_type_id) VALUES (?, ?, ?, ?)");
    $stmt_payment->bind_param("issi", $amount, $payment_method, $payment_status, $payment_type_id);
    if (!$stmt_payment->execute()) {
        die("Payment insert error: " . $stmt_payment->error);
    }
    $payment_id = $mycon->insert_id;

    // Insert reservation
    $stmt = $mycon->prepare("INSERT INTO tbl_reservation (guest_id, payment_id, check_in, check_out, admin_id, room_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissii", $guest_id, $payment_id, $check_in, $check_out, $admin_id, $room_type_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Reservation successful!";
        header("Location: booking_form.php?success=1");
        exit();
    } else {
        $_SESSION['error'] = "Error: " . $stmt->error;
        header("Location: booking_form.php?room_type_id=" . $room_type_id);
        exit();
    }
} else {
    header("Location: rooms.php");
    exit();
} 