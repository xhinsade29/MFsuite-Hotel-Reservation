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
include_once '../functions/notify.php'; // Ensure notify.php is included

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
            $admin_id = 1; // Use your default or actual admin_id here
            $user_name = $first_name . ' ' . $last_name;
            // Determine what was updated
            $profile_fields = [$_POST['first_name'], $_POST['middle_name'], $_POST['last_name'], $_POST['phone_number'], $_POST['address']];
            $payment_fields = [$_POST['bank_account_number'] ?? '', $_POST['paypal_email'] ?? '', $_POST['credit_card_number'] ?? '', $_POST['gcash_number'] ?? ''];
            $profile_updated = false;
            $payment_updated = false;
            foreach ($profile_fields as $field) { if (!empty($field)) { $profile_updated = true; break; } }
            foreach ($payment_fields as $field) { if (!empty($field)) { $payment_updated = true; break; } }
            // User notification
            if ($profile_updated && !$payment_updated) {
                $notif = "Your profile details have been updated.";
                $admin_notif = "User $user_name updated their profile details.";
            } elseif (!$profile_updated && $payment_updated) {
                $notif = "Your payment details have been updated.";
                $admin_notif = "User $user_name updated their payment details.";
            } else {
                $notif = "Your profile and payment details have been updated.";
                $admin_notif = "User $user_name updated their profile and payment details.";
            }
            // Use add_notification for user notification
            add_notification($guest_id, 'user', 'profile', $notif, $mycon, 0, $admin_id);
            // Use add_notification for admin notification
            add_notification($admin_id, 'admin', 'profile', $admin_notif, $mycon, 0, null, $guest_id); // Related ID could be guest_id

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
                // Insert notification for user
                $admin_id = 1; // Use your default or actual admin_id here
                $notif = "Your password has been changed.";
                add_notification($guest_id, 'user', 'profile', $notif, $mycon, 0, $admin_id);

                // Insert notification for admin
                $user_name = $first_name . ' ' . $last_name;
                $admin_notif = "User $user_name changed their password.";
                add_notification($admin_id, 'admin', 'profile', $admin_notif, $mycon, 0, null, $guest_id); // Related ID could be guest_id
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
            "address = ?",
        ];
        $params = [$first_name, $middle_name, $last_name, $phone_number, $user_email, $address];
        $types = "ssssss";
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
            $_SESSION['success'] = "Profile updated successfully!";
            // Add notification for admin (legacy profile update)
            $admin_id = 1; // Use your default or actual admin_id here
            $user_name = $first_name . ' ' . $last_name;
            $admin_notif = "User $user_name updated their profile details (via legacy page).";
            // Use add_notification for admin notification
             add_notification($admin_id, 'admin', 'profile', $admin_notif, $mycon, 0, null, $guest_id); // Related ID could be guest_id

        } else {
            $_SESSION['error'] = "Profile update failed: " . mysqli_error($mycon);
        }
        header("Location: update_profile.php");
        exit();

    } elseif ($update_type === 'payment') {
        // Get payment details (legacy)
        $bank_account_number = mysqli_real_escape_string($mycon, $_POST['bank_account_number'] ?? '');
        $paypal_email = mysqli_real_escape_string($mycon, $_POST['paypal_email'] ?? '');
        $credit_card_number = mysqli_real_escape_string($mycon, $_POST['credit_card_number'] ?? '');
        $gcash_number = mysqli_real_escape_string($mycon, $_POST['gcash_number'] ?? '');

        $update_fields = [
            "bank_account_number = ?",
            "paypal_email = ?",
            "credit_card_number = ?",
            "gcash_number = ?"
        ];
        $params = [$bank_account_number, $paypal_email, $credit_card_number, $gcash_number];
        $types = "ssss";
        $params[] = $guest_id;
        $types .= "i";

        $sql = "UPDATE tbl_guest SET " . implode(", ", $update_fields) . " WHERE guest_id = ?";
        $stmt = mysqli_prepare($mycon, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Payment details updated successfully!";
            // Add notification for admin (legacy payment update)
            $admin_id = 1; // Use your default or actual admin_id here
            // Fetch guest name to include in the admin notification
            $guest_name = '';
            $stmt_guest = $mycon->prepare("SELECT first_name, last_name FROM tbl_guest WHERE guest_id = ?");
            $stmt_guest->bind_param("i", $guest_id);
            $stmt_guest->execute();
            $stmt_guest->bind_result($first_name, $last_name);
            if ($stmt_guest->fetch()) {
                $guest_name = trim($first_name . ' ' . $last_name);
            }
            $stmt_guest->close();
            $admin_notif = "User $guest_name updated their payment details (via legacy page).";
            // Use add_notification for admin notification
             add_notification($admin_id, 'admin', 'payment', $admin_notif, $mycon, 0, null, $guest_id); // Related ID could be guest_id

        } else {
            $_SESSION['error'] = "Payment details update failed: " . mysqli_error($mycon);
        }
        header("Location: update_profile.php");
        exit();
    }
}
// Redirect to dashboard if not POST
header("Location: /pages/dashboard.php");
exit();
?> 