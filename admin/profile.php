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
$sql = "SELECT full_name, email, username, role, wallet_id, wallet_balance, profile_picture, last_login FROM tbl_admin WHERE admin_id = ? LIMIT 1";
$stmt = $mycon->prepare($sql);
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$stmt->bind_result($full_name, $email, $username, $role, $wallet_id, $wallet_balance, $profile_picture, $last_login);
if ($stmt->fetch()) {
    $admin['full_name'] = $full_name;
    $admin['email'] = $email;
    $admin['username'] = $username;
    $admin['role'] = $role;
    $admin['wallet_id'] = $wallet_id;
    $admin['wallet_balance'] = $wallet_balance;
    $admin['profile_picture'] = $profile_picture;
    $admin['last_login'] = $last_login;
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
        body { background: linear-gradient(120deg, #23234a 0%, #1e1e2f 100%); color: #fff; font-family: 'Poppins', sans-serif; }
        .profile-container { margin-left: 240px; padding: 40px 24px 24px 24px; max-width: 1400px; }
        .profile-title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; letter-spacing: 1px; }
        .profile-card { background: rgba(35,35,74,0.98); border-radius: 22px; box-shadow: 0 8px 32px rgba(0,0,0,0.22); padding: 36px 32px; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid #FF8C00; margin-bottom: 18px; box-shadow: 0 0 0 6px rgba(255,140,0,0.18), 0 2px 16px rgba(0,0,0,0.18); transition: box-shadow 0.3s; }
        .profile-avatar:hover { box-shadow: 0 0 0 10px #ffa53355, 0 2px 24px #ff8c0033; }
        .form-label { color: #ffa533; font-weight: 500; }
        .btn-primary { background: linear-gradient(90deg, #FF8C00, #ffa533); border: none; font-weight: 600; letter-spacing: 0.5px; }
        .btn-primary:hover { background: linear-gradient(90deg, #e67c00, #ffa533); }
        .section-header { font-size: 1.15rem; font-weight: 600; color: #ffa533; margin-bottom: 18px; letter-spacing: 0.5px; }
        .left-card { background: linear-gradient(135deg, #23234a 80%, #ffa53322 100%); border-radius: 18px; box-shadow: 0 4px 18px rgba(255,140,0,0.08); padding: 28px 18px 24px 18px; }
        .wallet-box { background: #18182f; border-radius: 12px; padding: 18px 16px; margin-top: 24px; box-shadow: 0 2px 12px rgba(255,140,0,0.07); }
        .wallet-label { color: #ffa533; font-weight: 500; }
        .table-dark th, .table-dark td { background: transparent !important; }
        .table-dark { border-radius: 10px; overflow: hidden; }
        .profile-form .form-control { background: #23234a; color: #fff; border: 1.5px solid #ffa53333; border-radius: 8px; }
        .profile-form .form-control:focus { border-color: #ffa533; box-shadow: 0 0 0 0.12rem #ffa53344; }
        @media (max-width: 991px) {
            .profile-container { margin-left: 0; padding: 18px 4px; }
            .profile-card { padding: 18px 8px; }
        }
        .modern-table-wrapper {
            background: rgba(255,255,255,0.03);
            border-radius: 20px;
            box-shadow: 0 2px 16px rgba(255,140,0,0.07);
            padding: 24px 16px 16px 16px;
            margin-bottom: 0;
        }
        .modern-table {
            border-radius: 18px !important;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(255,140,0,0.06);
            font-size: 1.08em;
        }
        .modern-table th, .modern-table td {
            border: none !important;
            background: transparent !important;
            vertical-align: middle;
            font-size: 1.07em;
        }
        .modern-table thead th {
            background: rgba(255,140,0,0.08) !important;
            color: #ffa533;
            font-weight: 600;
            font-size: 1.12em;
            letter-spacing: 0.5px;
        }
        .modern-table tbody tr {
            transition: background 0.2s;
        }
        .modern-table tbody tr:hover {
            background: rgba(255,140,0,0.07) !important;
        }
        .big-right-card {
            min-width: 370px;
            padding: 36px 28px 36px 28px !important;
        }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="profile-container">
    <div class="profile-title">Admin Profile</div>
    <div class="profile-card">
        <div class="row g-4 align-items-stretch">
            <!-- Left: Admin Info -->
            <div class="col-md-7 border-end">
                <div class="text-center mb-4">
                    <img src="<?php echo $admin['profile_picture'] ? '../uploads/profile_pictures/' . htmlspecialchars($admin['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode($admin['full_name'] ?: $admin['username']) . '&background=FF8C00&color=fff'; ?>" class="profile-avatar shadow" alt="Admin Avatar">
                    <h4 class="fw-semibold mb-0 mt-2"><?php echo htmlspecialchars($admin['full_name']); ?></h4>
                    <span class="text-warning"><?php echo ucfirst($admin['role']); ?></span>
                </div>
                <?php if ($success): ?>
                    <div class="alert alert-success text-center py-2 mb-3"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="POST" action="update_profile.php" enctype="multipart/form-data" class="profile-form">
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
                    <div class="mb-3">
                        <label class="form-label">Last Login</label>
                        <input type="text" class="form-control" value="<?php echo $admin['last_login'] ? date('M d, Y h:i A', strtotime($admin['last_login'])) : 'Never'; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" name="profile_picture" accept=".jpg,.jpeg,.png">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                </form>
            </div>
            <!-- Right: Wallet Info & Payment Accounts -->
            <div class="col-md-5">
                <div class="left-card h-100 d-flex flex-column justify-content-between big-right-card">
                    <div>
                        <div class="wallet-box mb-4">
                            <div class="wallet-label mb-1"><i class="bi bi-wallet2 me-2"></i>Wallet Info</div>
                            <div class="mb-1"><strong>ID:</strong> <?php echo htmlspecialchars($admin['wallet_id']); ?></div>
                            <div><strong>Balance:</strong> <span class="text-success">₱<?php echo number_format($admin['wallet_balance'],2); ?></span></div>
                        </div>
                        <div class="section-header mb-2"><i class="bi bi-credit-card me-2"></i>Payment Accounts</div>
                        <?php
                        $sql = "SELECT account_type, account_number, account_email, balance FROM admin_payment_accounts WHERE admin_id = ?";
                        $stmt = $mycon->prepare($sql);
                        $stmt->bind_param('i', $admin_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        echo '<div class="modern-table-wrapper">';
                        echo '<table class="table table-dark table-striped table-bordered modern-table mb-0">';
                        echo '<thead><tr><th>Type</th><th>Number / Email</th><th>Balance</th></tr></thead><tbody>';
                        while ($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . ucfirst($row['account_type']) . '</td>';
                            echo '<td>';
                            if ($row['account_type'] === 'paypal') {
                                echo '<i class="bi bi-paypal text-info me-1"></i> ' . htmlspecialchars($row['account_email']);
                            } else if ($row['account_type'] === 'gcash') {
                                echo '<i class="bi bi-phone text-primary me-1"></i> ' . htmlspecialchars($row['account_number']);
                            } else if ($row['account_type'] === 'bank') {
                                echo '<i class="bi bi-bank text-success me-1"></i> ' . htmlspecialchars($row['account_number']);
                            } else if ($row['account_type'] === 'credit_card') {
                                echo '<i class="bi bi-credit-card-2-front text-warning me-1"></i> ' . htmlspecialchars($row['account_number']);
                            } else {
                                echo htmlspecialchars($row['account_number']);
                            }
                            echo '</td>';
                            echo '<td>₱' . number_format($row['balance'], 2) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        echo '</div>';
                        $stmt->close();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html> 