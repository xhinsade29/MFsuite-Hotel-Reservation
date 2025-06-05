<?php
include '../functions/db_connect.php';
$where = [];
if (!empty($_GET['guest'])) {
    $guest = mysqli_real_escape_string($mycon, $_GET['guest']);
    $where[] = "(g.first_name LIKE '%$guest%' OR g.last_name LIKE '%$guest%')";
}
if (!empty($_GET['room_type'])) {
    $rtype = intval($_GET['room_type']);
    $where[] = "rt.room_type_id = $rtype";
}
$list_sort = $_GET['list_sort'] ?? 'date_desc';
$list_order = 'ORDER BY r.date_created DESC';
if ($list_sort === 'date_asc') $list_order = 'ORDER BY r.date_created ASC';
if ($list_sort === 'guest') $list_order = 'ORDER BY g.last_name ASC, g.first_name ASC';
if ($list_sort === 'room') $list_order = 'ORDER BY rt.type_name ASC';
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$sql = "SELECT r.reservation_id, r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, p.amount, p.payment_method, p.payment_status, p.reference_number FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id $where_sql $list_order";
$res = mysqli_query($mycon, $sql);
if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo '<tr>';
        echo '<td>' . $row['reservation_id'] . '</td>';
        echo '<td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['type_name']) . '</td>';
        echo '<td>' . date('M d, Y h:i A', strtotime($row['check_in'])) . '</td>';
        echo '<td>' . date('M d, Y h:i A', strtotime($row['check_out'])) . '</td>';
        $status = $row['status'];
        echo '<td><span class="badge bg-'.(
            $status==='approved'?'success':(
            $status==='cancelled'?'danger':(
            $status==='denied'?'warning text-dark':(
            $status==='completed'?'primary':(
            $status==='cancellation_requested'?'info text-dark':'secondary'))))).'">'.ucfirst($status).'</span></td>';
        echo '<td>' . (isset($row['amount']) ? '₱' . number_format($row['amount'], 2) : '-') . '</td>';
        echo '<td>' . htmlspecialchars($row['payment_method'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($row['payment_status'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($row['reference_number'] ?? '-') . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="10" class="text-center text-secondary">No reservations found.</td></tr>';
} 