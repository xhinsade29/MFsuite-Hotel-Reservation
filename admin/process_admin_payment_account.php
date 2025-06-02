<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}
include '../functions/db_connect.php';
$admin_id = $_SESSION['admin_id'];
$action = $_REQUEST['action'] ?? '';

function sanitize($str) {
    return htmlspecialchars(trim($str));
}

if ($action === 'list') {
    $sql = "SELECT * FROM admin_payment_accounts WHERE admin_id = ? ORDER BY account_type";
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo '<table class="table table-bordered"><thead><tr><th>Type</th><th>Number/Email</th><th>Balance</th><th>Actions</th></tr></thead><tbody>';
    while ($row = $result->fetch_assoc()) {
        echo '<tr data-id="' . $row['account_id'] . '" data-type="' . $row['account_type'] . '" data-number="' . htmlspecialchars($row['account_number']) . '" data-email="' . htmlspecialchars($row['account_email']) . '">';
        echo '<td>' . ucfirst($row['account_type']) . '</td>';
        echo '<td>';
        if ($row['account_type'] === 'paypal') {
            echo htmlspecialchars($row['account_email']);
        } else {
            echo htmlspecialchars($row['account_number']);
        }
        echo '</td>';
        echo '<td>₱' . number_format($row['balance'], 2) . '</td>';
        echo '<td>';
        echo '<button class="btn btn-sm btn-warning edit-account-btn me-1"><i class="bi bi-pencil"></i> Edit</button>';
        echo '<button class="btn btn-sm btn-danger delete-account-btn"><i class="bi bi-trash"></i> Delete</button>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    $stmt->close();
    exit;
}

if ($action === 'add') {
    $type = $_POST['account_type'] ?? '';
    $number = sanitize($_POST['account_number'] ?? '');
    $email = sanitize($_POST['account_email'] ?? '');
    if (!in_array($type, ['gcash','bank','paypal','credit_card'])) exit('Invalid type');
    if ($type === 'paypal' && !$email) exit('Email required');
    if ($type !== 'paypal' && !$number) exit('Number required');
    $sql = "INSERT INTO admin_payment_accounts (admin_id, account_type, account_number, account_email, balance) VALUES (?, ?, ?, ?, 0)";
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param('isss', $admin_id, $type, $number, $email);
    $stmt->execute();
    $stmt->close();
    exit('ok');
}

if ($action === 'edit') {
    $id = intval($_POST['account_id'] ?? 0);
    $type = $_POST['account_type'] ?? '';
    $number = sanitize($_POST['account_number'] ?? '');
    $email = sanitize($_POST['account_email'] ?? '');
    if (!in_array($type, ['gcash','bank','paypal','credit_card'])) exit('Invalid type');
    if ($type === 'paypal' && !$email) exit('Email required');
    if ($type !== 'paypal' && !$number) exit('Number required');
    $sql = "UPDATE admin_payment_accounts SET account_number=?, account_email=? WHERE account_id=? AND admin_id=?";
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param('ssii', $number, $email, $id, $admin_id);
    $stmt->execute();
    $stmt->close();
    exit('ok');
}

if ($action === 'delete') {
    $id = intval($_POST['account_id'] ?? 0);
    $sql = "DELETE FROM admin_payment_accounts WHERE account_id=? AND admin_id=?";
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param('ii', $id, $admin_id);
    $stmt->execute();
    $stmt->close();
    exit('ok');
}

echo 'Invalid action'; 