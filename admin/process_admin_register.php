<?php
session_start();
require_once '../functions/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $role = trim($_POST['role'] ?? 'admin');

    // Basic validation
    if (!$username || !$password || !$email || !$full_name) {
        $_SESSION['msg'] = 'All fields are required.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: admin_register.php');
        exit();
    }

    // Check for duplicate email or username
    $stmt = $mycon->prepare("SELECT 1 FROM tbl_admin WHERE email = ? OR username = ? LIMIT 1");
    $stmt->bind_param('ss', $email, $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['msg'] = 'Email or username already exists.';
        $_SESSION['msg_type'] = 'danger';
        header('Location: admin_register.php');
        exit();
    }
    $stmt->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Generate unique wallet_id
    function generate_wallet_id() {
        return bin2hex(random_bytes(16));
    }
    $wallet_id = generate_wallet_id();
    $exists = mysqli_query($mycon, "SELECT 1 FROM tbl_guest WHERE wallet_id = '$wallet_id' UNION SELECT 1 FROM tbl_admin WHERE wallet_id = '$wallet_id'");
    while (mysqli_num_rows($exists) > 0) {
        $wallet_id = generate_wallet_id();
        $exists = mysqli_query($mycon, "SELECT 1 FROM tbl_guest WHERE wallet_id = '$wallet_id' UNION SELECT 1 FROM tbl_admin WHERE wallet_id = '$wallet_id'");
    }

    // Insert new admin
    $stmt = $mycon->prepare("INSERT INTO tbl_admin (username, password, email, full_name, role, wallet_id, date_created) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssssss', $username, $hashed_password, $email, $full_name, $role, $wallet_id);
    if ($stmt->execute()) {
        $_SESSION['msg'] = 'Admin registered successfully!';
        $_SESSION['msg_type'] = 'success';
        header('Location: admin_login.php');
        exit();
    } else {
        $_SESSION['msg'] = 'Registration failed: ' . $stmt->error;
        $_SESSION['msg_type'] = 'danger';
        header('Location: admin_register.php');
        exit();
    }
} else {
    header('Location: admin_register.php');
    exit();
} 