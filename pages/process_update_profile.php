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
    $update_type = $_POST['update_type'] ?? '';

    // Persist theme preference
    if (isset($_POST['theme_preference'])) {
        $theme = $_POST['theme_preference'] === 'light' ? 'light' : 'dark';
        $_SESSION['theme_preference'] = $theme;
        // Save to database
        $stmt = $mycon->prepare("UPDATE tbl_guest SET theme_preference = ? WHERE guest_id = ?");
        $stmt->bind_param("si", $theme, $guest_id);
        $stmt->execute();
        $stmt->close();
        exit;
    }

    // --- SETTINGS PAGE LOGIC (no update_type) ---
    $did_update = false;
    if (isset($_POST['first_name']) && isset($_POST['user_email'])) {
        // Update profile and payment info
        $first_name = mysqli_real_escape_string($mycon, $_POST['first_name']);
        $middle_name = mysqli_real_escape_string($mycon, $_POST['middle_name']);
        $last_name = mysqli_real_escape_string($mycon, $_POST['last_name']);
        $user_email = mysqli_real_escape_string($mycon, $_POST['user_email']);
        $phone_number = mysqli_real_escape_string($mycon, $_POST['phone_number']);
        $address = mysqli_real_escape_string($mycon, $_POST['address']);
        $bank_account_number = mysqli_real_escape_string($mycon, $_POST['bank_account_number'] ?? '');
        $paypal_email = mysqli_real_escape_string($mycon, $_POST['paypal_email'] ?? '');
        $credit_card_number = mysqli_real_escape_string($mycon, $_POST['credit_card_number'] ?? '');
        $gcash_number = mysqli_real_escape_string($mycon, $_POST['gcash_number'] ?? '');

        // Handle profile picture upload (for settings page)
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

        // Check if email is already taken by another user
        $check_email = "SELECT * FROM tbl_guest WHERE user_email = ? AND guest_id != ?";
        $stmt = mysqli_prepare($mycon, $check_email);
        mysqli_stmt_bind_param($stmt, "si", $user_email, $guest_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Profile update failed: Email already exists.";
            header("Location: settings.php");
            exit();
        }

        // Build update query
        $update_fields = [
            "first_name = ?",
            "middle_name = ?",
            "last_name = ?",
            "user_email = ?",
            "phone_number = ?",
            "address = ?",
            "bank_account_number = ?",
            "paypal_email = ?",
            "credit_card_number = ?",
            "gcash_number = ?"
        ];
        $params = [$first_name, $middle_name, $last_name, $user_email, $phone_number, $address, $bank_account_number, $paypal_email, $credit_card_number, $gcash_number];
        $types = "ssssssssss";
        if ($profile_picture) {
            $update_fields[] = "profile_picture = ?";
            $params[] = $profile_picture;
            $types .= "s";
        }
        $params[] = $guest_id;
        $types .= "i";
        $sql = "UPDATE tbl_guest SET " . implode(", ", $update_fields) . " WHERE guest_id = ?";
        $stmt = mysqli_prepare($mycon, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Profile and payment details updated successfully!";
            // Insert notification
            $notif = "Your profile and payment details have been updated.";
            $notif_sql = "INSERT INTO user_notifications (guest_id, message, created_at) VALUES (?, ?, NOW())";
            $notif_stmt = mysqli_prepare($mycon, $notif_sql);
            mysqli_stmt_bind_param($notif_stmt, "is", $guest_id, $notif);
            mysqli_stmt_execute($notif_stmt);
            $did_update = true;
        } else {
            $_SESSION['error'] = "Profile update failed: " . mysqli_error($mycon);
            header("Location: settings.php");
            exit();
        }
    }
    // Password change from settings page
    if (isset($_POST['current_password']) && isset($_POST['new_password']) && isset($_POST['confirm_new_password'])) {
        $sql = "SELECT password FROM tbl_guest WHERE guest_id = ?";
        $stmt = mysqli_prepare($mycon, $sql);
        mysqli_stmt_bind_param($stmt, "i", $guest_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        if ($_POST['current_password'] === $user['password']) {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_new_password'];
            if (strlen($new_password) < 8) {
                $_SESSION['error'] = "Password update failed: New password must be at least 8 characters long.";
                header("Location: settings.php");
                exit();
            }
            if (!preg_match("/[A-Z]/", $new_password)) {
                $_SESSION['error'] = "Password update failed: New password must contain at least one uppercase letter.";
                header("Location: settings.php");
                exit();
            }
            if (!preg_match("/[a-z]/", $new_password)) {
                $_SESSION['error'] = "Password update failed: New password must contain at least one lowercase letter.";
                header("Location: settings.php");
                exit();
            }
            if (!preg_match("/[0-9]/", $new_password)) {
                $_SESSION['error'] = "Password update failed: New password must contain at least one number.";
                header("Location: settings.php");
                exit();
            }
            if ($new_password !== $confirm_password) {
                $_SESSION['error'] = "Password update failed: New passwords do not match.";
                header("Location: settings.php");
                exit();
            }
            $update = $mycon->prepare("UPDATE tbl_guest SET password = ? WHERE guest_id = ?");
            $update->bind_param('si', $new_password, $guest_id);
            if ($update->execute()) {
                $_SESSION['success'] = 'Password updated successfully!';
                // Insert notification
                $notif = "Your password has been changed.";
                $notif_sql = "INSERT INTO user_notifications (guest_id, message, created_at) VALUES (?, ?, NOW())";
                $notif_stmt = mysqli_prepare($mycon, $notif_sql);
                mysqli_stmt_bind_param($notif_stmt, "is", $guest_id, $notif);
                mysqli_stmt_execute($notif_stmt);
            } else {
                $_SESSION['error'] = 'Password update failed: ' . $update->error;
                header("Location: settings.php");
                exit();
            }
            $update->close();
            $did_update = true;
        } else {
            $_SESSION['error'] = "Password update failed: Current password is incorrect.";
            header("Location: settings.php");
            exit();
        }
    }
    if ($did_update) {
        header("Location: settings.php");
        exit();
    }
    // --- END SETTINGS PAGE LOGIC ---

    // --- LEGACY PROFILE PAGE LOGIC (with update_type) ---
    if ($update_type === 'user') {
        // Get user details
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
            header("Location: update_profile.php");
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

        // Build update query for user details
        $update_fields = [
            "first_name = ?",
            "middle_name = ?",
            "last_name = ?",
            "phone_number = ?",
            "user_email = ?",
            "address = ?"
        ];
        $params = [$first_name, $middle_name, $last_name, $phone_number, $user_email, $address];
        $types = "ssssss";
        if ($profile_picture) {
            $update_fields[] = "profile_picture = ?";
            $params[] = $profile_picture;
            $types .= "s";
        }

        // Handle password update if provided
        if (!empty($_POST['current_password']) && !empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
            $sql = "SELECT password FROM tbl_guest WHERE guest_id = ?";
            $stmt = mysqli_prepare($mycon, $sql);
            mysqli_stmt_bind_param($stmt, "i", $guest_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            if ($_POST['current_password'] === $user['password']) {
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                if (strlen($new_password) < 8) {
                    $_SESSION['error'] = "New password must be at least 8 characters long";
                    header("Location: update_profile.php");
                    exit();
                }
                if (!preg_match("/[A-Z]/", $new_password)) {
                    $_SESSION['error'] = "New password must contain at least one uppercase letter";
                    header("Location: update_profile.php");
                    exit();
                }
                if (!preg_match("/[a-z]/", $new_password)) {
                    $_SESSION['error'] = "New password must contain at least one lowercase letter";
                    header("Location: update_profile.php");
                    exit();
                }
                if (!preg_match("/[0-9]/", $new_password)) {
                    $_SESSION['error'] = "New password must contain at least one number";
                    header("Location: update_profile.php");
                    exit();
                }
                if ($new_password !== $confirm_password) {
                    $_SESSION['error'] = "New passwords do not match";
                    header("Location: update_profile.php");
                    exit();
                }
                $update_fields[] = "password = ?";
                $params[] = $new_password;
                $types .= "s";
            } else {
                $_SESSION['error'] = "Current password is incorrect";
                header("Location: update_profile.php");
                exit();
            }
        }
        $params[] = $guest_id;
        $types .= "i";
        $sql = "UPDATE tbl_guest SET " . implode(", ", $update_fields) . " WHERE guest_id = ?";
        $stmt = mysqli_prepare($mycon, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        if (mysqli_stmt_execute($stmt)) {
            // Update session variables
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['user_email'] = $user_email;
            $_SESSION['phone_number'] = $phone_number;
            $_SESSION['success'] = "User details updated successfully!";
            // Insert notification
            $notif = "Your profile details have been updated.";
            $notif_sql = "INSERT INTO user_notifications (guest_id, message, created_at) VALUES (?, ?, NOW())";
            $notif_stmt = mysqli_prepare($mycon, $notif_sql);
            mysqli_stmt_bind_param($notif_stmt, "is", $guest_id, $notif);
            mysqli_stmt_execute($notif_stmt);
            header("Location: update_profile.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update user details: " . mysqli_error($mycon);
            header("Location: update_profile.php");
            exit();
        }
    } elseif ($update_type === 'payment') {
        // Get payment details
        $bank_account_number = mysqli_real_escape_string($mycon, $_POST['bank_account_number'] ?? '');
        $paypal_email = mysqli_real_escape_string($mycon, $_POST['paypal_email'] ?? '');
        $credit_card_number = mysqli_real_escape_string($mycon, $_POST['credit_card_number'] ?? '');
        $gcash_number = mysqli_real_escape_string($mycon, $_POST['gcash_number'] ?? '');
        $sql = "UPDATE tbl_guest SET bank_account_number = ?, paypal_email = ?, credit_card_number = ?, gcash_number = ? WHERE guest_id = ?";
        $stmt = mysqli_prepare($mycon, $sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $bank_account_number, $paypal_email, $credit_card_number, $gcash_number, $guest_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Payment details updated successfully!";
            // Insert notification
            $notif = "Your payment details have been updated.";
            $notif_sql = "INSERT INTO user_notifications (guest_id, message, created_at) VALUES (?, ?, NOW())";
            $notif_stmt = mysqli_prepare($mycon, $notif_sql);
            mysqli_stmt_bind_param($notif_stmt, "is", $guest_id, $notif);
            mysqli_stmt_execute($notif_stmt);
            header("Location: update_profile.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update payment details: " . mysqli_error($mycon);
            header("Location: update_profile.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Invalid update type.";
        header("Location: update_profile.php");
        exit();
    }
} else {
    header("Location: update_profile.php");
    exit();
}
?> 