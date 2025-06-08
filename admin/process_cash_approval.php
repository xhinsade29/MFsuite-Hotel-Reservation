<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); // Set content type to JSON
session_start();

$response = ['success' => false, 'message' => 'Invalid request.'];

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $response['message'] = 'Unauthorized access.';
    http_response_code(403);
    echo json_encode($response);
    exit();
}

include_once '../functions/db_connect.php';
include_once '../functions/notify.php'; // Include the notification functions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($reservation_id && $action === 'approve') {
        
        // 1. Get room_type_id, guest_id, check_in, check_out, assigned_room_id for the reservation
        $stmt_details = mysqli_prepare($mycon, "SELECT r.room_id, r.guest_id, r.check_in, r.check_out, r.assigned_room_id FROM tbl_reservation r WHERE r.reservation_id = ?");
        mysqli_stmt_bind_param($stmt_details, "i", $reservation_id);
        mysqli_stmt_execute($stmt_details);
        mysqli_stmt_bind_result($stmt_details, $room_type_id, $guest_id, $check_in, $check_out, $assigned_room_id);
        mysqli_stmt_fetch($stmt_details);
        mysqli_stmt_close($stmt_details);

        // 2. If not already assigned, find an available room and assign it
        if (empty($assigned_room_id)) {
            $find_room_sql = "SELECT r.room_id FROM tbl_room r WHERE r.room_type_id = ? AND r.status = 'Available' AND r.room_id NOT IN (
                SELECT res.assigned_room_id FROM tbl_reservation res WHERE res.check_in < ? AND res.check_out > ? AND res.status IN ('pending','approved','completed') AND res.assigned_room_id IS NOT NULL
            ) LIMIT 1";
            $stmt_find = mysqli_prepare($mycon, $find_room_sql);
            mysqli_stmt_bind_param($stmt_find, "iss", $room_type_id, $check_out, $check_in);
            mysqli_stmt_execute($stmt_find);
            mysqli_stmt_bind_result($stmt_find, $new_assigned_room_id);
            
            if (mysqli_stmt_fetch($stmt_find)) {
                $assigned_room_id = $new_assigned_room_id;
            }
            mysqli_stmt_close($stmt_find);

            if(empty($assigned_room_id)) {
                // Halt approval if no room is available
                $response['message'] = 'Approval failed: No available room found for the selected dates.';
                echo json_encode($response);
                exit();
            }

            // Assign the found room to the reservation
            $stmt_assign = mysqli_prepare($mycon, "UPDATE tbl_reservation SET assigned_room_id = ? WHERE reservation_id = ?");
            mysqli_stmt_bind_param($stmt_assign, "ii", $assigned_room_id, $reservation_id);
            mysqli_stmt_execute($stmt_assign);
            mysqli_stmt_close($stmt_assign);
        }
        
        // Set reservation status to approved
        $result = mysqli_query($mycon, "UPDATE tbl_reservation SET status = 'approved' WHERE reservation_id = $reservation_id");
        if (!$result) {
            error_log('Failed to update reservation status: ' . mysqli_error($mycon));
        }

        // Set payment status to Paid
        $result2 = mysqli_query($mycon, "UPDATE tbl_payment p JOIN tbl_reservation r ON p.payment_id = r.payment_id SET p.payment_status = 'Paid' WHERE r.reservation_id = $reservation_id");
        if (!$result2) {
            error_log('Failed to update payment status: ' . mysqli_error($mycon));
        }

        // Set assigned room status to 'Occupied'
        if (!empty($assigned_room_id)) {
            $result3 = mysqli_query($mycon, "UPDATE tbl_room SET status = 'Occupied' WHERE room_id = $assigned_room_id");
            if (!$result3) {
                error_log('Failed to update room status: ' . mysqli_error($mycon));
            }
        }

        $admin_id = $_SESSION['admin_id'] ?? 1;
        // Notify Admin
        $admin_notif_msg = "Cash payment approved for reservation #" . $reservation_id . ". Room assigned.";
        add_notification($admin_id, 'admin', 'payment', $admin_notif_msg, $mycon, 0, null, $reservation_id);
        
        // Notify User
        if($guest_id) {
            $user_notif_msg = "Your cash payment for reservation #" . $reservation_id . " has been approved. Your booking is confirmed.";
            add_notification($guest_id, 'user', 'payment', $user_notif_msg, $mycon, 0, $admin_id, $reservation_id);
        }

        $response['success'] = true;
        $response['message'] = 'Cash payment approved and room assigned successfully.';
    }
    elseif ($reservation_id && $action === 'deny') {
        // Get guest_id for notification
        $stmt_details = mysqli_prepare($mycon, "SELECT guest_id FROM tbl_reservation WHERE reservation_id = ?");
        mysqli_stmt_bind_param($stmt_details, "i", $reservation_id);
        mysqli_stmt_execute($stmt_details);
        mysqli_stmt_bind_result($stmt_details, $guest_id);
        mysqli_stmt_fetch($stmt_details);
        mysqli_stmt_close($stmt_details);

        // Set reservation status to denied
        $result = mysqli_query($mycon, "UPDATE tbl_reservation SET status = 'denied' WHERE reservation_id = $reservation_id");
        // Set payment status to Denied
        $result2 = mysqli_query($mycon, "UPDATE tbl_payment p JOIN tbl_reservation r ON p.payment_id = r.payment_id SET p.payment_status = 'Denied' WHERE r.reservation_id = $reservation_id");

        $admin_id = $_SESSION['admin_id'] ?? 1;
        // Notify Admin
        $admin_notif_msg = "Cash payment denied for reservation #" . $reservation_id . ".";
        add_notification($admin_id, 'admin', 'payment', $admin_notif_msg, $mycon, 0, null, $reservation_id);
        // Notify User
        if($guest_id) {
            $user_notif_msg = "Your cash payment for reservation #" . $reservation_id . " has been denied by the admin.";
            add_notification($guest_id, 'user', 'payment', $user_notif_msg, $mycon, 0, $admin_id, $reservation_id);
        }

        $response['success'] = true;
        $response['message'] = 'Cash payment denied and reservation updated.';
    }
}

echo json_encode($response);
exit(); 