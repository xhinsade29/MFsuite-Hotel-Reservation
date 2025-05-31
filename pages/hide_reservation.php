<?php
session_start();
if (!isset($_SESSION['guest_id'])) {
    http_response_code(403);
    exit('Not logged in');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    $guest_id = $_SESSION['guest_id'];
    if ($reservation_id) {
        $conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $stmt = $conn->prepare("INSERT IGNORE INTO hidden_reservations (guest_id, reservation_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $guest_id, $reservation_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        echo "success";
        exit;
    }
}
http_response_code(400);
echo "Invalid request"; 