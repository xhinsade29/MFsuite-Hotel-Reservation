<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = intval($_POST['reservation_id'] ?? 0);
    $reason_id = $_POST['reason_id'] ?? '';
    $other_reason = trim($_POST['other_reason'] ?? '');
    $guest_id = $_SESSION['guest_id'] ?? null;
    if ($reservation_id && $reason_id && $guest_id) {
        $conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        // If 'Other', insert new reason and get its ID
        if ($reason_id === 'other' && $other_reason) {
            $stmt = $conn->prepare("INSERT INTO tbl_cancellation_reason (reason_text) VALUES (?)");
            $stmt->bind_param("s", $other_reason);
            $stmt->execute();
            $reason_id = $conn->insert_id;
            $stmt->close();
        }
        // Insert into cancelled_reservation
        $canceled_by = 'Guest';
        $stmt = $conn->prepare("INSERT INTO cancelled_reservation (reservation_id, admin_id, canceled_by, reason_id, date_canceled) VALUES (?, NULL, ?, ?, NOW())");
        $stmt->bind_param("isi", $reservation_id, $canceled_by, $reason_id);
        $stmt->execute();
        $stmt->close();
        // Update reservation: set status to 'cancellation_requested' only if currently 'pending'
        $stmt = $conn->prepare("UPDATE tbl_reservation SET status = 'cancellation_requested' WHERE reservation_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        // Redirect back to details with notification
        header("Location: /pages/reservation_details.php?id=$reservation_id&cancel=requested");
        exit();
    }
}
// If invalid, redirect to reservations page
header("Location: /pages/reservations.php");
exit(); 