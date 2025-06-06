<?php
include('../functions/db_connect.php');
header('Content-Type: application/json');

$room_type_id = isset($_POST['room_type_id']) ? intval($_POST['room_type_id']) : 0;
$check_in_date = isset($_POST['check_in_date']) ? $_POST['check_in_date'] : '';
$check_out_date = isset($_POST['check_out_date']) ? $_POST['check_out_date'] : '';

$available_rooms_count = 0;
$total_rooms_of_type = 0;
$is_fully_booked = false;
$message = '';

if ($room_type_id && !empty($check_in_date) && !empty($check_out_date)) {
    // 1. Get total number of rooms for this room type
    $sql_total_rooms = "SELECT COUNT(*) as total FROM tbl_room WHERE room_type_id = ?";
    $stmt_total = $mycon->prepare($sql_total_rooms);
    $stmt_total->bind_param('i', $room_type_id);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result();
    if ($row_total = $result_total->fetch_assoc()) {
        $total_rooms_of_type = $row_total['total'];
    }
    $stmt_total->close();

    if ($total_rooms_of_type == 0) {
        $message = "No rooms of this type exist.";
        $is_fully_booked = true; // Effectively fully booked if no rooms exist
    } else {
        // 2. Count rooms booked for the selected period for this room type
        // A room is considered 'booked' if its reservation overlaps with the requested check-in/out
        // and its status is 'pending', 'approved', or 'completed'.
        $sql_booked_rooms = "
            SELECT COUNT(DISTINCT r.room_id) as booked_count
            FROM tbl_reservation r
            JOIN tbl_room rm ON r.room_id = rm.room_id
            WHERE rm.room_type_id = ?
            AND r.status IN ('pending', 'approved', 'completed')
            AND r.check_in < ? AND r.check_out > ?
        ";
        $stmt_booked = $mycon->prepare($sql_booked_rooms);
        $stmt_booked->bind_param('iss', $room_type_id, $check_out_date, $check_in_date);
        $stmt_booked->execute();
        $result_booked = $stmt_booked->get_result();
        $booked_rooms_count = 0;
        if ($row_booked = $result_booked->fetch_assoc()) {
            $booked_rooms_count = $row_booked['booked_count'];
        }
        $stmt_booked->close();

        $available_rooms_count = $total_rooms_of_type - $booked_rooms_count;

        if ($available_rooms_count <= 0) {
            $is_fully_booked = true;
            $message = "All rooms of this type are fully booked for your selected dates. Please choose different dates or another room type.";
        } else {
            $message = "Rooms available: " . $available_rooms_count;
        }
    }
} else {
    $message = "Invalid room type ID or dates provided.";
}

echo json_encode([
    'available' => $available_rooms_count > 0,
    'available_rooms_count' => $available_rooms_count,
    'total_rooms_of_type' => $total_rooms_of_type,
    'is_fully_booked' => $is_fully_booked,
    'message' => $message
]);
?> 