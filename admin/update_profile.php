<?php
session_start();
include '../functions/db_connect.php';
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
$admin_id = $_SESSION['admin_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($full_name && $email && $username) {
        if ($password) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mycon->prepare("UPDATE tbl_admin SET full_name=?, email=?, username=?, password=? WHERE admin_id=?");
            $stmt->bind_param('ssssi', $full_name, $email, $username, $hashed, $admin_id);
        } else {
            $stmt = $mycon->prepare("UPDATE tbl_admin SET full_name=?, email=?, username=? WHERE admin_id=?");
            $stmt->bind_param('sssi', $full_name, $email, $username, $admin_id);
        }
        $stmt->execute();
        $stmt->close();
        // Update session variables
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        header('Location: profile.php?msg=' . urlencode('Profile updated successfully.'));
        exit();
    }
}
header('Location: profile.php?msg=' . urlencode('Failed to update profile. Please fill all required fields.'));
exit(); 