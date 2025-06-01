<?php
session_start();
include('../functions/db_connect.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (!isset($_SESSION['guest_id'])) {
    $_SESSION['error'] = "You must be logged in to book a room.";
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $guest_id = $_SESSION['guest_id'];
    $room_type_id = isset($_POST['room_type_id']) ? intval($_POST['room_type_id']) : 0;
    // Ensure checkin_datetime and checkout_datetime are received and correctly formatted
    $check_in = isset($_POST['checkin_datetime']) ? $_POST['checkin_datetime'] : '';
    $check_out = isset($_POST['checkout_datetime']) ? $_POST['checkout_datetime'] : '';

    // Validate dates are not empty
    if (empty($check_in) || empty($check_out)) {
        $_SESSION['error'] = "Check-in and Check-out dates are required.";
        header("Location: ../pages/booking_form.php?room_type_id=" . $room_type_id);
        exit();
    }

    // Convert dates to database-friendly format if necessary (Flatpickr default format might be OK)
    // Assuming format is 'Y-m-d H:i:s' or similar that MySQL DATETIME accepts.
    // If Flatpickr format 'Y-m-d h:i K' (e.g., 2023-10-27 10:30 AM), it needs conversion.
    // Let's assume Flatpickr output 'Y-m-d H:i:s' or compatible, or convert explicitly.
    // Based on booking_form.php JS, format is "Y-m-d h:i K". Needs conversion.
    $check_in_db = date('Y-m-d H:i:s', strtotime($check_in));
    $check_out_db = date('Y-m-d H:i:s', strtotime($check_out));

    $payment_type_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0; // Payment ID is now required
     if ($payment_type_id == 0) {
        $_SESSION['error'] = "Payment method is required.";
        header("Location: ../pages/booking_form.php?room_type_id=" . $room_type_id);
        exit();
     }

    $number_of_nights = isset($_POST['number_of_nights']) ? intval($_POST['number_of_nights']) : 1;
    $requests = isset($_POST['requests']) ? $_POST['requests'] : ''; // Get special requests
    $reference_number = isset($_POST['reference_number']) && !empty($_POST['reference_number']) ? $_POST['reference_number'] : '';

    // Fetch nightly_rate from tbl_room_type
    $rate_res = $mycon->query("SELECT nightly_rate FROM tbl_room_type WHERE room_type_id = $room_type_id");
    $nightly_rate = 0;
    if ($rate_res && $rate_res->num_rows > 0) {
        $rate_row = $rate_res->fetch_assoc();
        $nightly_rate = floatval($rate_row['nightly_rate']);
    } else {
        $_SESSION['error'] = "Invalid room type.";
        header("Location: ../pages/booking_form.php"); // Redirect without room_type_id if invalid
        exit();
    }

    $amount = $nightly_rate * $number_of_nights; // Base amount

    // Add service costs if any - This logic needs to be implemented if services are added to booking form
    // For now, assuming services are inclusions and not separately priced per booking.
    // If services are add-ons, fetch selected service IDs from POST and calculate additional cost.

    $payment_status = 'Pending'; // Default
    if (strtolower($payment_method) !== 'cash') {
        $payment_status = 'Paid';
    }

    $reservation_status = 'pending'; // Default status
    $assigned_room_id = NULL; // Default assigned room

    // Get payment method name and determine initial reservation status
    $payment_method = '';
    $ptype_res = $mycon->query("SELECT payment_name FROM tbl_payment_types WHERE payment_type_id = $payment_type_id");
    if ($ptype_res && $ptype_res->num_rows > 0) {
        $ptype_row = $ptype_res->fetch_assoc();
        $payment_method = $ptype_row['payment_name'];

        // For wallet or top up payments, use the reference number from the latest wallet top-up (the one the guest entered)
        if (in_array(strtolower($payment_method), ['wallet', 'top up', 'topup'])) {
            // Check wallet balance
            $wallet_sql = "SELECT wallet_balance FROM tbl_guest WHERE guest_id = ?";
            $stmt_wallet = $mycon->prepare($wallet_sql);
            $stmt_wallet->bind_param("i", $guest_id);
            $stmt_wallet->execute();
            $stmt_wallet->bind_result($wallet_balance);
            $stmt_wallet->fetch();
            $stmt_wallet->close();

            if ($wallet_balance < $amount) {
                $_SESSION['error'] = 'Insufficient wallet balance to complete this booking.';
                header("Location: ../pages/booking_form.php?room_type_id=" . $room_type_id);
                exit();
            }

            // Deduct from wallet
            $update_wallet_sql = "UPDATE tbl_guest SET wallet_balance = wallet_balance - ? WHERE guest_id = ?";
            $stmt_update_wallet = $mycon->prepare($update_wallet_sql);
            $stmt_update_wallet->bind_param("di", $amount, $guest_id);
            $stmt_update_wallet->execute();
            $stmt_update_wallet->close();

            // Log wallet payment
            $log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, reference_number) VALUES (?, ?, 'payment', ?, ?)";
            $desc = 'Wallet payment for booking';
            $stmt_log = $mycon->prepare($log_sql);
            $stmt_log->bind_param("idss", $guest_id, $amount, $desc, $reference_number);
            $stmt_log->execute();
            $stmt_log->close();

            // For wallet payments, use the reference number from the latest wallet top-up (the one the guest entered)
            $wallet_ref_sql = "SELECT reference_number FROM tbl_payment WHERE payment_method = 'Wallet' AND payment_status = 'Paid' AND reference_number IS NOT NULL AND reference_number != '' AND amount > 0 AND guest_id = $guest_id ORDER BY payment_id DESC LIMIT 1";
            $wallet_ref_result = $mycon->query($wallet_ref_sql);
            if ($wallet_ref_result && $wallet_ref_result->num_rows > 0) {
                $wallet_ref_row = $wallet_ref_result->fetch_assoc();
                $reference_number = $wallet_ref_row['reference_number'];
            }
        }

        // If payment method is NOT Cash, attempt to auto-approve and assign room
        if (strtolower($payment_method) !== 'cash') {
            // Find an available room of this type for the dates
            // Query to find rooms of the correct type not booked during the requested period
            $available_room_sql = "SELECT r.room_id FROM tbl_room r
                                   WHERE r.room_type_id = ?
                                     AND r.status = 'available' -- Ensure the room itself is available/operational
                                     AND NOT EXISTS (
                                         SELECT 1 FROM tbl_reservation res
                                         WHERE res.assigned_room_id = r.room_id
                                           AND res.status IN ('pending', 'approved', 'completed') -- Consider these statuses as occupying the room
                                           AND (
                                                 (? < res.check_out AND ? > res.check_in) -- Check-in is before existing checkout AND Checkout is after existing check-in
                                               )
                                     )
                                   LIMIT 1"; // Get just one available room

            $stmt_find_room = $mycon->prepare($available_room_sql);
            // Bind parameters: i (room_type_id), s (check_out_db), s (check_in_db)
            $stmt_find_room->bind_param("iss", $room_type_id, $check_out_db, $check_in_db);
            $stmt_find_room->execute();
            $result_find_room = $stmt_find_room->get_result();

            if ($result_find_room && $result_find_room->num_rows > 0) {
                $room_row = $result_find_room->fetch_assoc();
                $assigned_room_id = $room_row['room_id'];
                $reservation_status = 'approved'; // Auto-approve if room assigned

                // Optionally update room status to 'occupied' or 'reserved' if auto-assigned? Or leave as 'available' until check-in?
                // Leaving as 'available' might be better until check-in date, and a separate cron job/trigger handles status updates.

            } else {
                // No room found for the dates, keep pending status and let admin handle it.
                // The front-end availability check should ideally prevent this, but this is a fallback.
                 $reservation_status = 'pending'; // Keep pending
                 $assigned_room_id = NULL; // No room assigned
                 // Consider logging this event or notifying admin
            }
             $stmt_find_room->close();
        }
         // If payment method is Cash, reservation_status remains 'pending' and assigned_room_id remains NULL

    } else {
        $_SESSION['error'] = "Invalid payment method.";
        header("Location: ../pages/booking_form.php?room_type_id=" . $room_type_id);
        exit();
    }


    // Insert payment record
    $stmt_payment = $mycon->prepare("INSERT INTO tbl_payment (amount, payment_method, payment_status, payment_type_id, reference_number) VALUES (?, ?, ?, ?, ?)");
    // Using 'd' for double for amount, 'sss' for strings, 'i' for integer
    $stmt_payment->bind_param("dssis", $amount, $payment_method, $payment_status, $payment_type_id, $reference_number);

    if (!$stmt_payment->execute()) {
        // Error inserting payment
        $_SESSION['error'] = "Error processing payment: " . $stmt_payment->error;
        header("Location: ../pages/booking_form.php?room_type_id=" . $room_type_id);
        exit();
    }
    $payment_id = $mycon->insert_id;

    // Get a valid admin_id from tbl_admin
    $admin_id = 1; // Default admin ID
    $admin_result = $mycon->query("SELECT admin_id FROM tbl_admin LIMIT 1");
    if ($admin_result && $admin_result->num_rows > 0) {
        $row = $admin_result->fetch_assoc();
        $admin_id = $row['admin_id'];
    }

    // Insert reservation
    // Updated INSERT statement to include status and assigned_room_id (no requests)
    $stmt = $mycon->prepare("INSERT INTO tbl_reservation (guest_id, payment_id, check_in, check_out, admin_id, room_id, number_of_nights, status, assigned_room_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters: iissiiisi (guest_id, payment_id, check_in_db, check_out_db, admin_id, room_type_id, number_of_nights, reservation_status, assigned_room_id)
    $stmt->bind_param("iissiiisi", $guest_id, $payment_id, $check_in_db, $check_out_db, $admin_id, $room_type_id, $number_of_nights, $reservation_status, $assigned_room_id);


    if ($stmt->execute()) {
        $reservation_id = $mycon->insert_id; // Get the new reservation ID

        // Insert notification for the user
        $notif_sql = "INSERT INTO user_notifications (guest_id, type, message, created_at) VALUES (?, 'reservation', ?, NOW())";
        $notif_msg = "Your reservation has been placed successfully. Ref: " . htmlspecialchars($reference_number) . ".";

        if ($reservation_status === 'approved') {
             $notif_msg .= " Your reservation is approved.";
             if ($assigned_room_id !== NULL) {
                 // Fetch room number to include in notification if assigned
                 $room_num_sql = "SELECT room_number FROM tbl_room WHERE room_id = ? LIMIT 1"; // Limit 1 just in case
                 $stmt_room_num = $mycon->prepare($room_num_sql);
                 $stmt_room_num->bind_param("i", $assigned_room_id);
                 $stmt_room_num->execute();
                 $stmt_room_num->bind_result($room_number);
                 $stmt_room_num->fetch();
                 $stmt_room_num->close();
                 if (!empty($room_number)) {
                    $notif_msg .= " Assigned Room Number: " . htmlspecialchars($room_number) . ".";
                 }
             }
        } else {
             $notif_msg .= " It is pending admin approval for room assignment and confirmation.";
        }

        $notif_stmt = $mycon->prepare($notif_sql);
        $notif_stmt->bind_param("is", $guest_id, $notif_msg);
        $notif_stmt->execute();
        $notif_stmt->close();

        $_SESSION['success'] = "Reservation successful!<br>Your Reference Number: <b>" . htmlspecialchars($reference_number) . "</b>";

        // Add assigned room number to success message if applicable
         if ($reservation_status === 'approved' && !empty($room_number)) {
             $_SESSION['success'] .= "<br>Assigned Room Number: <b>" . htmlspecialchars($room_number) . "</b>";
         }

        // Redirect to the reservation details page or reservations list
        header("Location: ../pages/reservations.php?success=1"); // Redirect to reservations list with success flag
        exit();
    } else {
        $_SESSION['error'] = "Error creating reservation: " . $stmt->error;
        // Consider rolling back the payment insertion here if reservation insertion fails
        // $mycon->query("DELETE FROM tbl_payment WHERE payment_id = $payment_id"); // Simple rollback
        header("Location: ../pages/booking_form.php?room_type_id=" . $room_type_id);
        exit();
    }

     $stmt->close();

} else {
    header("Location: ../pages/rooms.php");
    exit();
}

function generate_reference_number() {
    // Simple unique ID generator (can be improved)
    return strtoupper(bin2hex(random_bytes(8)));
}

?> 