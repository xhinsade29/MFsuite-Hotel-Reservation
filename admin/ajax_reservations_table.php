<?php
include '../functions/db_connect.php';
$active_sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, rm.room_number, rm.status AS room_status, p.payment_status, p.payment_method FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE r.status IN ('pending','approved') ORDER BY r.date_created DESC";
$active_res = mysqli_query($mycon, $active_sql);
if ($active_res && mysqli_num_rows($active_res) > 0) {
    while ($row = mysqli_fetch_assoc($active_res)) {
        echo '<tr>';
        echo '<td>' . $row['reservation_id'] . '</td>';
        echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['room_number']) . '</td>';
        echo '<td>' . htmlspecialchars($row['room_status']) . '</td>';
        echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
        echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
        $status = $row['status'];
        echo '<td><span class="badge bg-'.(
            $status==='approved'?'success':(
            $status==='cancelled'?'danger':(
            $status==='denied'?'warning text-dark':(
            $status==='completed'?'primary':(
            $status==='cancellation_requested'?'info text-dark':'secondary'))))).'">'.ucfirst($status).'</span></td>';
        echo '<td>' . htmlspecialchars($row['payment_status']) . '</td>';
        echo '<td>';
        // Action buttons for cash payment, only one approval button at a time
        if ($row['payment_method'] === 'Cash') {
            if ($row['payment_status'] === 'Pending') {
                echo '<button class="btn btn-success btn-sm approve-payment-btn" data-reservation-id="' . $row['reservation_id'] . '">Approve Payment</button> ';
            } else if ($row['payment_status'] === 'Paid' && $row['status'] === 'pending') {
                echo '<button class="btn btn-primary btn-sm approve-reservation-btn" data-reservation-id="' . $row['reservation_id'] . '">Approve Reservation</button> ';
            }
            if ($row['status'] === 'pending' || $row['status'] === 'approved') {
                echo '<button class="btn btn-danger btn-sm cancel-reservation-btn" data-reservation-id="' . $row['reservation_id'] . '">Cancel</button>';
            }
        }
        if ($status === 'approved' && strtotime($row['check_out']) < time()) {
            echo '<button class="btn btn-primary btn-sm" onclick="showCompleteModal(' . $row['reservation_id'] . ')">Mark as Completed</button>';
        }
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="10" class="text-center text-secondary">No active reservations.</td></tr>';
} 