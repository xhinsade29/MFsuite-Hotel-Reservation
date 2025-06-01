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
$sql = "SELECT * FROM tbl_guest WHERE guest_id = ?";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - MF Suites Hotel</title>
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

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header text-center">
                        <!-- <img src="../assets/MFsuites_logo.png" alt="Hotel Logo" class="img-fluid" style="max-height: 100px;" /> -->
                        <h3 class="mt-3">User Profile</h3>
                    </div>
                    <div class="card-body">
                        <!-- Wallet Balance and Actions -->
                        <div class="mb-4 p-3 rounded d-flex align-items-center justify-content-between" style="background:#23234a;">
                            <div>
                                <div class="mb-1 text-light" style="font-size:0.98em;">
                                    <i class="bi bi-credit-card-2-front me-1"></i> <strong>Wallet Number:</strong> <span class="text-info"><?php echo htmlspecialchars($user['wallet_id']); ?></span>
                                </div>
                                <h5 class="mb-0 text-warning">
                                    <i class="bi bi-wallet-fill me-2"></i> Wallet Balance:
                                    <span class="fw-bold">₱<?php echo number_format($user['wallet_balance'], 2); ?></span>
                                </h5>
                            </div>
                            <div>
                                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#topupModal">
                                    <i class="bi bi-plus-circle"></i> Top Up
                                </button>
                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#walletHistoryModal">
                                    <i class="bi bi-clock-history"></i> View History
                                </button>
                            </div>
                        </div>
                        <!-- End Wallet Balance and Actions -->

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
                                      <option value="GCash" <?php echo empty($user['gcash_number']) ? 'disabled' : ''; ?>>GCash</option>
                                      <option value="Bank" <?php echo empty($user['bank_account_number']) ? 'disabled' : ''; ?>>Bank</option>
                                      <option value="PayPal" <?php echo empty($user['paypal_email']) ? 'disabled' : ''; ?>>PayPal</option>
                                      <option value="Credit Card" <?php echo empty($user['credit_card_number']) ? 'disabled' : ''; ?>>Credit Card</option>
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

                        <div class="row">
                            <!-- Left: User Info (Read-only) -->
                            <div class="col-md-6 border-end" style="padding-right:2rem;">
                                <h4 class="mb-4 text-warning"><i class="bi bi-person-circle me-2"></i>User Information</h4>
                                <div class="text-center mb-4">
                                    <div class="profile-picture-container">
                                        <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" class="profile-picture" id="profile-preview">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Middle Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['middle_name']); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" disabled>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Phone Number</label>
                                            <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user['phone_number']); ?>" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['user_email']); ?>" disabled>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" rows="2" disabled><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                                <hr class="my-4" style="border-color: rgba(255, 255, 255, 0.1);">
                                <div class="alert alert-info mt-3">To update your profile, payment methods, or password, please go to <a href="settings.php" class="text-warning">Settings</a>.</div>
                            </div>
                            <!-- Right: Payment Details (Read-only) -->
                            <div class="col-md-6" style="padding-left:2rem;">
                                <h4 class="mb-4 text-warning"><i class="bi bi-credit-card me-2"></i>Payment Methods</h4>
                                <div class="mb-3">
                                    <label class="form-label">Bank Account Number</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['bank_account_number'] ?? ''); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">PayPal Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['paypal_email'] ?? ''); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Credit Card Number</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['credit_card_number'] ?? ''); ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">GCash Number</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['gcash_number'] ?? ''); ?>" disabled>
                                </div>
                                <div class="alert alert-info mt-3">To update your payment methods, please go to <a href="settings.php" class="text-warning">Settings</a>.</div>
                            </div>
                        </div>
                        <div class="d-grid gap-2 mt-4">
                            <a href="../index.php" class="btn btn-outline-secondary">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = event.currentTarget.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-preview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function generateReferenceNumber() {
            // Generate a random 16-character hex string (8 bytes)
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

        // Initialize all toasts
        document.addEventListener('DOMContentLoaded', function() {
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            var toastList = toastElList.map(function(toastEl) {
                return new bootstrap.Toast(toastEl, {
                    autohide: true,
                    delay: 3000
                });
            });
            
            toastList.forEach(toast => {
                toast.show();
            });
        });

        // Show toast if ?topup=success is in the URL
        document.addEventListener('DOMContentLoaded', function() {
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

        function normalize_service_key($name) {
            // Remove all non-alphanumeric, then remove spaces, then lowercase
            return strtolower(str_replace(' ', '', trim(preg_replace('/[^a-zA-Z0-9 ]/', '', $name))));
        }
        $service_icons = [
            'spa' => 'bi-spa',
            'swimmingpool' => 'bi-water',
            'restaurant' => 'bi-cup-straw',
            'airportshuttle' => 'bi-bus-front',
            'businesscenter' => 'bi-briefcase',
            'concierge' => 'bi-person-badge',
            'fitnesscenter' => 'bi-barbell',
            'luggagestorage' => 'bi-suitcase',
            'laundrydrycleaning' => 'bi-droplet',
            'roomservice' => 'bi-door-open',
            'housekeeping' => 'bi-bucket',
            'conferenceroom' => 'bi-easel',
            'wifi' => 'bi-wifi'
        ];
    </script>
</body>
</html> 