<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
include('../functions/db_connect.php');
$token = $_GET['token'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (empty($password) || strlen($password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $_SESSION['error'] = 'Passwords do not match.';
    } else {
        $sql = "SELECT guest_id FROM tbl_guest WHERE reset_token = ? AND reset_token_expiry > NOW() AND is_deleted = 0 LIMIT 1";
        $stmt = $mycon->prepare($sql);
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($guest_id);
            $stmt->fetch();
            $stmt->close();
            $update = $mycon->prepare("UPDATE tbl_guest SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE guest_id = ?");
            $update->bind_param('si', $password, $guest_id);
            $update->execute();
            $update->close();
            $_SESSION['success'] = 'Password reset successful! You may now log in.';
            header('Location: login.php');
            exit();
        } else {
            $_SESSION['error'] = 'Invalid or expired reset link.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
<?php include '../components/user_navigation.php'; ?>
<div class="container py-5">
    <div class="card bg-dark text-light p-4 mx-auto" style="max-width:400px;">
        <h3 class="mb-3 text-warning">Reset Password</h3>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="mb-3 position-relative">
                <label for="password" class="form-label">New Password</label>
                <input type="password" class="form-control" name="password" id="password" required>
                <button class="btn position-absolute end-0 top-0 mt-2 me-2" type="button" tabindex="-1" onclick="togglePassword('password', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <div class="mb-3 position-relative">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                <button class="btn position-absolute end-0 top-0 mt-2 me-2" type="button" tabindex="-1" onclick="togglePassword('confirm_password', this)">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <button type="submit" class="btn btn-warning w-100">Reset Password</button>
        </form>
        <div class="mt-3 text-center">
            <a href="login.php" class="text-info">Back to Login</a>
        </div>
    </div>
</div>
<script>
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html> 