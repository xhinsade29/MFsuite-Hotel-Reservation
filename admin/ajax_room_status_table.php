<?php
include '../functions/db_connect.php';
$room_type_id = isset($_GET['room_type_id']) ? intval($_GET['room_type_id']) : 0;
if (!$room_type_id) exit;
$room_query = $mycon->query("SELECT * FROM tbl_room WHERE room_type_id = $room_type_id ORDER BY room_number ASC");
if ($room_query->num_rows === 0) {
    echo '<tr><td colspan="2" class="text-center text-muted">No rooms yet. Add one above.</td></tr>';
    exit;
}
while ($r = $room_query->fetch_assoc()) {
    // Check if this room is assigned to any active reservation
    $room_id = $r['room_id'];
    $now = date('Y-m-d H:i:s');
    $active_res = $mycon->query("SELECT 1 FROM tbl_reservation WHERE assigned_room_id = $room_id AND status IN ('pending','approved','completed') AND check_out > '$now' LIMIT 1");
    $is_occupied = ($active_res && $active_res->num_rows > 0);
    echo '<tr data-room-id="' . $r['room_id'] . '">';
    echo '<td class="d-flex align-items-center gap-2">';
    echo '<input type="hidden" name="room_id" value="' . $r['room_id'] . '">';
    echo '<input type="text" name="room_number" value="' . htmlspecialchars($r['room_number']) . '" class="form-control form-control-sm room-number-input" required style="max-width:110px;" data-original-value="' . htmlspecialchars($r['room_number']) . '">';
    echo '<button type="button" class="btn btn-success btn-sm save-room-btn" title="Save"><i class="bi bi-check"></i></button>';
    echo '<button type="button" class="btn btn-secondary btn-sm cancel-room-btn" title="Cancel"><i class="bi bi-x"></i></button>';
    echo '<button type="button" class="btn btn-danger btn-sm delete-room-btn" title="Delete"><i class="bi bi-trash"></i></button>';
    echo '</td>';
    $status = $r['status'];
    $badge = $status === 'Available' ? 'success' : ($status === 'Occupied' ? 'danger' : 'secondary');
    echo '<td><span class="badge bg-' . $badge . '">' . htmlspecialchars($status) . '</span></td>';
    echo '</tr>';
} 