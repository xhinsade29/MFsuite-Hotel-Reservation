<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../functions/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['user_email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Email and password are required.';
        header('Location: ../pages/login.php');
        exit();
    }
    $sql = "SELECT guest_id, password, first_name, last_name, user_email, phone_number FROM tbl_guest WHERE user_email = ? AND is_deleted = 0 LIMIT 1";
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($guest_id, $db_password, $first_name, $last_name, $user_email, $phone_number);
        $stmt->fetch();
        if ($password === $db_password) {
            $_SESSION['guest_id'] = $guest_id;
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['user_email'] = $user_email;
            $_SESSION['phone_number'] = $phone_number;
            // Fetch theme_preference
            $theme_sql = "SELECT theme_preference FROM tbl_guest WHERE guest_id = ? LIMIT 1";
            $theme_stmt = $mycon->prepare($theme_sql);
            $theme_stmt->bind_param('i', $guest_id);
            $theme_stmt->execute();
            $theme_stmt->bind_result($theme_preference);
            $theme_stmt->fetch();
            $_SESSION['theme_preference'] = $theme_preference ?: 'dark';
            $theme_stmt->close();
            $_SESSION['success'] = "Welcome back, " . $first_name . "!";
            header('Location: ../pages/rooms.php');
            exit();
        } else {
            $_SESSION['error'] = 'Invalid email or password.';
        }
    } else {
        $_SESSION['error'] = 'Invalid email or password.';
    }
    $stmt->close();
    header('Location: ../pages/login.php');
    exit();
} else {
    header('Location: ../pages/login.php');
    exit();
}
?> 