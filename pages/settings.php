<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
include('../functions/db_connect.php');
if (!isset($_SESSION['guest_id'])) {
    header("Location: login.php");
    exit();
}
$guest_id = $_SESSION['guest_id'];

// Fetch user info
$sql = "SELECT first_name, middle_name, last_name, user_email, phone_number, address, profile_picture FROM tbl_guest WHERE guest_id = ?";
$stmt = $mycon->prepare($sql);
$stmt->bind_param("i", $guest_id);
$stmt->execute();
$stmt->bind_result($first_name, $middle_name, $last_name, $user_email, $phone_number, $address, $profile_picture);
$stmt->fetch();
$stmt->close();
$profile_pic = !empty($profile_picture) ? '../uploads/profile_pictures/' . $profile_picture : '../assets/default_profile.png';

// Fetch all payment accounts for the guest
$total_wallet_balance = 0.00;
$guest_payment_accounts = [];
$sql_accounts = "SELECT account_id, account_type, account_number, account_email, balance FROM guest_payment_accounts WHERE guest_id = ?";
$stmt_accounts = $mycon->prepare($sql_accounts);
$stmt_accounts->bind_param("i", $guest_id);
$stmt_accounts->execute();
$result_accounts = $stmt_accounts->get_result();

while ($row_account = $result_accounts->fetch_assoc()) {
    $normalized_account_type = strtolower(trim($row_account['account_type']));
    if ($normalized_account_type === 'wallet') {
        $total_wallet_balance = floatval($row_account['balance']);
    } else {
        // Ensure the account_type is normalized before adding to the list
        $row_account['account_type'] = $normalized_account_type;
        $guest_payment_accounts[] = $row_account;
    }
}
$stmt_accounts->close();

