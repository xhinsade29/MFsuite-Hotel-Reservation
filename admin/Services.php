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
            echo "<script>setTimeout(function(){ showAdminToast('Service added!', 'success'); }, 500);</script>";
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
            echo "<script>setTimeout(function(){ showAdminToast('Service updated!', 'success'); }, 500);</script>";
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
            echo "<script>setTimeout(function(){ showAdminToast('Service deleted!', 'success'); }, 500);</script>";
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
        .service-cards { display: flex; flex-wrap: wrap; gap: 28px; }
        .service-card { background: #23234a; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 28px 22px 22px 22px; min-width: 260px; max-width: 340px; flex: 1 1 260px; display: flex; flex-direction: column; align-items: flex-start; position: relative; transition: box-shadow 0.2s; }
        .service-card:hover { box-shadow: 0 8px 32px rgba(255,140,0,0.13); }
        .service-icon { font-size: 2.2rem; color: #ffa533; background: rgba(255,140,0,0.08); border-radius: 12px; padding: 12px; margin-bottom: 12px; }
        .service-title { font-size: 1.3rem; font-weight: 600; color: #ffa533; margin-bottom: 6px; }
        .service-desc { color: #fff; font-size: 1.05em; margin-bottom: 18px; min-height: 40px; }
        .service-actions { margin-top: auto; display: flex; gap: 10px; }
        .btn-edit { background: #ffa533; color: #23234a; border: none; }
        .btn-edit:hover { background: #ff8c00; color: #fff; }
        .btn-delete { background: #ff4444; color: #fff; border: none; }
        .btn-delete:hover { background: #c82333; color: #fff; }
        @media (max-width: 900px) { .container { margin-left: 70px; padding: 18px 4px; } .service-cards { gap: 18px; } }
        @media (max-width: 700px) { .service-cards { flex-direction: column; gap: 18px; } .service-card { max-width: 100%; min-width: 0; } }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="title">Services Management</div>
        <button class="btn btn-success fw-bold px-4 py-2" data-bs-toggle="modal" data-bs-target="#addServiceModal"><i class="bi bi-plus-circle me-2"></i>Add Service</button>
    </div>
    <div class="service-cards">
        <?php if (empty($services)): ?>
            <div class="text-muted">No services found. Add a new service.</div>
        <?php else: ?>
            <?php foreach ($services as $service): ?>
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-gear"></i></div>
                    <div class="service-title"><?php echo htmlspecialchars($service['service_name']); ?></div>
                    <div class="service-desc"><?php echo htmlspecialchars($service['service_description']); ?></div>
                    <div class="service-actions">
                        <button class="btn btn-edit btn-sm px-3" data-bs-toggle="modal" data-bs-target="#editServiceModal<?php echo $service['service_id']; ?>"><i class="bi bi-pencil"></i> Edit</button>
                        <form method="POST" onsubmit="return confirm('Delete this service?');" style="display:inline;">
                            <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                            <button type="submit" name="delete_service" class="btn btn-delete btn-sm px-3"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    </div>
                </div>
                <!-- Edit Service Modal -->
                <div class="modal fade" id="editServiceModal<?php echo $service['service_id']; ?>" tabindex="-1" aria-labelledby="editServiceModalLabel<?php echo $service['service_id']; ?>" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-dark text-light rounded-4">
                      <div class="modal-header border-0">
                        <h5 class="modal-title text-warning" id="editServiceModalLabel<?php echo $service['service_id']; ?>">Edit Service</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <form method="POST">
                        <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                        <div class="modal-body">
                          <div class="mb-3">
                            <label class="form-label">Service Name</label>
                            <input type="text" name="service_name" value="<?php echo htmlspecialchars($service['service_name']); ?>" class="form-control" required>
                          </div>
                          <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="service_description" value="<?php echo htmlspecialchars($service['service_description']); ?>" class="form-control">
                          </div>
                        </div>
                        <div class="modal-footer border-0">
                          <button type="submit" name="edit_service" class="btn btn-primary">Save Changes</button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-labelledby="addServiceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-light rounded-4">
      <div class="modal-header border-0">
        <h5 class="modal-title text-warning" id="addServiceModalLabel">Add Service</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Service Name</label>
            <input type="text" name="service_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Description</label>
            <input type="text" name="service_description" class="form-control">
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="submit" name="add_service" class="btn btn-success">Add Service</button>
        </div>
      </form>
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
