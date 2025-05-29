<?php
session_start();
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
$payment_type = $_POST['payment_type'];
$service_ids = isset($_POST['service_id']) ? $_POST['service_id'] : []; // This is an array

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

$payment_status = 'Pending';
$payment_method = 'N/A'; // Or get from form
$stmt = $conn->prepare("INSERT INTO tbl_payment (amount, payment_method, payment_status) VALUES (?, ?, ?)");
$stmt->bind_param("dss", $amount, $payment_method, $payment_status);
$stmt->execute();
$payment_id = $conn->insert_id;

// Insert reservation
$guest_id = $_SESSION['guest_id'] ?? null;
if (!$guest_id) {
    die("Not logged in.");
}
$stmt = $conn->prepare("INSERT INTO tbl_reservation (guest_id, payment_id, check_in, check_out, room_id, guests, special_requests, date_created, status) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')");
$stmt->bind_param("iisssis", $guest_id, $payment_id, $check_in, $check_out, $room_id, $guests, $special_requests);
$stmt->execute();
$reservation_id = $conn->insert_id;

// Insert selected services
if (!empty($service_ids)) {
    $stmt = $conn->prepare("INSERT INTO reservation_services (reservation_id, service_id) VALUES (?, ?)");
    foreach ($service_ids as $service_id) {
        $stmt->bind_param("ii", $reservation_id, $service_id);
        $stmt->execute();
    }
}

$conn->close();
header("Location: reservations.php?success=1");
exit;
?> 