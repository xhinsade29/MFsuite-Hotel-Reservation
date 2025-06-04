<?php
include '../functions/db_connect.php';
$cancellation_sql = "SELECT r.reservation_id, r.check_in, r.check_out, g.first_name, g.last_name, rt.type_name, r.date_created as date_canceled, r.status FROM tbl_reservation r JOIN tbl_guest g ON r.guest_id = g.guest_id JOIN tbl_room rm ON r.room_id = rm.room_id JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id WHERE r.status IN ('cancellation_requested', 'cancelled') ORDER BY r.date_created DESC";
$cancellation_res = mysqli_query($mycon, $cancellation_sql);
if ($cancellation_res && mysqli_num_rows($cancellation_res) > 0) {
    while ($row = mysqli_fetch_assoc($cancellation_res)) {
        echo '<tr>';
        echo '<td>' . $row['reservation_id'] . '</td>';
        echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
        echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
        echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
        echo '<td>' . date('M d, Y h:i A', strtotime($row['date_canceled'])) . '</td>';
        echo '<td><span class="badge bg-'.(
            $row['status']==='cancelled'?'danger':(
            $row['status']==='cancellation_requested'?'info text-dark':'secondary')).'">'.ucfirst($row['status']).'</span></td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="7" class="text-center text-secondary">No pending or approved cancellation requests.</td></tr>';
} 