<?php
header('Content-Type: application/json');
include_once '../functions/db_connect.php';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = [];
if ($q !== '') {
    $sql = "SELECT room_type_id, type_name, description FROM tbl_room_type WHERE type_name LIKE ? OR description LIKE ? LIMIT 10";
    $like = '%' . $q . '%';
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $stmt->bind_result($id, $name, $desc);
    while ($stmt->fetch()) {
        $results[] = [
            'room_type_id' => $id,
            'type_name' => $name,
            'description' => $desc
        ];
    }
    $stmt->close();
}
echo json_encode($results); 