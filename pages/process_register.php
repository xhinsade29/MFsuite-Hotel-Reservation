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
        header("Location: register.php");
        exit();
    }

    if (!preg_match("/[A-Z]/", $password)) {
        $_SESSION['error'] = "Password must contain at least one uppercase letter";
        header("Location: register.php");
        exit();
    }

    if (!preg_match("/[a-z]/", $password)) {
        $_SESSION['error'] = "Password must contain at least one lowercase letter";
        header("Location: register.php");
        exit();
    }

    if (!preg_match("/[0-9]/", $password)) {
        $_SESSION['error'] = "Password must contain at least one number";
        header("Location: register.php");
        exit();
    }

    // Validate passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: register.php");
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
        header("Location: register.php");
        exit();
    }

    // Store actual password (NOT RECOMMENDED for security reasons)
    $actual_password = $password;

    // Insert new guest
    $sql = "INSERT INTO tbl_guest (first_name, middle_name, last_name, phone_number, user_email, password, address, date_created) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($mycon, $sql);
    mysqli_stmt_bind_param($stmt, "sssssss", $first_name, $middle_name, $last_name, $phone_number, $user_email, $actual_password, $address);

    if (mysqli_stmt_execute($stmt)) {
        $new_guest_id = mysqli_insert_id($mycon);

        // Create default 'wallet' entry in guest_payment_accounts for the new guest
        $insert_wallet_sql = "INSERT INTO guest_payment_accounts (guest_id, account_type, balance) VALUES (?, ?, ?)";
        $stmt_wallet = mysqli_prepare($mycon, $insert_wallet_sql);
        $initial_balance = 0.00;
        $account_type = 'wallet';
        mysqli_stmt_bind_param($stmt_wallet, "isd", $new_guest_id, $account_type, $initial_balance);
        mysqli_stmt_execute($stmt_wallet);
        mysqli_stmt_close($stmt_wallet);

        // Set success message
        $_SESSION['success'] = "Registration successful! You may now log in.";
        // Clear form data from session
        unset($_SESSION['form_data']);
        // Notify admin of new user registration
        $admin_id = 1; // Notify the first admin (or loop through all admins if needed)
        $notif_type = 'profile';
        $notif_message = 'A new user has registered: ' . $first_name . ' ' . $last_name . ' (' . $user_email . ')';
        $notif_sql = "INSERT INTO admin_notifications (admin_id, type, message, created_at) VALUES (?, ?, ?, NOW())";
        $notif_stmt = mysqli_prepare($mycon, $notif_sql);
        mysqli_stmt_bind_param($notif_stmt, "iss", $admin_id, $notif_type, $notif_message);
        mysqli_stmt_execute($notif_stmt);
        mysqli_stmt_close($notif_stmt);
        // Redirect to login page
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed: " . mysqli_error($mycon);
        error_log("Registration error: " . mysqli_error($mycon));
        header("Location: register.php");
        exit();
    }
} else {
    header("Location: register.php");
    exit();
}
?> 