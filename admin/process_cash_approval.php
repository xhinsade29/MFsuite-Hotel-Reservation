<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}

include_once '../functions/db_connect.php';
include_once '../functions/notify.php'; // Include the notification functions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($reservation_id && $action === 'approve') {
        $conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
        if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

        // 1. Get reservation details (room_type_id, check_in, check_out)
        $stmt_details = $conn->prepare("SELECT room_id, check_in, check_out, assigned_room_id FROM tbl_reservation WHERE reservation_id = ?");
        $stmt_details->bind_param("i", $reservation_id);
        $stmt_details->execute();
        $stmt_details->bind_result($room_type_id, $check_in, $check_out, $assigned_room_id);
        $stmt_details->fetch();
        $stmt_details->close();

        // 2. If not already assigned, find an available room and assign it
        if (empty($assigned_room_id)) {
            $find_room_sql = "SELECT r.room_id FROM tbl_room r WHERE r.room_type_id = ? AND r.status = 'Available' AND r.room_id NOT IN (
                SELECT res.assigned_room_id FROM tbl_reservation res WHERE res.check_in < ? AND res.check_out > ? AND res.status IN ('pending','approved','completed') AND res.assigned_room_id IS NOT NULL
            ) LIMIT 1";
            $stmt_find = $conn->prepare($find_room_sql);
            $stmt_find->bind_param("iss", $room_type_id, $check_out, $check_in);
            $stmt_find->execute();
            $stmt_find->bind_result($new_assigned_room_id);
            $assigned_room_id = null;
            if ($stmt_find->fetch()) {
                $assigned_room_id = $new_assigned_room_id;
            }
            $stmt_find->close();
            if ($assigned_room_id) {
                // Assign the room to the reservation
                $stmt_assign = $conn->prepare("UPDATE tbl_reservation SET assigned_room_id = ? WHERE reservation_id = ?");
                $stmt_assign->bind_param("ii", $assigned_room_id, $reservation_id);
                $stmt_assign->execute();
                $stmt_assign->close();
            }
        }

        // Set reservation status to approved (not completed)
        $stmt = $conn->prepare("UPDATE tbl_reservation SET status = 'approved' WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->close();

        // Set payment status to Paid
        $stmt2 = $conn->prepare("UPDATE tbl_payment p JOIN tbl_reservation r ON p.payment_id = r.payment_id SET p.payment_status = 'Paid' WHERE r.reservation_id = ?");
        $stmt2->bind_param("i", $reservation_id);
        $stmt2->execute();
        $stmt2->close();

        // Set assigned room status to 'Occupied' if a room is assigned
        if (!empty($assigned_room_id)) {
            $stmt_update_room = $conn->prepare("UPDATE tbl_room SET status = 'Occupied' WHERE room_id = ?");
            $stmt_update_room->bind_param("i", $assigned_room_id);
            $stmt_update_room->execute();
            $stmt_update_room->close();
        }

        // Add notification for the admin
        $admin_id = $_SESSION['admin_id'] ?? 1; // Get logged-in admin ID or default
        $admin_notif_msg = "Cash payment approved for reservation #" . $reservation_id . ".";
        add_notification($admin_id, 'admin', 'payment', $admin_notif_msg, $conn, 0, null, $reservation_id);

        $conn->close();

        header("Location: dashboard.php?msg=" . urlencode('Booking marked as completed and paid.'));
        exit();
    }
}

header("Location: dashboard.php?msg=Invalid+request");
exit(); 