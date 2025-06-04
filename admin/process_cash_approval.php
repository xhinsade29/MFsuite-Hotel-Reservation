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

        // Set reservation status to completed (valid enum value)
        $stmt = $conn->prepare("UPDATE tbl_reservation SET status = 'completed' WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->close();

        // Set payment status to Paid
        $stmt2 = $conn->prepare("UPDATE tbl_payment p JOIN tbl_reservation r ON p.payment_id = r.payment_id SET p.payment_status = 'Paid' WHERE r.reservation_id = ?");
        $stmt2->bind_param("i", $reservation_id);
        $stmt2->execute();
        $stmt2->close();

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