// Check for active reservations
$active_res_sql = "SELECT COUNT(*) FROM tbl_reservation WHERE guest_id = ? AND status IN ('pending','approved')";
$stmt = $mycon->prepare($active_res_sql);
$stmt->bind_param('i', $guest_id);
$stmt->execute();
$stmt->bind_result($active_res_count);
$stmt->fetch();
$stmt->close();
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
        <div><span class="wallet-id">Wallet Balance:</span> ₱<?php echo number_format($total_wallet_balance, 2); ?></div>
    </div>

    <!-- Personal Information Form -->
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
        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">Save Personal Changes</button>
        </div>
    </form>

    <div class="divider"></div>

    <!-- Manage Payment Accounts Form -->
    <form id="linkPaymentAccountForm" class="mb-4">
        <h5 class="mb-3 text-warning">Manage Payment Accounts</h5>
        <p class="text-secondary">Add or update your external payment accounts.</p>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <label for="paymentType" class="form-label">Account Type</label>
                <select class="form-select" id="paymentType" name="payment_account_type" required>
                    <option value="">Select Type</option>
                    <option value="gcash">GCash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="paypal">PayPal</option>
                    <option value="credit_card">Credit Card</option>
                </select>
            </div>
            <div class="col-md-8">
                <label for="accountIdentifier" class="form-label">Account Number/Email</label>
                <input type="text" class="form-control" id="accountIdentifier" name="account_number" placeholder="Enter account number or email" required>
                <small class="form-text text-muted">For Bank/Credit Card, enter account/card number. For GCash/PayPal, enter associated phone/email.</small>
            </div>
        </div>
        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-primary btn-lg fw-bold shadow-sm">Link/Update Payment Account</button>
        </div>

        <h6 class="text-warning mt-4 mb-2">Currently Linked Accounts:</h6>
        <?php if (!empty($guest_payment_accounts)): ?>
            <div class="list-group list-group-flush bg-dark" id="linkedAccountsList">
                <?php
                $shown_types = [];
                foreach ($guest_payment_accounts as $account):
                    $normalized_account_type = strtolower(trim($account['account_type'] ?? ''));
                    // Skip wallet, empty, or duplicate types
                    if ($normalized_account_type === 'wallet' || empty($normalized_account_type)) continue;
                    // Only show each account type once per account_number/email
                    $unique_key = $normalized_account_type . '|' . ($account['account_number'] ?? '') . '|' . ($account['account_email'] ?? '');
                    if (in_array($unique_key, $shown_types)) continue;
                    $shown_types[] = $unique_key;
                    $display_account_type = $normalized_account_type === 'bank_transfer' ? 'Bank Transfer' : ucfirst(str_replace('_', ' ', $normalized_account_type));
                    if (empty(trim($display_account_type))) continue;
                ?>
                <div class="list-group-item bg-dark text-light border-secondary d-flex justify-content-between align-items-center mb-2 rounded" id="account-<?php echo $account['account_id']; ?>">
                    <div>
                        <strong class="text-info">
                            <?php echo htmlspecialchars($display_account_type); ?>:
                        </strong>
                        <?php
                        if (!empty($account['account_number'])) {
                            echo htmlspecialchars($account['account_number']);
                        } elseif (!empty($account['account_email'])) {
                            echo htmlspecialchars($account['account_email']);
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger unlink-btn" data-bs-toggle="modal" data-bs-target="#unlinkAccountModal" data-account-id="<?php echo $account['account_id']; ?>" data-account-type="<?php echo htmlspecialchars($display_account_type); ?>"><i class="bi bi-trash"></i> Unlink</button>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-secondary" id="noAccountsMessage">No external payment accounts linked yet.</p>
        <?php endif; ?>
    </form>

    <div class="divider"></div>

    <!-- Change Password Form -->
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

    <!-- Danger Zone (Account Deletion) -->
    <h5 class="mb-3 text-danger">Danger Zone</h5>
    <?php if ($active_res_count > 0): ?>
        <button type="button" class="btn btn-danger btn-lg fw-bold shadow-sm" disabled data-bs-toggle="tooltip" data-bs-placement="top" title="You cannot delete your account while you have active reservations (pending or approved). Please cancel all active reservations first.">
            Delete My Account
        </button>
        <div class="delete-disabled-msg text-danger mt-2 p-3" style="background: rgba(255,140,0,0.08); border: 2px solid rgba(255,255,255,0.18); border-radius: 14px; font-weight: 500; max-width: 500px;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            You cannot delete your account while you have active reservations (pending or approved).<br>
            Please cancel all active reservations first.
        </div>
    <?php else: ?>
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
                <form id="deleteAccountForm" method="POST" action="process_delete_account.php" class="d-inline">
                  <input type="hidden" name="confirm_delete" value="1">
                  <button type="submit" class="btn btn-danger">Yes, Delete My Account</button>
                </form>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </div>
          </div>
        </div>
    <?php endif; ?>
</div>

<!-- Unlink Account Confirmation Modal -->
<div class="modal fade" id="unlinkAccountModal" tabindex="-1" aria-labelledby="unlinkAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-0">
                <h5 class="modal-title text-warning" id="unlinkAccountModalLabel">Confirm Unlink Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to unlink your <strong id="modalAccountType"></strong> account?</p>
                <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> This action will remove the account details from your profile. It does not affect your actual balance on the external platform.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmUnlinkBtn">Unlink Account</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profile-preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
$(document).ready(function() {
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

    // Theme switcher
    var themeSelect = document.getElementById('themeSelect');
    themeSelect.addEventListener('change', function() {
        var theme = this.value;
        document.body.className = theme === 'light' ? 'light-mode' : '';
        // Save to server via AJAX
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'process_update_profile.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send('theme_preference=' + encodeURIComponent(theme));
    });

    // Enable Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Show toast messages (existing function, ensure it's globally accessible or re-evaluated if modified by previous steps)
    window.showToast = function(message, type) {
        var toastHtml = `<div class="toast align-items-center text-bg-${type} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">` +
            `<div class="d-flex"><div class="toast-body">` + (type === 'success' ? '<i class="bi bi-check-circle me-2"></i> ' : '<i class="bi bi-exclamation-triangle me-2"></i> ') + message + `</div>` +
            `<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`;
        var $toast = $(toastHtml);
        $('.toast-container').append($toast);
        var toast = new bootstrap.Toast($toast[0], { delay: 4000 });
        toast.show();
        $toast.on('hidden.bs.toast', function() { $(this).remove(); });
    };

    // Handle Unlink Account Modal
    var unlinkAccountModal = document.getElementById('unlinkAccountModal');
    var accountIdToUnlink = null;
    var accountTypeToUnlink = null;

    $(unlinkAccountModal).on('show.bs.modal', function (event) { // Use jQuery event for consistency
        var button = $(event.relatedTarget); // Button that triggered the modal
        accountIdToUnlink = button.data('account-id'); // Use .data() for data attributes
        accountTypeToUnlink = button.data('account-type');
        var modalAccountType = $(this).find('#modalAccountType'); // Use $(this) to scope the search
        modalAccountType.text(accountTypeToUnlink);
    });

    $('#confirmUnlinkBtn').on('click', function() { // Use jQuery event for consistency
        if (accountIdToUnlink) {
            console.log('Attempting to unlink account ID:', accountIdToUnlink);
            $.ajax({
                url: 'process_update_profile.php',
                type: 'POST',
                dataType: 'json', // Expect JSON response
                data: {
                    action: 'delete_payment_account',
                    account_id: accountIdToUnlink
                },
                success: function(response) {
                    console.log('AJAX Success Response:', response);
                    if (response.success) {
                        showToast(response.message, 'success');
                        // Remove the unlinked account item from the DOM
                        $('#account-' + accountIdToUnlink).remove();
                        // Check if no accounts left and show message
                        if ($('#linkedAccountsList .list-group-item').length === 0) {
                            $('#linkedAccountsList').replaceWith('<p class="text-secondary" id="noAccountsMessage">No external payment accounts linked yet.</p>');
                        }
                        location.reload(); // Reload the page after successful unlink
                    } else {
                        // Handle cases where PHP reports an error
                        showToast(response.message || 'Unknown error occurred.', 'danger');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error, xhr);
                    showToast('An error occurred during unlinking. Please try again.', 'danger');
                },
                complete: function() {
                    console.log('AJAX Complete: Hiding modal.');
                    $('#unlinkAccountModal').modal('hide'); // Ensure modal closes
                }
            });
        }
    });

    // Handle Link/Update Payment Account Form Submission
    $('#linkPaymentAccountForm').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true).text('Processing...');

        $.ajax({
            url: 'process_update_profile.php',
            type: 'POST',
            dataType: 'json',
            data: $form.serialize(),
            success: function(response) {
                console.log('Link/Update AJAX Success Response:', response);
                if (response.success) {
                    showToast(response.message, 'success');
                    // Clear form fields after successful submission
                    $form[0].reset();
                    // Reload the page to reflect changes and update notifications
                    location.reload(); 
                } else {
                    showToast(response.message || 'Unknown error occurred.', 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Link/Update AJAX Error:', status, error, xhr);
                showToast('An error occurred while linking/updating account. Please try again.', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Link/Update Payment Account');
            }
        });
    });

    // AJAX account deletion (for entire account deletion - keeping this as it's separate from payment account unlink)
    $('#deleteAccountForm').on('submit', function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true);
        $.ajax({
            url: 'process_delete_account.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(resp) {
                if (resp.success && resp.deleted) {
                    window.location.href = 'login.php?msg=Account+deleted+successfully';
                } else if (resp.success && resp.confirm) {
                    // Should not happen in AJAX, but fallback
                } else {
                    showToast(resp.message || 'Unable to delete account.', 'danger');
                }
            },
            error: function(xhr) {
                showToast('An error occurred. Please try again.', 'danger');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
</body>
</html>