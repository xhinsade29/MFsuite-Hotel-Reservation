<?php
include '../functions/db_connect.php';
$active_sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, rm.room_number FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id WHERE r.status IN ('pending','approved') ORDER BY r.date_created DESC";
$active_res = mysqli_query($mycon, $active_sql);
if ($active_res && mysqli_num_rows($active_res) > 0) {
    while ($row = mysqli_fetch_assoc($active_res)) {
        echo '<tr>';
        echo '<td>' . $row['reservation_id'] . '</td>';
        echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['room_number']) . '</td>';
        echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
        echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
        $status = $row['status'];
        echo '<td><span class="badge bg-'.(
            $status==='approved'?'success':(
            $status==='cancelled'?'danger':(
            $status==='denied'?'warning text-dark':(
            $status==='completed'?'primary':(
            $status==='cancellation_requested'?'info text-dark':'secondary'))))).'">'.ucfirst($status).'</span></td>';
        // Action column
        echo '<td>';
        if ($status === 'approved') {
            $can_complete = strtotime($row['check_out']) < time();
            $btn_class = $can_complete ? 'btn-primary' : 'btn-secondary';
            $checkout_time = date('M d, Y h:i A', strtotime($row['check_out']));
            $checkout_timestamp = strtotime($row['check_out']);
            echo '<button class="btn ' . $btn_class . ' btn-sm mark-complete-btn" data-reservation-id="' . $row['reservation_id'] . '" data-checkout-time="' . htmlspecialchars($checkout_time) . '" data-checkout-timestamp="' . $checkout_timestamp . '">Mark as Completed</button>';
        }
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="8" class="text-center text-secondary">No active reservations.</td></tr>';
} 