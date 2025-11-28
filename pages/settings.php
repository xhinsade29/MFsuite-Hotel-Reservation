<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
include('../functions/db_connect.php');
if (!isset($_SESSION['guest_id'])) {
    header("Location: login.php");
    exit();
}
$guest_id = $_SESSION['guest_id'];
// Fetch user info, now also fetch profile_picture
$sql = "SELECT first_name, middle_name, last_name, user_email, phone_number, address, bank_account_number, paypal_email, credit_card_number, gcash_number, wallet_id, wallet_balance, profile_picture FROM tbl_guest WHERE guest_id = ?";
$stmt = $mycon->prepare($sql);
$stmt->bind_param("i", $guest_id);
$stmt->execute();
$stmt->bind_result($first_name, $middle_name, $last_name, $user_email, $phone_number, $address, $bank_account_number, $paypal_email, $credit_card_number, $gcash_number, $wallet_id, $wallet_balance, $profile_picture);
$stmt->fetch();
$stmt->close();
$profile_pic = !empty($profile_picture) ? '../uploads/profile_pictures/' . $profile_picture : '../assets/default_profile.png';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Account Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; }
        body.light-mode { background: #f8f9fa; color: #23234a; }
        .settings-container { max-width: 700px; margin: 40px auto; background: #23234a; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 36px 32px; }
        .settings-title { color: #ffa533; font-weight: 700; margin-bottom: 24px; }
        .form-label { color: #ffa533; }
        .divider { border-bottom: 1.5px solid #35356a; margin: 24px 0; }
        .wallet-box { background: #18182f; border-radius: 10px; padding: 18px 22px; margin-bottom: 24px; }
        .wallet-id { color: #ffa533; font-weight: 600; }
        .wallet-balance { color: #fff; font-size: 1.2em; }
        .btn-primary { background: #ffa533; border: none; }
        .btn-primary:hover { background: #ff8c00; }
        .theme-switch { float: right; }
        body.light-mode .settings-container { background: #fff; color: #23234a; }
        body.light-mode .wallet-box { background: #f1f1f1; color: #23234a; }
        body.light-mode .form-label, body.light-mode .settings-title, body.light-mode .wallet-id { color: #ff8c00; }
    </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
<?php include('../components/user_navigation.php'); ?>
<!-- Toast Container -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 2000; min-width: 320px;">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="toast align-items-center text-bg-success border-0 show" id="successToast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="toast align-items-center text-bg-danger border-0 show" id="errorToast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-triangle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
</div>
<div class="settings-container">
    <div class="theme-switch">
        <label class="form-label me-2">Theme:</label>
        <select id="themeSelect" class="form-select d-inline-block w-auto" style="min-width:120px;">
            <option value="dark" <?php if(!isset($theme_preference) || $theme_preference !== 'light') echo 'selected'; ?>>Dark</option>
            <option value="light" <?php if(isset($theme_preference) && $theme_preference === 'light') echo 'selected'; ?>>Light</option>
        </select>
    </div>
    <h2 class="settings-title"><i class="bi bi-gear"></i> Account Settings</h2>
    <div class="wallet-box mb-4">
        <div><span class="wallet-id">Wallet ID:</span> <?php echo htmlspecialchars($wallet_id); ?></div>
        <div><span class="wallet-id">Wallet Balance:</span> ₱<?php echo number_format($wallet_balance, 2); ?></div>
    </div>
    <form action="process_update_profile.php" method="POST" class="mb-4" enctype="multipart/form-data">
        <div class="text-center mb-4">
            <div class="profile-picture-container position-relative d-inline-block">
                <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" class="rounded-circle border border-3" style="width: 120px; height: 120px; object-fit: cover;" id="profile-preview">
                <label class="profile-picture-upload position-absolute bottom-0 end-0 bg-warning rounded-circle p-2" style="cursor:pointer;">
                    <i class="bi bi-camera-fill text-dark"></i>
                    <input type="file" name="profile_picture" accept="image/*" style="display:none;" onchange="previewImage(this)">
                </label>
            </div>
        </div>
        <h5 class="mb-3 text-warning">Personal Information</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($first_name); ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($middle_name); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($last_name); ?>" required>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="user_email" class="form-control" value="<?php echo htmlspecialchars($user_email); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($phone_number); ?>" required>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-12">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($address); ?>">
            </div>
        </div>
        <div class="divider"></div>
        <h5 class="mb-3 text-warning">Payment Accounts</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Bank Account Number</label>
                <input type="text" name="bank_account_number" class="form-control" value="<?php echo htmlspecialchars($bank_account_number); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">GCash Number</label>
                <input type="text" name="gcash_number" class="form-control" value="<?php echo htmlspecialchars($gcash_number); ?>">
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">PayPal Email</label>
                <input type="email" name="paypal_email" class="form-control" value="<?php echo htmlspecialchars($paypal_email); ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Credit Card Number</label>
                <input type="text" name="credit_card_number" class="form-control" value="<?php echo htmlspecialchars($credit_card_number); ?>">
            </div>
        </div>
        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">Save Changes</button>
        </div>
    </form>
    <div class="divider"></div>
    <form action="process_update_profile.php" method="POST">
        <h5 class="mb-3 text-warning">Change Password</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_new_password" class="form-control" required>
            </div>
        </div>
        <div class="d-grid mt-4">
            <button type="submit" name="change_password" class="btn btn-primary btn-lg fw-bold shadow-sm">Change Password</button>
        </div>
    </form>
    <div class="divider"></div>
    <h5 class="mb-3 text-danger">Danger Zone</h5>
    <!-- Trigger Button -->
    <button type="button" class="btn btn-danger btn-lg fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
        Delete My Account
    </button>
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light">
          <div class="modal-header border-0">
            <h4 class="modal-title text-danger" id="deleteAccountModalLabel">Confirm Account Deletion</h4>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>
              Are you sure you want to delete your account?<br>
              <strong>This action cannot be undone.</strong><br>
              All your reservations, payments, and data will be permanently removed.<br>
            </p>
          </div>
          <div class="modal-footer border-0">
            <form method="POST" action="process_delete_account.php" class="d-inline">
              <input type="hidden" name="confirm_delete" value="1">
              <button type="submit" class="btn btn-danger">Yes, Delete My Account</button>
            </form>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          </div>
        </div>
      </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete() {
    return confirm('Are you sure you want to delete your account? This action cannot be undone.');
}
// Theme switcher
var themeSelect = document.getElementById('themeSelect');
themeSelect.addEventListener('change', function() {
    var theme = this.value;
    document.body.className = theme === 'light' ? 'light-mode' : '';
    // Optionally, save to server via AJAX or form
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'process_update_profile.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('theme_preference=' + encodeURIComponent(theme));
});
document.addEventListener('DOMContentLoaded', function() {
    var successToast = document.getElementById('successToast');
    if (successToast) {
        var toast = new bootstrap.Toast(successToast, { delay: 3500 });
        toast.show();
    }
    var errorToast = document.getElementById('errorToast');
    if (errorToast) {
        var toast = new bootstrap.Toast(errorToast, { delay: 3500 });
        toast.show();
    }
});
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profile-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html> 