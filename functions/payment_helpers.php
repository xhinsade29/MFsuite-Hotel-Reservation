<?php
// payment_helpers.php
// Usage: require_once this file, then call auto_approve_reservation_if_paid($payment_id, $mycon);
// This will set the reservation status to 'approved' if the payment is non-cash and marked as 'Paid'.

function auto_approve_reservation_if_paid($payment_id, $mycon) {
    if (!$payment_id) return;
    // Get payment info
    $sql = "SELECT payment_status, payment_method FROM tbl_payment WHERE payment_id = ?";
    $stmt = mysqli_prepare($mycon, $sql);
    mysqli_stmt_bind_param($stmt, "i", $payment_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    if (!$row) return;
    if ($row['payment_status'] === 'Paid' && strtolower($row['payment_method']) !== 'cash') {
        // Find reservation
        $res_sql = "SELECT reservation_id, status FROM tbl_reservation WHERE payment_id = ?";
        $res_stmt = mysqli_prepare($mycon, $res_sql);
        mysqli_stmt_bind_param($res_stmt, "i", $payment_id);
        mysqli_stmt_execute($res_stmt);
        $res_result = mysqli_stmt_get_result($res_stmt);
        $res_row = mysqli_fetch_assoc($res_result);
        mysqli_stmt_close($res_stmt);
        if ($res_row && $res_row['status'] !== 'approved') {
            $update_sql = "UPDATE tbl_reservation SET status = 'approved' WHERE reservation_id = ?";
            $update_stmt = mysqli_prepare($mycon, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $res_row['reservation_id']);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        }
    }
} 