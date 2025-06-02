<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
$admin_id = $_SESSION['admin_id'];
$transactions = [];
$sql = "SELECT * FROM wallet_transactions WHERE admin_id = ? ORDER BY created_at DESC";
$stmt = $mycon->prepare($sql);
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Wallet Transactions - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        .container { margin-left: 240px; padding: 40px 24px 24px 24px; max-width: 900px; }
        .title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; }
        .table thead { background: #1a1a2e; color: #ffa533; }
        .table tbody tr { color: #fff; }
        .table tbody tr:nth-child(even) { background: #23234a; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="container">
    <div class="title">Wallet Transaction History</div>
    <div class="card bg-dark text-light rounded-4 shadow mb-4">
        <div class="card-body">
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
                            <td class="text-<?php echo $t['type'] === 'credit' ? 'success' : 'danger'; ?>">
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
    </div>
</div>
</body>
</html> 