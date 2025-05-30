<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($email && $password) {
        $stmt = $mycon->prepare("SELECT admin_id, username, password, role, full_name, email FROM tbl_admin WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($admin_id, $username, $db_password, $role, $full_name, $db_email);
            $stmt->fetch();
            $valid = false;
            $debug = isset($_GET['debug']) && $_GET['debug'] == 1;
            if ($debug) {
                echo "DB password: $db_password<br>Input: $password<br>password_verify: ".(password_verify($password, $db_password) ? 'true' : 'false')."<br>Plain match: ".($password === $db_password ? 'true' : 'false')."<br>";
            }
            if (password_verify($password, $db_password)) {
                $valid = true;
            } elseif ($password === $db_password) {
                $valid = true;
            }
            if ($valid) {
                $_SESSION['admin_id'] = $admin_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $db_email;
                $_SESSION['msg'] = 'Login successful! Welcome, ' . htmlspecialchars($full_name ?: $username) . '.';
                $_SESSION['msg_type'] = 'success';
                if ($debug) { echo "Login success!"; exit; }
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid email or password.';
                $_SESSION['msg'] = $error;
                $_SESSION['msg_type'] = 'danger';
                if ($debug) { echo $error; exit; }
            }
        } else {
            $error = 'Invalid email or password.';
            $_SESSION['msg'] = $error;
            $_SESSION['msg_type'] = 'danger';
            if ($debug) { echo $error; exit; }
        }
        $stmt->close();
    } else {
        $error = 'Please enter both email and password.';
        $_SESSION['msg'] = $error;
        $_SESSION['msg_type'] = 'danger';
    }
    header('Location: admin_login.php');
    exit();
}
header('Location: admin_login.php');
exit(); 