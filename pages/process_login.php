<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../functions/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($mycon, $_POST['email']);
    $password = $_POST['password'];

    // Get user from database
    $sql = "SELECT * FROM tbl_guest WHERE user_email = ?";
    $stmt = mysqli_prepare($mycon, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result->num_rows == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Check password directly (NOT RECOMMENDED for security reasons)
        if ($password === $user['password']) {
            // Set session variables
            $_SESSION['guest_id'] = $user['guest_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['user_email'] = $user['user_email'];
            $_SESSION['phone_number'] = $user['phone_number'];
            
            $_SESSION['success'] = "Welcome back, " . $user['first_name'] . "!";
            header("Location: ../index.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password";
            header("Location: login.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?> 