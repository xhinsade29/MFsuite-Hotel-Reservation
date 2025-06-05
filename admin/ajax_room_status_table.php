<?php
include '../functions/db_connect.php';
$room_type_id = isset($_GET['room_type_id']) ? intval($_GET['room_type_id']) : 0;
if (!$room_type_id) exit;
$room_query = $mycon->query("SELECT * FROM tbl_room WHERE room_type_id = $room_type_id ORDER BY room_number ASC");
if ($room_query->num_rows === 0) {
    echo '<tr><td colspan="4" class="text-center text-muted">No rooms yet. Add one above.</td></tr>';
    exit;
}
while ($r = $room_query->fetch_assoc()) {
    // Check if this room is assigned to any active reservation
    $room_id = $r['room_id'];
    $now = date('Y-m-d H:i:s');
    $active_res = $mycon->query("SELECT 1 FROM tbl_reservation WHERE assigned_room_id = $room_id AND status IN ('pending','approved','completed') AND check_out > '$now' LIMIT 1");
    $is_occupied = ($active_res && $active_res->num_rows > 0);
    echo '<tr>';
    echo '<form method="POST">';
    echo '<input type="hidden" name="edit_room_number" value="1">';
    echo '<input type="hidden" name="room_id" value="' . $r['room_id'] . '">';
    echo '<td><input type="text" name="room_number" value="' . htmlspecialchars($r['room_number']) . '" class="form-control form-control-sm" required></td>';
    $status = $r['status'];
    $badge = $status === 'Available' ? 'success' : ($status === 'Occupied' ? 'danger' : 'secondary');
    echo '<td><span class="badge bg-' . $badge . '">' . htmlspecialchars($status) . '</span></td>';
    echo '<td><button type="submit" class="btn btn-primary btn-sm">Save</button> ';
    echo '<button type="button" class="btn btn-secondary btn-sm" onclick="this.form.querySelector(\'[name=room_number]\').value=\'' . htmlspecialchars($r['room_number']) . '\';">Cancel</button></td>';
    echo '<td><form method="POST" onsubmit="return confirm(\'Delete this room?\');" style="display:inline;">';
    echo '<input type="hidden" name="delete_room_single" value="1">';
    echo '<input type="hidden" name="room_id" value="' . $r['room_id'] . '">';
    echo '<button type="submit" class="btn btn-danger btn-sm">Delete</button>';
    echo '</form></td>';
    echo '</form>';
    echo '</tr>';
} 