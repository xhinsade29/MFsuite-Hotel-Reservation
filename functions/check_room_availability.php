<?php
header('Content-Type: application/json');
include 'db_connect.php';
$room_type_id = intval($_GET['room_type_id'] ?? 0);
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
if (!$room_type_id || !$checkin || !$checkout) {
    echo json_encode(['fully_booked' => false]);
    exit;
}
// Count total rooms of this type
$sql_count = "SELECT COUNT(*) as total FROM tbl_room WHERE room_type_id = $room_type_id";
$result_count = mysqli_query($mycon, $sql_count);
$total_rooms = 0;
if ($result_count && $row_count = mysqli_fetch_assoc($result_count)) {
    $total_rooms = $row_count['total'];
}
// Count booked rooms for the date range
$sql_booked = "SELECT COUNT(DISTINCT r.room_id) as booked FROM tbl_reservation r WHERE r.room_id IN (SELECT room_id FROM tbl_room WHERE room_type_id = $room_type_id) AND r.status IN ('pending','approved','completed') AND r.check_in < '$checkout' AND r.check_out > '$checkin'";
$result_booked = mysqli_query($mycon, $sql_booked);
$booked_rooms = 0;
if ($result_booked && $row_booked = mysqli_fetch_assoc($result_booked)) {
    $booked_rooms = $row_booked['booked'];
}
$fully_booked = ($total_rooms > 0 && $booked_rooms >= $total_rooms);
echo json_encode(['fully_booked' => $fully_booked]); 