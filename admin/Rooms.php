<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';

// Handle add, edit, delete actions for rooms only
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Room
    if (isset($_POST['add_room'])) {
        $type_name = trim($_POST['type_name']);
        $nightly_rate = floatval($_POST['nightly_rate']);
        $description = trim($_POST['description']);
        $max_occupancy = intval($_POST['max_occupancy']);
        $image_name = '';
        if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', ])) {
                $image_name = uniqid('room_') . '.' . $ext;
                move_uploaded_file($_FILES['room_image']['tmp_name'], '../assets/rooms/' . $image_name);
            }
        }
        if ($type_name && $nightly_rate > 0 && $max_occupancy > 0) {
            $stmt = $mycon->prepare("INSERT INTO tbl_room_type (type_name, nightly_rate, description, max_occupancy, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sdsis', $type_name, $nightly_rate, $description, $max_occupancy, $image_name);
            $stmt->execute();
            $room_type_id = $mycon->insert_id;
            // Insert selected services as inclusions
            if (!empty($_POST['included_services']) && is_array($_POST['included_services'])) {
                $ins = $mycon->prepare("INSERT INTO tbl_room_services (room_type_id, service_id) VALUES (?, ?)");
                foreach ($_POST['included_services'] as $service_id) {
                    $service_id = intval($service_id);
                    $ins->bind_param('ii', $room_type_id, $service_id);
                    $ins->execute();
                }
                $ins->close();
            }
            $stmt->close();
            $msg = 'Room type added!';
            echo "<script>setTimeout(function(){ showAdminToast('Room type created successfully!', 'success'); }, 500);</script>";
        }
    }
    // Edit Room
    if (isset($_POST['edit_room'])) {
        $room_type_id = intval($_POST['room_type_id']);
        $type_name = trim($_POST['type_name']);
        $nightly_rate = floatval($_POST['nightly_rate']);
        $description = trim($_POST['description']);
        $max_occupancy = intval($_POST['max_occupancy']);
        $image_name = $_POST['existing_image'] ?? '';
        if (isset($_FILES['room_image']) && $_FILES['room_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['room_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $image_name = uniqid('room_') . '.' . $ext;
                move_uploaded_file($_FILES['room_image']['tmp_name'], '../assets/rooms/' . $image_name);
            }
        }
        if ($room_type_id && $type_name && $nightly_rate > 0 && $max_occupancy > 0) {
            $stmt = $mycon->prepare("UPDATE tbl_room_type SET type_name=?, nightly_rate=?, description=?, max_occupancy=?, image=? WHERE room_type_id=?");
            $stmt->bind_param('sdsisi', $type_name, $nightly_rate, $description, $max_occupancy, $image_name, $room_type_id);
            $stmt->execute();
            $stmt->close();
            // Update included services
            $mycon->query("DELETE FROM tbl_room_services WHERE room_type_id = $room_type_id");
            if (!empty($_POST['included_services']) && is_array($_POST['included_services'])) {
                $ins = $mycon->prepare("INSERT INTO tbl_room_services (room_type_id, service_id) VALUES (?, ?)");
                foreach ($_POST['included_services'] as $service_id) {
                    $service_id = intval($service_id);
                    $ins->bind_param('ii', $room_type_id, $service_id);
                    $ins->execute();
                }
                $ins->close();
            }
            $msg = 'Room type updated!';
            echo "<script>setTimeout(function(){ showAdminToast('Room type updated successfully!', 'success'); }, 500);</script>";
            // Notify all users with future reservations for this room type
            include_once '../functions/notify.php';
            $future_res = $mycon->prepare("SELECT DISTINCT guest_id, reservation_id FROM tbl_reservation WHERE room_id=? AND check_in > ?");
            $future_res->bind_param('is', $room_type_id, $now);
            $future_res->execute();
            $future_res->bind_result($guest_id, $reservation_id);
            while ($future_res->fetch()) {
                $msg = "The details for your upcoming reservation (ID: $reservation_id) have changed. New room: $type_name, Price: ₱" . number_format($nightly_rate, 2) . ".";
                $admin_id = 1; // Use your default or actual admin_id here
                add_notification($guest_id, 'reservation', $msg, $mycon, 0, $admin_id);
            }
            $future_res->close();
        }
    }
    // Delete Room
    if (isset($_POST['delete_room'])) {
        $room_type_id = intval($_POST['room_type_id']);
        if ($room_type_id) {
            // Check for active reservations for any room under this room type
            $active_res_sql = "SELECT COUNT(*) FROM tbl_reservation r JOIN tbl_room rm ON r.assigned_room_id = rm.room_id WHERE rm.room_type_id = ? AND r.status IN ('pending','approved','completed')";
            $active_res_stmt = $mycon->prepare($active_res_sql);
            $active_res_stmt->bind_param('i', $room_type_id);
            $active_res_stmt->execute();
            $active_res_stmt->bind_result($active_count);
            $active_res_stmt->fetch();
            $active_res_stmt->close();
            if ($active_count > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Cannot delete: There are active reservations for this room type.']);
                exit;
            }
            $stmt = $mycon->prepare("DELETE FROM tbl_room_type WHERE room_type_id=?");
            $stmt->bind_param('i', $room_type_id);
            $stmt->execute();
            $stmt->close();
            echo json_encode(['status' => 'success', 'message' => 'Room type deleted successfully!']);
            exit;
        }
    }
    // Handle room number edit
    if (isset($_POST['edit_room_number'])) {
        $room_id = intval($_POST['room_id']);
        $room_number = trim($_POST['room_number']);
        if ($room_id && $room_number) {
            $stmt = $mycon->prepare("UPDATE tbl_room SET room_number=? WHERE room_id=?");
            $stmt->bind_param('si', $room_number, $room_id);
            if (!$stmt->execute()) {
                echo "<script>setTimeout(function(){ showAdminToast('Error: " . addslashes($stmt->error) . "', 'danger'); }, 500);</script>";
            } else {
                echo "<script>setTimeout(function(){ showAdminToast('Room number updated!', 'success'); setTimeout(function(){ location.reload(); }, 1200); }, 100);</script>";
            }
            $stmt->close();
            exit;
        }
    }
    // Handle single room delete
    if (isset($_POST['delete_room_single'])) {
        $room_id = intval($_POST['room_id']);
        if ($room_id) {
            // Check if the room is occupied or assigned to a reservation
            $check = $mycon->prepare("SELECT COUNT(*) FROM tbl_reservation WHERE assigned_room_id=? AND status IN ('pending','approved','completed')");
            $check->bind_param('i', $room_id);
            $check->execute();
            $check->bind_result($count);
            $check->fetch();
            $check->close();
            if ($count > 0) {
                echo "<script>setTimeout(function(){ showAdminToast('Cannot delete: Room is currently occupied or assigned to a reservation.', 'danger'); }, 500);</script>";
            } else {
                $stmt = $mycon->prepare("DELETE FROM tbl_room WHERE room_id=?");
                $stmt->bind_param('i', $room_id);
                if (!$stmt->execute()) {
                    echo "<script>setTimeout(function(){ showAdminToast('Error: " . addslashes($stmt->error) . "', 'danger'); }, 500);</script>";
                } else {
                    echo "<script>setTimeout(function(){ showAdminToast('Room deleted successfully!', 'success'); setTimeout(function(){ location.reload(); }, 1200); }, 100);</script>";
                }
                $stmt->close();
            }
            exit;
        }
    }
    // Handle add single room
    if (isset($_POST['add_room_single'])) {
        $room_type_id = intval($_POST['room_type_id']);
        $room_number = trim($_POST['room_number']);
        if ($room_type_id && $room_number) {
            // Check for duplicate room number across all room types
            $dup_check = $mycon->prepare("SELECT COUNT(*) FROM tbl_room WHERE room_number=?");
            $dup_check->bind_param('s', $room_number);
            $dup_check->execute();
            $dup_check->bind_result($count);
            $dup_check->fetch();
            $dup_check->close();
            if ($count > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Room number already exists!']);
                exit;
            } else {
                $stmt = $mycon->prepare("INSERT INTO tbl_room (room_number, room_type_id, status) VALUES (?, ?, 'Available')");
                $stmt->bind_param('si', $room_number, $room_type_id);
                $stmt->execute();
                $new_room_id = $mycon->insert_id;
                $stmt->close();
                // Fetch the new room details
                $room = $mycon->query("SELECT * FROM tbl_room WHERE room_id = $new_room_id")->fetch_assoc();
                echo json_encode(['status' => 'success', 'message' => 'Room number added successfully!', 'room' => $room]);
                exit;
            }
        }
    }
}
// Fetch all rooms and services into arrays
$rooms_result = $mycon->query("SELECT * FROM tbl_room_type ORDER BY type_name ASC");
$rooms = [];
while ($row = $rooms_result->fetch_assoc()) {
    // Ensure max_occupancy and image are set with default values
    if (!array_key_exists('max_occupancy', $row) || $row['max_occupancy'] === null) $row['max_occupancy'] = 1;
    if (!array_key_exists('image', $row) || $row['image'] === null) $row['image'] = '';
    // Fetch all rooms for this type
    $room_type_id = $row['room_type_id'];
    $room_list = [];
    $room_query = $mycon->query("SELECT * FROM tbl_room WHERE room_type_id = $room_type_id ORDER BY room_number ASC");
    while ($r = $room_query->fetch_assoc()) {
        $room_list[] = $r;
    }
    $row['room_list'] = $room_list;
    $rooms[] = $row;
}
$services_result = $mycon->query("SELECT * FROM tbl_services ORDER BY service_name ASC");
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Room Types - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        .container { margin-left: 240px; margin-top: 70px; padding: 40px 24px 24px 24px; }
        .title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; }
        .section { background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 32px 18px; margin-bottom: 32px; }
        .form-label { color: #ffa533; font-weight: 500; }
        .table thead { background: #1a1a2e; color: #ffa533; }
        .table tbody tr { color: #fff; }
        .table tbody tr:nth-child(even) { background: #23234a; }
        .table td, .table th { vertical-align: middle; }
        .btn { min-width: 80px; }
        .dropdown-toggle { min-width: 180px; }
        .edit-form { display: none; }
        .room-card { background: #23234a; color: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); margin-bottom: 24px; }
        .room-card .card-title { color: #ffa533; font-weight: 600; }
        .room-card .card-subtitle { color: #fff; }
        .room-card .card-footer { background: transparent; border: none; }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="container">
    <div class="title">Room Types Management</div>
    <?php if ($msg): ?>
        <div class="alert alert-success text-center mb-3"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <!-- Add Room Type Button -->
    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-warning fw-bold" data-bs-toggle="modal" data-bs-target="#addRoomTypeModal">
        <i class="bi bi-plus-circle"></i> Add Room Type
      </button>
    </div>
    <!-- Add Room Type Modal -->
    <div class="modal fade" id="addRoomTypeModal" tabindex="-1" aria-labelledby="addRoomTypeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-light rounded-4">
          <div class="modal-header border-0">
            <h5 class="modal-title text-warning" id="addRoomTypeModalLabel">Add Room Type</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="roomForm" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
              <div class="row g-4">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Room Type Name</label>
                    <input type="text" name="type_name" class="form-control" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Room Price</label>
                    <input type="number" name="nightly_rate" class="form-control" min="1" step="0.01" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Max Occupancy</label>
                    <input type="number" name="max_occupancy" class="form-control" min="1" max="20" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Image</label>
                    <input type="file" name="room_image" accept=".jpg,.jpeg,.png" class="form-control form-control-sm">
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Inclusions (Services)</label>
                  <div class="row g-2 mb-2" style="background:#23234a; border:none;">
                    <?php foreach ($services as $i => $service): ?>
                      <div class="col-12 col-md-4 mb-2">
                        <div class="form-check" style="padding-left:1.8em;">
                          <input class="form-check-input" type="checkbox" name="included_services[]" value="<?php echo $service['service_id']; ?>" id="addinc<?php echo $service['service_id']; ?>">
                          <label class="form-check-label" for="addinc<?php echo $service['service_id']; ?>" style="color:#ffa533;font-weight:500;">
                            <?php echo htmlspecialchars($service['service_name']); ?>
                          </label>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <small class="text-muted">Check to include as room inclusion</small>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0">
              <button type="submit" name="add_room" class="btn btn-success">Add Room Type</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <!-- End Add Room Type Modal -->
    <!-- Room Types Section (existing code) -->
    <div class="section">
      <!-- Room Types Cards (existing code) -->
      <h5 class="text-warning mb-3">Room Types</h5>
      <div class="row g-4">
        <?php foreach ($rooms as $room): ?>
        <div class="col-md-4">
          <div class="card room-card h-100">
            <div class="card-body p-0">
              <?php 
              $fallback_image = '../assets/rooms/standard.avif';
              $img = !empty($room['image']) ? '../assets/rooms/' . htmlspecialchars($room['image']) : $fallback_image;
              ?>
              <img src="<?php echo $img; ?>" alt="Room Image" class="img-fluid rounded-top" style="width:100%;height:180px;object-fit:cover;">
              <div class="p-3">
                <h5 class="card-title text-warning"><?php echo htmlspecialchars($room['type_name']); ?></h5>
                <h6 class="card-subtitle mb-2">₱<?php echo number_format(isset($room['nightly_rate']) ? $room['nightly_rate'] : 0, 2); ?></h6>
                <div class="mb-1"><i class="bi bi-people"></i> Max Occupancy: <?php echo htmlspecialchars($room['max_occupancy']); ?></div>
                <p class="card-text"><?php echo htmlspecialchars($room['description']); ?></p>
                <button class="btn btn-info btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#roomModal<?php echo $room['room_type_id']; ?>">View Rooms</button>
                <button class="btn btn-danger btn-sm mt-2 ms-2 delete-room-type-btn" data-room-type-id="<?php echo $room['room_type_id']; ?>" title="Delete Room Type"><i class="bi bi-trash"></i></button>
              </div>
            </div>
            <!-- Modal for Room List -->
            <div class="modal fade" id="roomModal<?php echo $room['room_type_id']; ?>" tabindex="-1" aria-labelledby="roomModalLabel<?php echo $room['room_type_id']; ?>" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-dark text-light rounded-4">
                  <div class="modal-header border-0">
                    <h5 class="modal-title text-warning" id="roomModalLabel<?php echo $room['room_type_id']; ?>">Rooms for <?php echo htmlspecialchars($room['type_name']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body">
                    <!-- Edit Room Type Form -->
                    <form method="POST" enctype="multipart/form-data" class="mb-4 p-3 rounded bg-secondary-subtle">
                      <input type="hidden" name="room_type_id" value="<?php echo $room['room_type_id']; ?>">
                      <div class="row g-4">
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label">Room Type Name</label>
                            <input type="text" name="type_name" value="<?php echo htmlspecialchars($room['type_name']); ?>" class="form-control form-control-sm" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Room Price</label>
                            <input type="number" name="nightly_rate" value="<?php echo isset($room['nightly_rate']) ? $room['nightly_rate'] : ''; ?>" class="form-control form-control-sm" min="1" step="0.01" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Max Occupancy</label>
                            <input type="number" name="max_occupancy" value="<?php echo $room['max_occupancy']; ?>" class="form-control form-control-sm" min="1" max="20" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" value="<?php echo htmlspecialchars($room['description']); ?>" class="form-control form-control-sm">
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Image</label>
                            <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($room['image'] ?? ''); ?>">
                            <input type="file" name="room_image" accept=".jpg,.jpeg,.png,.avif" class="form-control form-control-sm">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Inclusions (Services)</label>
                          <div class="row g-2 mb-2" style="background:#23234a; border:none;">
                            <?php
                            // Fetch included services for this room type
                            $included_services = [];
                            $inc_res = $mycon->query("SELECT service_id FROM tbl_room_services WHERE room_type_id = " . intval($room['room_type_id']));
                            while ($inc_row = $inc_res->fetch_assoc()) {
                                $included_services[] = $inc_row['service_id'];
                            }
                            foreach ($services as $service): ?>
                              <div class="col-12 col-md-4 mb-2">
                                <div class="form-check" style="padding-left:1.8em;">
                                  <input class="form-check-input" type="checkbox" name="included_services[]" value="<?php echo $service['service_id']; ?>" id="editinc<?php echo $room['room_type_id']; ?>_<?php echo $service['service_id']; ?>" <?php echo in_array($service['service_id'], $included_services) ? 'checked' : ''; ?>>
                                  <label class="form-check-label" for="editinc<?php echo $room['room_type_id']; ?>_<?php echo $service['service_id']; ?>" style="color:#ffa533;font-weight:500;">
                                    <?php echo htmlspecialchars($service['service_name']); ?>
                                  </label>
                                </div>
                              </div>
                            <?php endforeach; ?>
                          </div>
                          <small class="text-muted">Check to include as room inclusion</small>
                        </div>
                      </div>
                      <div class="row mt-2">
                        <div class="col text-end">
                          <button type="submit" name="edit_room" class="btn btn-primary btn-sm">Save Room Type</button>
                        </div>
                      </div>
                    </form>
                    <!-- End Edit Room Type Form -->
                    <!-- Add Room Form -->
                    <form method="POST" class="row g-3 align-items-end mb-3 add-room-form">
                      <input type="hidden" name="add_room_single" value="1">
                      <input type="hidden" name="room_type_id" value="<?php echo $room['room_type_id']; ?>">
                      <div class="col-md-6">
                        <input type="text" name="room_number" class="form-control form-control-sm" placeholder="Room Number" required>
                      </div>
                      <div class="col-md-3">
                        <button type="submit" class="btn btn-success btn-sm">Add Room</button>
                      </div>
                    </form>
                    <table class="table table-dark table-bordered align-middle">
                      <thead>
                        <tr>
                          <th>Room Number</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody id="roomListTable<?php echo $room['room_type_id']; ?>">
                        <?php if (empty($room['room_list'])): ?>
                          <tr><td colspan="2" class="text-center text-muted">No rooms yet. Add one above.</td></tr>
                        <?php else: ?>
                          <?php foreach ($room['room_list'] as $r): ?>
                          <tr>
                            <form method="POST" class="room-row-form" data-room-id="<?php echo $r['room_id']; ?>">
                              <input type="hidden" name="room_id" value="<?php echo $r['room_id']; ?>">
                              <td class="d-flex align-items-center gap-2">
                                <input type="text" name="room_number" value="<?php echo htmlspecialchars($r['room_number']); ?>" class="form-control form-control-sm room-number-input" required readonly style="max-width:110px;">
                                <button type="button" class="btn btn-success btn-sm save-room-btn d-none" title="Save"><i class="bi bi-check"></i></button>
                                <button type="button" class="btn btn-secondary btn-sm cancel-room-btn d-none" title="Cancel"><i class="bi bi-x"></i></button>
                                <button type="button" class="btn btn-danger btn-sm delete-room-btn" title="Delete"><i class="bi bi-trash"></i></button>
                              </td>
                              <td>
                                <span class="badge bg-<?php echo $r['status'] === 'Available' ? 'success' : ($r['status'] === 'Occupied' ? 'danger' : 'secondary'); ?>"><?php echo htmlspecialchars($r['status']); ?></span>
                              </td>
                            </form>
                          </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
</div>
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
  <div id="adminToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="adminToastBody">
        <!-- Toast message here -->
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<!-- Add Edit Room Number Modal -->
<div class="modal fade" id="editRoomNumberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header border-0">
        <h5 class="modal-title text-warning">Edit Room Number</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editRoomNumberForm" method="POST">
        <div class="modal-body">
          <input type="hidden" name="room_id" id="editRoomId">
          <input type="hidden" name="edit_room_number" value="1">
          <div class="mb-3">
            <label class="form-label">Room Number</label>
            <input type="text" name="room_number" id="editRoomNumber" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Add Delete Confirmation Modal -->
<div class="modal fade" id="deleteRoomConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this room number?</p>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteRoomBtn">Delete</button>
      </div>
    </div>
  </div>
</div>
<!-- Delete Room Type Confirmation Modal -->
<div class="modal fade" id="deleteRoomTypeModal" tabindex="-1" aria-labelledby="deleteRoomTypeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light rounded-4">
      <div class="modal-header border-0">
        <h5 class="modal-title text-danger" id="deleteRoomTypeModalLabel">Confirm Delete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this room type? All rooms under this type will also be deleted.
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteRoomTypeBtn">Delete</button>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showAdminToast(message, type) {
  var toastEl = document.getElementById('adminToast');
  var toastBody = document.getElementById('adminToastBody');
  toastBody.textContent = message;
  toastEl.classList.remove('text-bg-primary', 'text-bg-success', 'text-bg-danger');
  if (type === 'success') toastEl.classList.add('text-bg-success');
  else if (type === 'danger') toastEl.classList.add('text-bg-danger');
  else toastEl.classList.add('text-bg-primary');
  var toast = new bootstrap.Toast(toastEl);
  toast.show();
}
function attachRoomEditListeners() {
  // Save Room Number
  document.querySelectorAll('.save-room-btn').forEach(function(btn) {
    btn.onclick = function() {
      var tr = btn.closest('tr');
      if (!tr) return;
      var roomIdInput = tr.querySelector('input[name="room_id"]');
      var roomNumberInput = tr.querySelector('.room-number-input');
      if (!roomIdInput || !roomNumberInput) return;
      var roomId = roomIdInput.value;
      var newRoomNumber = roomNumberInput.value;
      fetch('', {
        method: 'POST',
        body: new URLSearchParams({
          edit_room_number: 1,
          room_id: roomId,
          room_number: newRoomNumber
        })
      })
      .then(response => response.text())
      .then(() => {
        showAdminToast('Room number updated successfully!', 'success');
        roomNumberInput.setAttribute('data-original-value', newRoomNumber);
      })
      .catch(() => {
        showAdminToast('Error updating room number', 'danger');
      });
    };
  });
  // Cancel Room Number Edit
  document.querySelectorAll('.cancel-room-btn').forEach(function(btn) {
    btn.onclick = function() {
      var tr = btn.closest('tr');
      if (!tr) return;
      var roomNumberInput = tr.querySelector('.room-number-input');
      if (!roomNumberInput) return;
      var originalValue = roomNumberInput.getAttribute('data-original-value');
      roomNumberInput.value = originalValue;
    };
  });
  // Delete Room with modal confirmation
  let roomIdToDelete = null;
  document.querySelectorAll('.delete-room-btn').forEach(function(btn) {
    btn.onclick = function() {
      var tr = btn.closest('tr');
      if (!tr) return;
      var roomIdInput = tr.querySelector('input[name="room_id"]');
      var statusBadge = tr.querySelector('span.badge');
      if (!roomIdInput || !statusBadge) return;
      var status = statusBadge.textContent.trim();
      if (status === 'Occupied') {
        showAdminToast('Cannot delete: Room is currently occupied.', 'danger');
        return;
      }
      roomIdToDelete = { tr: tr, roomId: roomIdInput.value };
      var deleteModal = new bootstrap.Modal(document.getElementById('deleteRoomConfirmModal'));
      deleteModal.show();
    };
  });
  var confirmDeleteBtn = document.getElementById('confirmDeleteRoomBtn');
  if (confirmDeleteBtn) {
    confirmDeleteBtn.onclick = function() {
      if (!roomIdToDelete) return;
      var fd = new FormData();
      fd.append('delete_room_single', '1');
      fd.append('room_id', roomIdToDelete.roomId);
      fetch('', { method: 'POST', body: fd })
        .then(response => response.text())
        .then(() => {
          showAdminToast('Room deleted successfully!', 'success');
          roomIdToDelete.tr.remove();
          var deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteRoomConfirmModal'));
          if (deleteModal) deleteModal.hide();
          roomIdToDelete = null;
        })
        .catch(() => {
          showAdminToast('Error deleting room', 'danger');
        });
    };
  }
}

// Handle Edit Room Number Form Submit
document.getElementById('editRoomNumberForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  fetch('', {
    method: 'POST',
    body: new FormData(this)
  })
  .then(response => response.text())
  .then(() => {
    // Hide modal
    bootstrap.Modal.getInstance(document.getElementById('editRoomNumberModal')).hide();
    
    // Show success message
    showAdminToast('Room number updated successfully!', 'success');
    
    // Refresh the room list
    var roomTypeId = document.querySelector('.room-row-form[data-room-id="' + this.querySelector('#editRoomId').value + '"]')
      .closest('.modal').id.replace('roomModal', '');
    fetchRoomList(roomTypeId);
  })
  .catch(error => {
    showAdminToast('Error updating room number', 'danger');
  });
});

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.add-room-form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var fd = new FormData(form);
      fetch('', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'success') {
            showAdminToast(data.message, 'success');
            // Add the new room to the table
            var table = form.closest('.modal-body').querySelector('table tbody');
            var tr = document.createElement('tr');
            tr.innerHTML = `
              <td class="d-flex align-items-center gap-2">
                <input type="hidden" name="room_id" value="${data.room.room_id}">
                <input type="text" name="room_number" value="${data.room.room_number}" class="form-control form-control-sm room-number-input" required style="max-width:110px;" data-original-value="${data.room.room_number}">
                <button type="button" class="btn btn-success btn-sm save-room-btn" title="Save"><i class="bi bi-check"></i></button>
                <button type="button" class="btn btn-secondary btn-sm cancel-room-btn" title="Cancel"><i class="bi bi-x"></i></button>
                <button type="button" class="btn btn-danger btn-sm delete-room-btn" title="Delete"><i class="bi bi-trash"></i></button>
              </td>
              <td><span class="badge bg-success">${data.room.status}</span></td>
            `;
            table.appendChild(tr);
            form.reset();
            attachRoomEditListeners(); // Attach listeners to the new row
          } else {
            showAdminToast(data.message, 'danger');
            highlightRoomNumberField(fd.get('room_number'));
          }
        });
    });
  });
  attachRoomEditListeners(); // Attach listeners on page load
});
function fetchRoomList(roomTypeId) {
    fetch('ajax_room_status_table.php?room_type_id=' + roomTypeId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('roomListTable' + roomTypeId).innerHTML = html;
            attachRoomEditListeners(); // Attach listeners after AJAX update
        });
}
// Attach event listeners to all View Rooms buttons
// Assumes each button has data-bs-toggle="modal" and data-bs-target="#roomModal{room_type_id}"
document.querySelectorAll('button[data-bs-target^="#roomModal"]').forEach(btn => {
    btn.addEventListener('click', function() {
        var roomTypeId = this.getAttribute('data-bs-target').replace('#roomModal', '');
        fetchRoomList(roomTypeId);
    });
});
let roomTypeIdToDelete = null;
document.querySelectorAll('.delete-room-type-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    roomTypeIdToDelete = this.getAttribute('data-room-type-id');
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteRoomTypeModal'));
    deleteModal.show();
  });
});
document.getElementById('confirmDeleteRoomTypeBtn').addEventListener('click', function() {
  if (!roomTypeIdToDelete) return;
  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'delete_room=1&room_type_id=' + encodeURIComponent(roomTypeIdToDelete)
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'success') {
      var card = document.querySelector('.delete-room-type-btn[data-room-type-id="' + roomTypeIdToDelete + '"]').closest('.col-md-4');
      if (card) card.remove();
      showAdminToast(data.message, 'success');
    } else {
      showAdminToast(data.message || 'Failed to delete room type.', 'danger');
    }
    var deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteRoomTypeModal'));
    if (deleteModal) deleteModal.hide();
    roomTypeIdToDelete = null;
  })
  .catch(() => {
    showAdminToast('Failed to delete room type.', 'danger');
    var deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteRoomTypeModal'));
    if (deleteModal) deleteModal.hide();
    roomTypeIdToDelete = null;
  });
});
</script>
</body>
</html> 