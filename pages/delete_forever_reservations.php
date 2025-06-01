<?php
session_start();
if (!isset($_SESSION['guest_id'])) {
    http_response_code(403);
    exit('Not logged in');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_id = $_SESSION['guest_id'];
    $ids_json = $_POST['reservation_ids'] ?? '';
    $ids = json_decode($ids_json, true);
    if (is_array($ids) && count($ids) > 0) {
        $conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids) + 1); // +1 for guest_id
        $params = array_merge([$guest_id], $ids);
        $stmt = $conn->prepare("DELETE FROM hidden_reservations WHERE guest_id = ? AND reservation_id IN ($placeholders)");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        echo 'success';
        exit;
    }
}
http_response_code(400);
echo 'Invalid request'; 