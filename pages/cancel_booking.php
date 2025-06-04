<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';

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
        // Check if a cancellation request already exists for this reservation
        $check_sql = "SELECT COUNT(*) as cnt FROM cancelled_reservation WHERE reservation_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $reservation_id);
        $check_stmt->execute();
        $check_stmt->bind_result($existing_cnt);
        $check_stmt->fetch();
        $check_stmt->close();
        if ($existing_cnt == 0) {
            // Insert into cancelled_reservation
            $canceled_by = 'Guest';
            $stmt = $conn->prepare("INSERT INTO cancelled_reservation (reservation_id, admin_id, canceled_by, reason_id, date_canceled) VALUES (?, NULL, ?, ?, NOW())");
            $stmt->bind_param("isi", $reservation_id, $canceled_by, $reason_id);
            $stmt->execute();
            $stmt->close();
        }
        // Update reservation: set status to 'cancellation_requested' regardless of current status
        $stmt = $conn->prepare("UPDATE tbl_reservation SET status = 'cancellation_requested' WHERE reservation_id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->close();

        // Add notification for the user
        $notif_msg = "Your cancellation request has been submitted.";
        include_once '../functions/notify.php';
        // Get an admin_id (first admin) before calling add_notification
        $admin_id = 1; // <--- This sets a default
        $admin_res = $conn->query("SELECT admin_id FROM tbl_admin LIMIT 1");
        if ($admin_res && $admin_row = $admin_res->fetch_assoc()) {
            $admin_id = $admin_row['admin_id']; // <--- This might fail or not be reached
        }
        add_notification($guest_id, 'user', 'reservation', $notif_msg, $conn, 0, $admin_id, $reservation_id); // <--- $admin_id is used here

        // Add notification for the admin
        // Fetch guest name
        $guest_name = '';
        $stmt_guest = $conn->prepare("SELECT first_name, last_name FROM tbl_guest WHERE guest_id = ?");
        $stmt_guest->bind_param("i", $guest_id);
        $stmt_guest->execute();
        $stmt_guest->bind_result($first_name, $last_name);
        if ($stmt_guest->fetch()) {
            $guest_name = trim($first_name . ' ' . $last_name);
        }
        $stmt_guest->close();

        // Use the $admin_id fetched earlier for the admin notification
        $admin_notif_msg = "A cancellation request has been submitted by " . htmlspecialchars($guest_name) . " for reservation #" . $reservation_id . ".";
        add_notification($admin_id, 'admin', 'cancellation', $admin_notif_msg, $conn, 0, null, $reservation_id);

        $conn->close();
        // Redirect back to details with notification
        header("Location: reservation_details.php?id=$reservation_id&cancel=requested");
        exit();
    }
}
// If invalid, redirect to reservations page
header("Location: reservations.php");
exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
<?php include '../components/user_navigation.php'; ?>

<?php
// Notify user if cancellation is approved
if ($action === 'approve') {
    $admin_id = 1; // Use your default or actual admin_id here
    // Corrected parameters for add_notification: $recipient_id, $recipient_type, $type, $message, $mycon, $is_read, $associated_admin_id, $related_id
    add_notification($guest_id, 'user', 'cancellation', 'Your reservation cancellation has been approved by the admin.', $conn, 0, $admin_id, $reservation_id);
}

// If approved and eligible for refund
if ($action === 'approve' && $payment_status === 'Paid' && $payment_method !== 'Cash') {
    // Refund logic (same as above)
    $stmt = mysqli_prepare($conn, "UPDATE tbl_payment SET payment_status = 'Refunded' WHERE payment_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $payment_id);
    mysqli_stmt_execute($stmt);
    $admin_id = 1; // Use your default or actual admin_id here
    // Corrected parameters for add_notification: $recipient_id, $recipient_type, $type, $message, $mycon, $is_read, $associated_admin_id, $related_id
    add_notification($guest_id, 'user', 'wallet', "Refunded ₱" . number_format($amount, 2) . " to your wallet for cancelled reservation #$reservation_id.", $conn, 0, $admin_id, $reservation_id);
}

$msg = $action === 'approve' ? 'Cancellation approved.' : 'Cancellation denied.';
header("Location: dashboard.php?msg=" . urlencode($msg));
?>
</body>
</html> 