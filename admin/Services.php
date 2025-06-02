<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';

// Handle add, edit, delete actions for services only
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add Service
    if (isset($_POST['add_service'])) {
        $service_name = trim($_POST['service_name']);
        $service_description = trim($_POST['service_description']);
        if ($service_name && $service_description) {
            $stmt = $mycon->prepare("INSERT INTO tbl_services (service_name, service_description) VALUES (?, ?)");
            $stmt->bind_param('ss', $service_name, $service_description);
            $stmt->execute();
            $stmt->close();
            $msg = 'Service added!';
        }
    }
    // Edit Service
    if (isset($_POST['edit_service'])) {
        $service_id = intval($_POST['service_id']);
        $service_name = trim($_POST['service_name']);
        $service_description = trim($_POST['service_description']);
        if ($service_id && $service_name && $service_description) {
            $stmt = $mycon->prepare("UPDATE tbl_services SET service_name=?, service_description=? WHERE service_id=?");
            $stmt->bind_param('ssi', $service_name, $service_description, $service_id);
            $stmt->execute();
            $stmt->close();
            $msg = 'Service updated!';
        }
    }
    // Delete Service
    if (isset($_POST['delete_service'])) {
        $service_id = intval($_POST['service_id']);
        if ($service_id) {
            $stmt = $mycon->prepare("DELETE FROM tbl_services WHERE service_id=?");
            $stmt->bind_param('i', $service_id);
            $stmt->execute();
            $stmt->close();
            $msg = 'Service deleted!';
        }
    }
}
// Fetch all services into array
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
    <title>Admin Services - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        .container { margin-left: 240px; padding: 40px 24px 24px 24px; }
        .title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; }
        .section { background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 32px 18px; margin-bottom: 32px; }
        .form-label { color: #ffa533; font-weight: 500; }
        .table thead { background: #1a1a2e; color: #ffa533; }
        .table tbody tr { color: #fff; }
        .table tbody tr:nth-child(even) { background: #23234a; }
        .table td, .table th { vertical-align: middle; }
        .btn { min-width: 80px; }
        .dropdown-toggle { min-width: 180px; }
        .edit-service-form { display: none; }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="container">
    <div class="title">Services Management</div>
    <?php if ($msg): ?>
        <div class="alert alert-success text-center mb-3"><?php echo htmlspecialchars($msg); ?></div>
    <?php endif; ?>
    <div class="section">
      <!-- Add Service Form -->
      <form id="serviceForm" method="POST" class="mb-4">
        <h5 class="text-warning">Add Service</h5>
        <div class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Service Name</label>
            <input type="text" name="service_name" class="form-control" required>
          </div>
          <div class="col-md-5">
            <label class="form-label">Description</label>
            <input type="text" name="service_description" class="form-control">
          </div>
          <div class="col-md-1">
            <button type="submit" name="add_service" class="btn btn-success">Add</button>
          </div>
        </div>
      </form>
      <!-- Services Table -->
      <h5 class="text-warning mb-3">Services</h5>
      <div class="table-responsive">
        <table class="table table-hover table-bordered align-middle">
          <thead>
            <tr>
              <th>Name</th>
              <th>Description</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($services as $service): ?>
            <tr>
              <td><?php echo htmlspecialchars($service['service_name']); ?></td>
              <td><?php echo htmlspecialchars($service['service_description']); ?></td>
              <td>
                <!-- Edit Service Form (inline) -->
                <form class="edit-service-form d-inline-block" method="POST" style="display:none;">
                  <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                  <input type="text" name="service_name" value="<?php echo htmlspecialchars($service['service_name']); ?>" class="form-control form-control-sm mb-1" required>
                  <input type="text" name="service_description" value="<?php echo htmlspecialchars($service['service_description']); ?>" class="form-control form-control-sm mb-1">
                  <button type="submit" name="edit_service" class="btn btn-primary btn-sm mb-1">Save</button>
                  <button type="button" class="btn btn-secondary btn-sm mb-1" onclick="hideEditServiceForm(this)">Cancel</button>
                </form>
                <!-- Edit/Delete Buttons -->
                <button class="btn btn-warning btn-sm" onclick="showEditServiceForm(this)">Edit</button>
                <form method="POST" class="d-inline-block" onsubmit="return confirm('Delete this service?');">
                  <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                  <button type="submit" name="delete_service" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showEditServiceForm(btn) {
    var tr = btn.closest('tr');
    tr.querySelector('.edit-service-form').style.display = '';
    btn.style.display = 'none';
}
function hideEditServiceForm(btn) {
    var form = btn.closest('.edit-service-form');
    form.style.display = 'none';
    form.closest('tr').querySelector('.btn-warning').style.display = '';
}
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
</script>
</body>
</html>
