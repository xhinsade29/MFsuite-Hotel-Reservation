<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../functions/db_connect.php';

$admin_id = $_SESSION['admin_id'];

// Mark all admin notifications as read
$sql = "UPDATE admin_notifications SET is_read = 1 WHERE admin_id = ?";
$stmt = mysqli_prepare($mycon, $sql);
mysqli_stmt_bind_param($stmt, "i", $admin_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

echo json_encode(['success' => true]);
exit(); 