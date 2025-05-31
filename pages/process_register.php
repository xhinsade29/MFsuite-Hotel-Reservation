<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../functions/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Store form data in session
    $_SESSION['form_data'] = [
        'firstname' => $_POST['firstname'],
        'middlename' => $_POST['middlename'],
        'lastname' => $_POST['lastname'],
        'phone' => $_POST['phone'],
        'email' => $_POST['email'],
        'address' => $_POST['address']
    ];

    // Get form data
    $first_name = mysqli_real_escape_string($mycon, $_POST['firstname']);
    $middle_name = mysqli_real_escape_string($mycon, $_POST['middlename']);
    $last_name = mysqli_real_escape_string($mycon, $_POST['lastname']);
    $phone_number = mysqli_real_escape_string($mycon, $_POST['phone']);
    $user_email = mysqli_real_escape_string($mycon, $_POST['email']);
    $address = mysqli_real_escape_string($mycon, $_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Password validation
    if (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters long";
        header("Location: /pages/register.php");
        exit();
    }

    if (!preg_match("/[A-Z]/", $password)) {
        $_SESSION['error'] = "Password must contain at least one uppercase letter";
        header("Location: /pages/register.php");
        exit();
    }

    if (!preg_match("/[a-z]/", $password)) {
        $_SESSION['error'] = "Password must contain at least one lowercase letter";
        header("Location: /pages/register.php");
        exit();
    }

    if (!preg_match("/[0-9]/", $password)) {
        $_SESSION['error'] = "Password must contain at least one number";
        header("Location: /pages/register.php");
        exit();
    }

    // Validate passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: /pages/register.php");
        exit();
    }

    // Check if email already exists
    $check_email = "SELECT * FROM tbl_guest WHERE user_email = ?";
    $stmt = mysqli_prepare($mycon, $check_email);
    mysqli_stmt_bind_param($stmt, "s", $user_email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already exists";
        header("Location: /pages/register.php");
        exit();
    }

    // Store actual password (NOT RECOMMENDED for security reasons)
    $actual_password = $password;

    // Generate a unique wallet_id
    function generate_wallet_id() {
        return bin2hex(random_bytes(16)); // 32-char hex string
    }
    $wallet_id = generate_wallet_id();
    // Ensure uniqueness (very unlikely to collide, but check)
    $exists = mysqli_query($mycon, "SELECT 1 FROM tbl_guest WHERE wallet_id = '$wallet_id' UNION SELECT 1 FROM tbl_admin WHERE wallet_id = '$wallet_id'");
    while (mysqli_num_rows($exists) > 0) {
        $wallet_id = generate_wallet_id();
        $exists = mysqli_query($mycon, "SELECT 1 FROM tbl_guest WHERE wallet_id = '$wallet_id' UNION SELECT 1 FROM tbl_admin WHERE wallet_id = '$wallet_id'");
    }

    // Insert new guest
    $sql = "INSERT INTO tbl_guest (first_name, middle_name, last_name, phone_number, user_email, password, address, wallet_id, date_created) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($mycon, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssss", $first_name, $middle_name, $last_name, $phone_number, $user_email, $actual_password, $address, $wallet_id);

    if (mysqli_stmt_execute($stmt)) {
        // Set success message
        $_SESSION['success'] = "Registration successful! You may now log in.";
        // Clear form data from session
        unset($_SESSION['form_data']);
        // Redirect to login page
        header("Location: /pages/login.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed: " . mysqli_error($mycon);
        error_log("Registration error: " . mysqli_error($mycon));
        header("Location: /pages/register.php");
        exit();
    }
} else {
    header("Location: /pages/register.php");
    exit();
}
?> 