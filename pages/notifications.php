<?php
session_start();
if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit();
}
include('../functions/db_connect.php');
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';
$where = 'WHERE guest_id = ?';
$params = [$_SESSION['guest_id']];
$types = 'i';
if ($type_filter !== '') {
    $where .= ' AND type = ?';
    $params[] = $type_filter;
    $types .= 's';
}
$sql = "SELECT * FROM user_notifications $where ORDER BY created_at DESC";
$stmt = mysqli_prepare($mycon, $sql);
if ($types && count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}
mysqli_stmt_close($stmt);
// Mark all as read for the current user only
$guest_id = $_SESSION['guest_id'];
$mycon->query("UPDATE user_notifications SET is_read = 1 WHERE guest_id = $guest_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; }
        .notif-card { background: #23234a; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.12); margin-bottom: 18px; }
        .notif-type-reservation { color: #FF8C00; }
        .notif-type-wallet { color: #00c896; }
        .notif-type-profile { color: #1e90ff; }
        .notif-date { font-size: 0.95em; color: #bdbdbd; }
        .notif-unread { border-left: 4px solid #FF8C00; }
    </style>
</head>
<body>
<?php include '../components/user_navigation.php'; ?>
<?php if (isset($_SESSION['success'])): ?>
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1200;">
  <div class="toast align-items-center text-bg-success border-0 show" id="successToast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3500">
    <div class="d-flex">
      <div class="toast-body">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var toastEl = document.getElementById('successToast');
  if (toastEl) {
    var toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toast.show();
  }
});
</script>
<?php endif; ?>
<div class="container py-5">
    <h2 class="mb-4 text-warning"><i class="bi bi-bell-fill"></i> My Notifications</h2>
    <form class="row g-3 mb-4" method="get">
        <div class="col-md-4">
            <select name="type" class="form-select">
                <option value="">All Types</option>
                <option value="reservation" <?php if($type_filter==='reservation') echo 'selected'; ?>>Reservation</option>
                <option value="wallet" <?php if($type_filter==='wallet') echo 'selected'; ?>>Wallet</option>
                <option value="profile" <?php if($type_filter==='profile') echo 'selected'; ?>>Profile</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>
    <?php if (count($notifications) > 0): ?>
        <form method="post" action="delete_notifications.php" id="deleteNotifsForm">
        <div class="mb-3 d-flex align-items-center gap-2">
            <button type="button" class="btn btn-danger" id="deleteSelectedBtn" disabled>Delete Selected</button>
            <div class="form-check ms-3">
                <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                <label class="form-check-label" for="selectAllCheckbox">Select All</label>
            </div>
        </div>
        <?php $latest_notif = $notifications[0]; ?>
        <!-- Toast Notification for the latest notification -->
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
            <div id="notifToast" class="toast align-items-center text-bg-info border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="bi bi-bell-fill me-2"></i>
                        <?php echo htmlspecialchars($latest_notif['message']); ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-light">
              <div class="modal-header border-0">
                <h5 class="modal-title text-warning" id="deleteConfirmModalLabel"><i class="bi bi-exclamation-triangle"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                Are you sure you want to delete the selected notifications?
              </div>
              <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</button>
              </div>
            </div>
          </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toastEl = document.getElementById('notifToast');
            if (toastEl) {
                var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
                toast.show();
            }
            var checkboxes = document.querySelectorAll('.notif-checkbox');
            var deleteBtn = document.getElementById('deleteSelectedBtn');
            var selectAllCheckbox = document.getElementById('selectAllCheckbox');
            var deleteForm = document.getElementById('deleteNotifsForm');
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            checkboxes.forEach(function(cb) {
                cb.addEventListener('change', function() {
                    var anyChecked = Array.from(checkboxes).some(x => x.checked);
                    deleteBtn.disabled = !anyChecked;
                    // If any are unchecked, uncheck select all
                    if (!cb.checked && selectAllCheckbox.checked) selectAllCheckbox.checked = false;
                    // If all are checked, check select all
                    if (Array.from(checkboxes).every(x => x.checked)) selectAllCheckbox.checked = true;
                });
            });
            selectAllCheckbox.addEventListener('change', function() {
                checkboxes.forEach(function(cb) {
                    cb.checked = selectAllCheckbox.checked;
                });
                var anyChecked = Array.from(checkboxes).some(x => x.checked);
                deleteBtn.disabled = !anyChecked;
            });
            // Show modal on delete button click
            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                deleteModal.show();
            });
            // On confirm in modal, submit the form
            confirmDeleteBtn.addEventListener('click', function() {
                deleteForm.submit();
            });
        });
        </script>
        <?php foreach ($notifications as $notif): ?>
        <div class="notif-card p-4 mb-3 <?php if(!$notif['is_read']) echo 'notif-unread'; ?> d-flex align-items-start">
            <div class="form-check me-3 mt-2">
                <input class="form-check-input notif-checkbox" type="checkbox" name="notif_ids[]" value="<?php echo $notif['user_notication_id']; ?>">
            </div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-2">
                    <?php if ($notif['type'] === 'reservation'): ?>
                        <i class="bi bi-calendar2-check notif-type-reservation me-2"></i>
                    <?php elseif ($notif['type'] === 'wallet'): ?>
                        <i class="bi bi-wallet2 notif-type-wallet me-2"></i>
                    <?php elseif ($notif['type'] === 'profile'): ?>
                        <i class="bi bi-person-circle notif-type-profile me-2"></i>
                    <?php else: ?>
                        <i class="bi bi-info-circle me-2"></i>
                    <?php endif; ?>
                    <span class="fw-bold text-capitalize me-2 notif-type-<?php echo htmlspecialchars($notif['type']); ?>">
                        <?php echo htmlspecialchars($notif['type']); ?>
                    </span>
                    <span class="notif-date ms-auto"><?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?></span>
                </div>
                <div><?php echo htmlspecialchars($notif['message']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        </form>
    <?php else: ?>
        <div class="alert alert-info">No notifications yet.</div>
    <?php endif; ?>
</div>
</body>
</html> 