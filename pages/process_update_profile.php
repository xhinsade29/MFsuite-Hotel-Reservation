<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['guest_id'])) {
    header("Location: /pages/login.php");
    exit();
}

include('../functions/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $guest_id = $_SESSION['guest_id'];
    
    // Get form data
    $first_name = mysqli_real_escape_string($mycon, $_POST['firstname']);
    $middle_name = mysqli_real_escape_string($mycon, $_POST['middlename']);
    $last_name = mysqli_real_escape_string($mycon, $_POST['lastname']);
    $phone_number = mysqli_real_escape_string($mycon, $_POST['phone']);
    $user_email = mysqli_real_escape_string($mycon, $_POST['email']);
    $address = mysqli_real_escape_string($mycon, $_POST['address']);

    // Check if email is already taken by another user
    $check_email = "SELECT * FROM tbl_guest WHERE user_email = ? AND guest_id != ?";
    $stmt = mysqli_prepare($mycon, $check_email);
    mysqli_stmt_bind_param($stmt, "si", $user_email, $guest_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Email already exists";
        header("Location: /pages/update_profile.php");
        exit();
    }

    // Handle profile picture upload
    $profile_picture = '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);

        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = 'profile_' . $guest_id . '_' . time() . '.' . $filetype;
            $upload_path = '../uploads/profile_pictures/' . $new_filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                $profile_picture = $new_filename;
            }
        }
    }

    // Start building the update query
    $update_fields = [];
    $params = [];
    $types = "";

    // Add basic fields
    $update_fields[] = "first_name = ?";
    $update_fields[] = "middle_name = ?";
    $update_fields[] = "last_name = ?";
    $update_fields[] = "phone_number = ?";
    $update_fields[] = "user_email = ?";
    $update_fields[] = "address = ?";
    
    $params[] = $first_name;
    $params[] = $middle_name;
    $params[] = $last_name;
    $params[] = $phone_number;
    $params[] = $user_email;
    $params[] = $address;
    $types .= "ssssss";

    // Add profile picture if uploaded
    if ($profile_picture) {
        $update_fields[] = "profile_picture = ?";
        $params[] = $profile_picture;
        $types .= "s";
    }

    // Handle password update if provided
    if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
        // Get current password
        $sql = "SELECT password FROM tbl_guest WHERE guest_id = ?";
        $stmt = mysqli_prepare($mycon, $sql);
        mysqli_stmt_bind_param($stmt, "i", $guest_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);

        // Verify current password
        if ($_POST['current_password'] === $user['password']) {
            // Validate new password
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (strlen($new_password) < 8) {
                $_SESSION['error'] = "New password must be at least 8 characters long";
                header("Location: /pages/update_profile.php");
                exit();
            }

            if (!preg_match("/[A-Z]/", $new_password)) {
                $_SESSION['error'] = "New password must contain at least one uppercase letter";
                header("Location: /pages/update_profile.php");
                exit();
            }

            if (!preg_match("/[a-z]/", $new_password)) {
                $_SESSION['error'] = "New password must contain at least one lowercase letter";
                header("Location: /pages/update_profile.php");
                exit();
            }

            if (!preg_match("/[0-9]/", $new_password)) {
                $_SESSION['error'] = "New password must contain at least one number";
                header("Location: /pages/update_profile.php");
                exit();
            }

            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "New passwords do not match";
                header("Location: /pages/update_profile.php");
                exit();
            }

            // Add password to update
            $update_fields[] = "password = ?";
            $params[] = $new_password;
            $types .= "s";
        } else {
            $_SESSION['error'] = "Current password is incorrect";
            header("Location: /pages/update_profile.php");
            exit();
        }
    }

    // Add guest_id to params
    $params[] = $guest_id;
    $types .= "i";

    // Build and execute the update query
    $sql = "UPDATE tbl_guest SET " . implode(", ", $update_fields) . " WHERE guest_id = ?";
    $stmt = mysqli_prepare($mycon, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (mysqli_stmt_execute($stmt)) {
        // Update session variables
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;
        $_SESSION['user_email'] = $user_email;
        $_SESSION['phone_number'] = $phone_number;

        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: /pages/update_profile.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update profile: " . mysqli_error($mycon);
        header("Location: /pages/update_profile.php");
        exit();
    }
} else {
    header("Location: /pages/update_profile.php");
    exit();
}
?> 