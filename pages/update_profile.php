<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: login.php");
    exit();
}

include('../functions/db_connect.php');

// Get current user data
$guest_id = $_SESSION['guest_id'];
$sql = "SELECT first_name, middle_name, last_name, user_email, phone_number, address, profile_picture FROM tbl_guest WHERE guest_id = ?";
$stmt = mysqli_prepare($mycon, $sql);
mysqli_stmt_bind_param($stmt, "i", $guest_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Get profile picture path
$profile_pic = !empty($user['profile_picture']) ? '../uploads/profile_pictures/' . $user['profile_picture'] : '../assets/default_profile.png';

// Fetch wallet transactions for the user
$transactions = [];
$trans_sql = "SELECT * FROM wallet_transactions WHERE guest_id = ? ORDER BY created_at DESC";
$trans_stmt = mysqli_prepare($mycon, $trans_sql);
mysqli_stmt_bind_param($trans_stmt, "i", $guest_id);
mysqli_stmt_execute($trans_stmt);
$trans_result = mysqli_stmt_get_result($trans_stmt);
while ($row = mysqli_fetch_assoc($trans_result)) {
    $transactions[] = $row;
}
mysqli_stmt_close($trans_stmt);

// Fetch guest payment accounts and balances
$accounts = [];
$acc_stmt = mysqli_prepare($mycon, "SELECT account_type, account_number, account_email, balance FROM guest_payment_accounts WHERE guest_id = ?");
mysqli_stmt_bind_param($acc_stmt, "i", $guest_id);
mysqli_stmt_execute($acc_stmt);
$acc_result = mysqli_stmt_get_result($acc_stmt);
while ($row = $acc_result->fetch_assoc()) {
    $accounts[$row['account_type']] = $row;
}
$acc_stmt->close();

// Calculate total wallet balance for display
$total_wallet_balance = 0;
foreach ($accounts as $acc) {
    if ($acc['account_type'] === 'wallet') {
        $total_wallet_balance = floatval($acc['balance']);
        break; // Assuming only one wallet account per guest
    }
}

// Check for active reservations (not directly used on this page, but keeping for completeness if needed elsewhere)
$active_res_sql = "SELECT COUNT(*) FROM tbl_reservation WHERE guest_id = ? AND status IN ('pending','approved')";
$stmt = $mycon->prepare($active_res_sql);
$stmt->bind_param('i', $guest_id);
$stmt->execute();
$stmt->bind_result($active_res_count);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #FF8C00;
            --background: #11101d;
            --input-bg: rgb(0, 0, 0);
            --text-light: #ffffff;
            --text-muted: rgb(255, 255, 255);
            --input-disabled-color: #ffa533;
        }

        body {
            background: var(--background);
            font-family: 'Poppins', sans-serif;
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .card {
            background: #1f1d2e;
            border: none;
            color: var(--text-light);
            border-radius: 12px;
            padding: 2rem;
        }

        .form-control {
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }

        .form-control:focus {
            background: var(--input-bg);
            color: var(--text-light);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(255, 140, 0, 0.25);
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: #e67c00;
            border-color: #e67c00;
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            margin-bottom: 1rem;
        }

        .profile-picture-container {
            position: relative;
            display: inline-block;
        }

        .profile-picture-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .profile-picture-upload:hover {
            background: #e67c00;
        }

        .profile-picture-upload input {
            display: none;
        }

        /* Toast Styles */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }

        .toast {
            background: #1f1d2e;
            color: var(--text-light);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .toast-header {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .toast-success {
            border-left: 4px solid #28a745;
        }

        .toast-error {
            border-left: 4px solid #dc3545;
        }

        .form-control:disabled, textarea.form-control:disabled {
            background: var(--input-bg);
            color: var(--input-disabled-color) !important;
            opacity: 1;
        }

        body.light-mode {
            background: #f8f9fa !important;
            color: #23234a !important;
        }
        body.light-mode .container, body.light-mode .card {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode .form-label, body.light-mode label {
            color: #23234a !important;
        }
        body.light-mode input, body.light-mode select, body.light-mode textarea {
            background: #fff !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode input:focus, body.light-mode select:focus, body.light-mode textarea:focus {
            border-color: #ff8c00 !important;
            box-shadow: 0 0 0 0.12rem rgba(255,140,0,0.13);
        }
        body.light-mode .btn-primary, body.light-mode .btn-success, body.light-mode .btn-info {
            background: linear-gradient(90deg, #ff8c00, #ffa533) !important;
            color: #fff !important;
            border: none !important;
        }
        body.light-mode .btn-outline-secondary {
            border-color: #ff8c00 !important;
            color: #ff8c00 !important;
        }
        body.light-mode .btn-outline-secondary:hover {
            background: #ff8c00 !important;
            color: #fff !important;
        }
        body.light-mode .alert-info {
            background: #ffe5b4 !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .alert-danger {
            background: #fff0e1 !important;
            color: #c0392b !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .modal-content {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode .modal-header, body.light-mode .modal-footer {
            background: #f7f7fa !important;
            color: #23234a !important;
        }
        /* End light mode overrides */
    </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
    <?php include '../components/user_navigation.php'; ?>
    
    <!-- Toast Container -->
    <div class="toast-container">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="toast toast-success" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000" id="successToast">
            <div class="toast-header">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="toast toast-error" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="toast-header">
                <i class="bi bi-exclamation-circle-fill text-danger me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        </div>
        <?php endif; ?>
        <!-- Toast for topup success via URL param -->
        <div class="toast toast-success" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000" id="topupToast" style="display:none;">
            <div class="toast-header">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                Wallet topped up successfully!
            </div>
        </div>
    </div>

    <div class="container mt-5 mb-5 mx-auto">
        <div class="card p-4 shadow-lg rounded-3" style="max-width: 900px; width: 100%;">
            <h4 class="mb-3 text-warning">Profile Information</h4>
            <div class="text-center mb-4">
                <div class="profile-picture-container">
                    <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" class="profile-picture">
                </div>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Middle Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['middle_name']); ?>" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['user_email']); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['phone_number']); ?>" disabled>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['address']); ?>" disabled>
                </div>
            </div>
                    </div>

        <div class="card p-4 shadow-lg rounded-3 mt-4" style="max-width: 900px; width: 100%;">
            <h4 class="mb-3 text-warning">Wallet Information</h4>
            <div class="d-flex align-items-center justify-content-between mb-4 p-3 rounded" style="background:#23234a;">
                            <div>
                                <div><strong>Balance:</strong> <span class="text-success">₱<?php echo number_format($total_wallet_balance, 2); ?></span></div>
                            </div>
                            <div>
                                <?php
                    $has_payment_method = false;
                    foreach ($accounts as $acc) {
                        if ($acc['account_type'] !== 'wallet') { // Check for non-wallet payment methods
                            $has_payment_method = true;
                            break;
                        }
                    }
                                ?>
                    <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#topupModal" <?php echo !$has_payment_method ? 'disabled' : ''; ?>>
                                    <i class="bi bi-plus-circle"></i> Top Up
                                </button>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#walletHistoryModal">
                                    <i class="bi bi-clock-history"></i> View History
                                </button>
                            </div>
                        </div>
                        <?php if (!$has_payment_method): ?>
                            <div class="alert alert-warning text-center mb-4">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Please add at least one payment method (GCash, Bank, PayPal, or Credit Card) in your <a href="settings.php" class="alert-link">settings</a> to enable wallet top-up.
                </div>
            <?php endif; ?>
        </div>

        <!-- Linked Payment Accounts (Read-only Display) -->
        <div class="card p-4 shadow-lg rounded-3 mt-4" style="max-width: 900px; width: 100%;">
            <h4 class="mb-3 text-warning">Linked Payment Accounts</h4>
            <?php if (!empty($accounts)): ?>
                <div class="list-group list-group-flush mb-4">
                    <?php foreach ($accounts as $type => $account): ?>
                        <?php if ($type !== 'wallet'): // Exclude wallet from this list ?>
                            <div class="list-group-item bg-transparent text-light border-secondary mb-2 rounded" style="background-color: #23234a !important;">
                                <strong class="text-info"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $account['account_type']))); ?>:</strong>
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
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    To update or manage your payment accounts, please go to your <a href="settings.php" class="alert-link">settings page</a>.
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    No external payment accounts linked yet.
                    <p class="mt-2 mb-0">Please visit your <a href="settings.php" class="alert-link">settings page</a> to add or manage your payment accounts.</p>
                </div>
            <?php endif; ?>
        </div>

                        <!-- Top Up Modal -->
                        <div class="modal fade" id="topupModal" tabindex="-1" aria-labelledby="topupModalLabel" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content bg-dark text-light">
                              <div class="modal-header border-0">
                                <h5 class="modal-title text-warning" id="topupModalLabel"><i class="bi bi-wallet2 me-2"></i>Top Up Wallet</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <form action="process_topup.php" method="POST">
                                <div class="modal-body">
                                  <div class="mb-3 text-center" id="referenceNumberDisplay" style="display:none;">
                                    <label class="form-label text-light mb-1" for="referenceNumberText">Reference Number</label>
                                    <input type="text" class="form-control-plaintext text-info fs-5 fw-semibold text-center" id="referenceNumberText" readonly style="background:transparent; border:none; outline:none; box-shadow:none;">
                                  </div>
                                  <div class="mb-3">
                                    <label for="topupAmount" class="form-label">Amount</label>
                                    <input type="number" name="topup_amount" id="topupAmount" min="1" step="0.01" class="form-control" placeholder="Enter amount" required>
                                  </div>
                                  <div class="mb-3">
                                    <label for="paymentMethod" class="form-label">Select Payment Method</label>
                                    <select name="payment_method" id="paymentMethod" class="form-select" required onchange="updateReferenceNumber()">
                                      <option value="">Select payment method</option>
                                    <?php
                                    foreach ($accounts as $type => $account) {
                                        if ($type !== 'wallet') { // Exclude wallet as a payment method for top-up
                                            echo '<option value="' . htmlspecialchars(ucfirst(str_replace('_', ' ', $type))) . '">' . htmlspecialchars(ucfirst(str_replace('_', ' ', $type))) . '</option>';
                                        }
                                    }
                                    ?>
                                    </select>
                                  </div>
                                </div>
                                <div class="modal-footer border-0">
                                  <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle"></i> Top Up</button>
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
                        <!-- End Top Up Modal -->

                        <!-- Wallet History Modal -->
                        <div class="modal fade" id="walletHistoryModal" tabindex="-1" aria-labelledby="walletHistoryModalLabel" aria-hidden="true">
                          <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content bg-dark text-light">
                              <div class="modal-header border-0">
                                <h5 class="modal-title text-info" id="walletHistoryModalLabel"><i class="bi bi-clock-history me-2"></i>Wallet Transaction History</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                <?php if (count($transactions) > 0): ?>
                                <div class="table-responsive">
                                  <table class="table table-dark table-striped table-bordered align-middle mb-0">
                                    <thead>
                                      <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Amount (₱)</th>
                                        <th>Payment Method</th>
                                        <th>Reference Number</th>
                                        <th>Description</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php foreach ($transactions as $t): ?>
                                      <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($t['created_at'])); ?></td>
                                        <td><?php echo ucfirst($t['type']); ?></td>
                                        <td class="text-<?php echo $t['type'] === 'topup' || $t['type'] === 'refund' ? 'success' : 'danger'; ?>">
                                          <?php echo number_format($t['amount'], 2); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($t['payment_method'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($t['reference_number'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($t['description']); ?></td>
                                      </tr>
                                      <?php endforeach; ?>
                                    </tbody>
                                  </table>
                                </div>
                                <?php else: ?>
                                <div class="text-muted">No wallet transactions yet.</div>
                                <?php endif; ?>
                              </div>
                              <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                              </div>
                            </div>
                          </div>
                        </div>
                        <!-- End Wallet History Modal -->
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        // Preview image for profile picture (kept as display is here)
        function previewImage(input) {
            // This function is for the profile picture preview in settings.php, not needed here for display only
            // If you later decide to have editable profile picture here, uncomment its logic
        }

        // Generate a random 16-character hex string (8 bytes)
        function generateReferenceNumber() {
            return Math.random().toString(16).substr(2, 8).toUpperCase() + Math.random().toString(16).substr(2, 8).toUpperCase();
        }

        function updateReferenceNumber() {
            var select = document.getElementById('paymentMethod');
            var display = document.getElementById('referenceNumberDisplay');
            var text = document.getElementById('referenceNumberText');
            if (select.value) {
                text.value = generateReferenceNumber();
                display.style.display = '';
            } else {
                display.style.display = 'none';
                text.value = '';
            }
        }

        // Show toast messages
        $(document).ready(function() {
            // Check if there's a success or error message from PHP session
            <?php if (isset($_SESSION['success'])): ?>
                showToast("<?php echo $_SESSION['success']; ?>", "success");
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                showToast("<?php echo $_SESSION['error']; ?>", "danger");
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            // Function to show custom toast
            window.showToast = function(message, type) {
                var toastHtml = `
                    <div class="toast toast-${type}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                        <div class="toast-header">
                            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'} text-${type === 'success' ? 'success' : 'danger'} me-2"></i>
                            <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            ${message}
                        </div>
                    </div>
                `;
                $('.toast-container').append(toastHtml);
                var toastEl = $('.toast-container .toast:last');
                var toast = new bootstrap.Toast(toastEl[0]);
                toast.show();
                toastEl.on('hidden.bs.toast', function () {
                    $(this).remove();
                });
            };

            // Theme switcher
            $('#themeSelect').on('change', function() {
                var theme = $(this).val();
                $('body').removeClass('light-mode').removeClass('dark-mode').addClass(theme === 'light' ? 'light-mode' : '');

                $.ajax({
                    url: 'process_update_profile.php',
                    type: 'POST',
                    data: { theme_preference: theme },
                    success: function(response) {
                        // Handle success if needed, e.g., show a small success message
                    },
                    error: function(xhr, status, error) {
                        // Handle error
                        console.error('Theme update failed:', error);
                    }
                });
            });
            
            // Set initial theme
            var initialTheme = "<?php echo $theme_preference; ?>";
            if (initialTheme === 'light') {
                $('body').addClass('light-mode');
            }
            $('#themeSelect').val(initialTheme);

        // Show toast if ?topup=success is in the URL
            var url = new URL(window.location.href);
            if (url.searchParams.get('topup') === 'success' && !document.getElementById('successToast')) {
                var topupToast = document.getElementById('topupToast');
                if (topupToast) {
                    topupToast.style.display = '';
                    var toast = new bootstrap.Toast(topupToast, { autohide: true, delay: 3000 });
                    toast.show();
                }
            }
        });
    </script>
</body>
</html> 