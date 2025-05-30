<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../pages/notification.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $new_status = $_POST['new_status'] ?? '';
    $allowed = ['pending','approved','cancelled','denied','completed','cancellation_requested'];
    if ($reservation_id && $action === 'complete') {
        $new_status = 'completed';
    }
    if ($reservation_id && in_array($new_status, $allowed)) {
        $conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
        if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
        $stmt = $conn->prepare("UPDATE tbl_reservation SET status = ? WHERE reservation_id = ?");
        $stmt->bind_param("si", $new_status, $reservation_id);
        $stmt->execute();
        $stmt->close();
        $email = '';
        $stmt2 = $conn->prepare("SELECT g.user_email FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id WHERE r.reservation_id = ? LIMIT 1");
        $stmt2->bind_param("i", $reservation_id);
        $stmt2->execute();
        $stmt2->bind_result($email);
        $stmt2->fetch();
        $stmt2->close();
        $conn->close();
        if ($email) notify_status_update($reservation_id, $new_status, $email);
        header("Location: dashboard.php?msg=" . urlencode('Status updated successfully.'));
        exit();
    }
}
header("Location: dashboard.php?msg=Invalid+request");
exit(); 