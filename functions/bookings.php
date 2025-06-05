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

    // Always generate a unique reference number if not provided
    if (empty($reference_number)) {
        $reference_number = generate_reference_number();
    }

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

    // Get payment method name and determine initial reservation status
    $payment_method = '';
    $ptype_res = $mycon->query("SELECT payment_name FROM tbl_payment_types WHERE payment_type_id = $payment_type_id");
    if ($ptype_res && $ptype_res->num_rows > 0) {
        $ptype_row = $ptype_res->fetch_assoc();
        $payment_method = $ptype_row['payment_name'];

        // WALLET DEDUCTION LOGIC
        if (strtolower($payment_method) === 'wallet') {
            // 1. Get current wallet balance
            $wallet_sql = "SELECT wallet_balance FROM tbl_guest WHERE guest_id = ?";
            $stmt_wallet = $mycon->prepare($wallet_sql);
            $stmt_wallet->bind_param("i", $guest_id);
            $stmt_wallet->execute();
            $stmt_wallet->bind_result($wallet_balance);
            $stmt_wallet->fetch();
            $stmt_wallet->close();

            // 2. Check if sufficient
            if ($wallet_balance < $amount) {
                $_SESSION['error'] = 'Insufficient wallet balance to complete this booking.';
                header("Location: ../pages/booking_form.php?room_type_id=" . $room_type_id);
                exit();
            }

            // 3. Deduct from wallet
            $update_wallet_sql = "UPDATE tbl_guest SET wallet_balance = wallet_balance - ? WHERE guest_id = ?";
            $stmt_update_wallet = $mycon->prepare($update_wallet_sql);
            $stmt_update_wallet->bind_param("di", $amount, $guest_id);
            $stmt_update_wallet->execute();
            $stmt_update_wallet->close();

            // 4. Log wallet payment
            $desc = "Booking payment";
            $log_sql = "INSERT INTO wallet_transactions (guest_id, amount, type, description, reference_number) VALUES (?, ?, 'payment', ?, ?)";
            $stmt_log = $mycon->prepare($log_sql);
            $stmt_log->bind_param("idss", $guest_id, $amount, $desc, $reference_number);
            $stmt_log->execute();
            $stmt_log->close();
        }

        // Set payment status after payment method is known
        $payment_status = 'Pending'; // Default
        if (strtolower($payment_method) !== 'cash') {
            $payment_status = 'Paid';
            // Auto-approve and assign room if available
            $available_room_sql = "SELECT r.room_id FROM tbl_room r
                                   WHERE r.room_type_id = ?
                                     AND r.status = 'available'
                                     AND NOT EXISTS (
                                         SELECT 1 FROM tbl_reservation res
                                         WHERE res.assigned_room_id = r.room_id
                                           AND res.status IN ('pending', 'approved', 'completed')
                                           AND ((? < res.check_out AND ? > res.check_in))
                                     )
                                   LIMIT 1";
            $stmt_find_room = $mycon->prepare($available_room_sql);
            $stmt_find_room->bind_param("iss", $room_type_id, $check_out_db, $check_in_db);
            $stmt_find_room->execute();
            $result_find_room = $stmt_find_room->get_result();
            if ($result_find_room && $result_find_room->num_rows > 0) {
                $room_row = $result_find_room->fetch_assoc();
                $assigned_room_id = $room_row['room_id'];
                $reservation_status = 'approved';
            } else {
                // No room found for the dates, do not allow booking
                $_SESSION['error'] = 'Sorry, all rooms of this type are fully booked or occupied for your selected dates. Please select another room type or date.';
                // Optionally, add a notification for the user
                include_once __DIR__ . '/notify.php';
                add_notification($guest_id, 'user', 'reservation', 'Booking failed: All rooms of this type are fully booked or occupied for your selected dates.', $mycon, 0, null, null);
                header("Location: ../pages/booking_form.php?room_type_id=" . $room_type_id);
                exit();
            }
            $stmt_find_room->close();
        } else {
            // Cash payment: pending, no room assigned
            $reservation_status = 'pending';
            $assigned_room_id = NULL;
        }
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
    $admin_id = 1; // Use your default or actual admin_id here
    $admin_result = $mycon->query("SELECT admin_id FROM tbl_admin LIMIT 1");
    if ($admin_result && $admin_result->num_rows > 0) {
        $row = $admin_result->fetch_assoc();
        $admin_id = $row['admin_id'];
    }

    // Insert reservation with reference_number, handling NULL for assigned_room_id
    if (is_null($assigned_room_id)) {
        $sql = "INSERT INTO tbl_reservation (reference_number, guest_id, payment_id, check_in, check_out, admin_id, room_id, number_of_nights, status, assigned_room_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)";
        $stmt = $mycon->prepare($sql);
        $stmt->bind_param("siissiiis", $reference_number, $guest_id, $payment_id, $check_in_db, $check_out_db, $admin_id, $room_type_id, $number_of_nights, $reservation_status);
    } else {
        $sql = "INSERT INTO tbl_reservation (reference_number, guest_id, payment_id, check_in, check_out, admin_id, room_id, number_of_nights, status, assigned_room_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mycon->prepare($sql);
        $stmt->bind_param("siissiiisi", $reference_number, $guest_id, $payment_id, $check_in_db, $check_out_db, $admin_id, $room_type_id, $number_of_nights, $reservation_status, $assigned_room_id);
    }

    if ($stmt->execute()) {
        $reservation_id = $mycon->insert_id; // Get the new reservation ID

        // If reservation is auto-approved and a room is assigned, set room status to 'Occupied'
        if ($reservation_status === 'approved' && !empty($assigned_room_id)) {
            $stmt_update_room = $mycon->prepare("UPDATE tbl_room SET status = 'Occupied' WHERE room_id = ?");
            $stmt_update_room->bind_param("i", $assigned_room_id);
            $stmt_update_room->execute();
            $stmt_update_room->close();
        }

        // Insert notification for the user (using updated function)
        // Was: INSERT INTO user_notifications (guest_id, type, message, created_at, admin_id) VALUES (?, 'reservation', ?, NOW(), ?)
        $notif_msg = "Your reservation has been placed successfully. Ref: " . htmlspecialchars($reference_number) . ".";

        // Fetch guest name for admin notification
        $guest_name = '';
        $guest_name_sql = "SELECT first_name, last_name FROM tbl_guest WHERE guest_id = ? LIMIT 1";
        $stmt_guest_name = $mycon->prepare($guest_name_sql);
        $stmt_guest_name->bind_param("i", $guest_id);
        $stmt_guest_name->execute();
        $stmt_guest_name->bind_result($first_name, $last_name);
        if ($stmt_guest_name->fetch()) {
            $guest_name = trim($first_name . ' ' . $last_name);
        }
        $stmt_guest_name->close();

        // Prepare admin notification message
        $admin_notif_msg = '';
        if ($reservation_status === 'approved') {
             // Append approval message to user notification if approved at booking
             if ($assigned_room_id !== NULL) {
                 // Fetch room number to include in user notification if assigned
                 $room_num_sql = "SELECT room_number FROM tbl_room WHERE room_id = ? LIMIT 1"; // Limit 1 just in case
                 $stmt_room_num = $mycon->prepare($room_num_sql);
                 $stmt_room_num->bind_param("i", $assigned_room_id);
                 $stmt_room_num->execute();
                 $stmt_room_num->bind_result($room_number);
                 $stmt_room_num->fetch();
                 $stmt_room_num->close();
                 if (!empty($room_number)) {
                    $notif_msg .= " Your reservation is approved. Assigned Room Number: " . htmlspecialchars($room_number) . ".";
                    $admin_notif_msg = "New reservation placed by $guest_name (Ref: $reference_number). Approved. Assigned Room Number: $room_number.";
                } else {
                    $notif_msg .= " Your reservation is approved."; // Approved but no room assigned?
                    $admin_notif_msg = "New reservation placed by $guest_name (Ref: $reference_number). Approved (No room assigned).";
                 }
            } else {
                $notif_msg .= " Your reservation is approved."; // Approved but no room assigned?
                $admin_notif_msg = "New reservation placed by $guest_name (Ref: $reference_number). Approved (No room assigned).";
             }
        } else {
             $notif_msg .= " It is pending admin approval for room assignment and confirmation.";
            $admin_notif_msg = "New reservation placed by $guest_name (Ref: $reference_number). Pending approval.";
        }

        // Use the updated add_notification function for user notification
        include_once __DIR__ . '/notify.php'; // Ensure function is available
        add_notification($guest_id, 'user', 'reservation', $notif_msg, $mycon, 0, $admin_id, $reservation_id);

        // Use the updated add_notification function for admin notification
        // Check if an admin is logged in to assign the notification
        $current_admin_id = $_SESSION['admin_id'] ?? 1; // Default to admin 1 if no admin logged in (e.g., system action)
        add_notification($current_admin_id, 'admin', 'reservation', $admin_notif_msg, $mycon, 0, null, $reservation_id);

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
        file_put_contents(__DIR__ . '/booking_debug.log', date('c') . " ERROR: Failed to insert reservation. Ref: $reference_number\n", FILE_APPEND);
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