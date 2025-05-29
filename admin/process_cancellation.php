<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../pages/login.php');
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($reservation_id && in_array($action, ['approve', 'deny'])) {
        $conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
        if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
        $new_status = $action === 'approve' ? 'cancelled' : 'denied';
        $stmt = $conn->prepare("UPDATE tbl_reservation SET status = ? WHERE reservation_id = ?");
        $stmt->bind_param("si", $new_status, $reservation_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        $msg = $action === 'approve' ? 'Cancellation approved.' : 'Cancellation denied.';
        header("Location: cancellation_requests.php?msg=" . urlencode($msg));
        exit();
    }
}
header("Location: cancellation_requests.php?msg=Invalid+request");
exit(); 