<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
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
        $conn->close();
        header("Location: dashboard.php?msg=" . urlencode('Booking marked as completed and paid.'));
        exit();
    }
}
header("Location: dashboard.php?msg=Invalid+request");
exit(); 