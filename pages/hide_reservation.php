<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['guest_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$guest_id = $_SESSION['guest_id'];
$conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid request.'];
$ids_to_hide = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle multiple reservations hide (from JSON array)
    if (isset($_POST['reservation_ids'])) {
        $decoded_ids = json_decode($_POST['reservation_ids'], true);
        if (is_array($decoded_ids)) {
            foreach ($decoded_ids as $id) {
                $ids_to_hide[] = intval($id);
            }
        }
    }
    // Handle single reservation hide
    elseif (isset($_POST['reservation_id'])) {
        $ids_to_hide[] = intval($_POST['reservation_id']);
    }

    if (!empty($ids_to_hide)) {
        $conn->begin_transaction();
        // Use ON DUPLICATE KEY UPDATE to prevent errors if the entry already exists
        $sql = "INSERT INTO hidden_reservations (reservation_id, guest_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE guest_id = VALUES(guest_id)";
        $stmt = $conn->prepare($sql);
        $all_success = true;

        foreach ($ids_to_hide as $res_id) {
            if ($res_id > 0) { // Basic validation
                $stmt->bind_param("ii", $res_id, $guest_id);
                if (!$stmt->execute()) {
                    $all_success = false;
                    break;
                }
            }
        }

        if ($all_success) {
            $conn->commit();
            $response = ['success' => true, 'message' => 'Reservation(s) moved to trash.'];
        } else {
            $conn->rollback();
            $response['message'] = 'Failed to hide one or more reservations.';
        }
        $stmt->close();
    }
}

$conn->close();
echo json_encode($response);
?> 