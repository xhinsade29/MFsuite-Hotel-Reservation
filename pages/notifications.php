<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
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
        /* Light mode overrides */
        body.light-mode {
            background: #f8f9fa !important;
            color: #23234a !important;
        }
        body.light-mode .container {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode h2 {
            color: #ff8c00 !important;
        }
        body.light-mode .notif-card {
            background: #f7f7fa !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
            box-shadow: 0 4px 20px rgba(255,140,0,0.07);
        }
        body.light-mode .notif-unread-new {
            border-left: 5px solid #ff8c00 !important;
            background: linear-gradient(90deg, #ffe5b4 80%, #fff 100%) !important;
            box-shadow: 0 4px 18px rgba(255,140,0,0.10);
        }
        body.light-mode .notif-type-reservation {
            color: #ff8c00 !important;
        }
        body.light-mode .notif-type-wallet {
            color: #00c896 !important;
        }
        body.light-mode .notif-type-profile {
            color: #1e90ff !important;
        }
        body.light-mode .notif-date {
            color: #888 !important;
        }
        body.light-mode .badge {
            background: linear-gradient(90deg,#ff8c00 60%,#ffa533 100%) !important;
            color: #fff !important;
        }
        body.light-mode .alert-info {
            background: #ffe5b4 !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .btn-primary {
            background: linear-gradient(90deg, #ff8c00, #ffa533) !important;
            color: #fff !important;
            border: none !important;
        }
        body.light-mode .form-select, body.light-mode input, body.light-mode textarea {
            background: #fff !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .form-select:focus, body.light-mode input:focus, body.light-mode textarea:focus {
            border-color: #ff8c00 !important;
            box-shadow: 0 0 0 0.12rem rgba(255,140,0,0.13);
        }
        /* End light mode overrides */
        .notif-card { background: #23234a; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.12); margin-bottom: 18px; }
        .notif-type-reservation { color: #FF8C00; }
        .notif-type-wallet { color: #00c896; }
        .notif-type-profile { color: #1e90ff; }
        .notif-date { font-size: 0.95em; color: #bdbdbd; }
        .notif-unread-new {
            border-left: 5px solid #FF8C00;
            background: linear-gradient(90deg, #2d2d5a 80%, #23234a 100%);
            box-shadow: 0 4px 18px rgba(255,140,0,0.10);
            position: relative;
        }
    </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
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
    <?php
    $unread_count = 0;
    foreach ($notifications as $notif) {
        if (!$notif['is_read']) $unread_count++;
    }
    ?>
    <?php if ($unread_count > 0): ?>
        <div class="mb-3">
            <span class="badge" style="background:linear-gradient(90deg,#ff8c00 60%,#ffa533 100%);color:#fff;font-size:1em;padding:8px 18px;border-radius:16px;box-shadow:0 2px 8px rgba(255,140,0,0.15);font-weight:600;letter-spacing:1px;">
                <?php echo $unread_count; ?> New Notification<?php echo $unread_count > 1 ? 's' : ''; ?>
            </span>
        </div>
    <?php endif; ?>
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
        <div class="notif-card p-4 mb-3 d-flex align-items-start <?php if(!$notif['is_read']) echo 'notif-unread-new'; ?>">
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