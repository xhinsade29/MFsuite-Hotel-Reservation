<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
$admin_id = $_SESSION['admin_id'];
$admin = [
    'full_name' => $_SESSION['full_name'] ?? '',
    'email' => $_SESSION['email'] ?? '',
    'username' => $_SESSION['username'] ?? '',
    'role' => $_SESSION['role'] ?? '',
];
$sql = "SELECT full_name, email, username, role FROM tbl_admin WHERE admin_id = ? LIMIT 1";
$stmt = $mycon->prepare($sql);
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $username, $role);
if ($stmt->fetch()) {
    $admin['full_name'] = $full_name;
    $admin['email'] = $email;
    $admin['username'] = $username;
    $admin['role'] = $role;
}
$stmt->close();
$success = isset($_GET['msg']) ? $_GET['msg'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        .profile-container { margin-left: 240px; padding: 40px 24px 24px 24px; max-width: 600px; }
        .profile-title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; }
        .profile-card { background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 32px 24px; }
        .profile-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #FF8C00; margin-bottom: 18px; }
        .form-label { color: #ffa533; font-weight: 500; }
        .btn-primary { background-color: #FF8C00; border: none; }
        .btn-primary:hover { background-color: #e67c00; }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="profile-container">
    <div class="profile-title">Admin Profile</div>
    <div class="profile-card">
        <div class="text-center mb-4">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['full_name'] ?: $admin['username']); ?>&background=FF8C00&color=fff" class="profile-avatar" alt="Admin Avatar">
            <h4 class="fw-semibold mb-0"><?php echo htmlspecialchars($admin['full_name']); ?></h4>
            <span class="text-warning"><?php echo ucfirst($admin['role']); ?></span>
        </div>
        <?php if ($success): ?>
            <div class="alert alert-success text-center py-2 mb-3"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST" action="update_profile.php">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password <span class="text-secondary" style="font-size:0.9em;">(leave blank to keep current)</span></label>
                <input type="password" class="form-control" name="password" placeholder="New Password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
        </form>
    </div>
</div>
</body>
</html> 