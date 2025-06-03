<?php
include('../functions/db_connect.php');
header('Content-Type: application/json');
$room_type_id = isset($_GET['room_type_id']) ? intval($_GET['room_type_id']) : 0;
$available = false;
if ($room_type_id) {
    $sql = "SELECT COUNT(*) as cnt FROM tbl_room WHERE room_type_id = ? AND status = 'Available'";
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param('i', $room_type_id);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    if ($cnt > 0) $available = true;
}
echo json_encode(['available' => $available]); 