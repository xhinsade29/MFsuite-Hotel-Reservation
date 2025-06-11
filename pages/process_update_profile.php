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

    // Handle theme preference (AJAX call from settings.php)
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

    $did_update = false;

    // --- Update Personal Profile Information ---
    if (isset($_POST['first_name']) && isset($_POST['user_email'])) {
        $first_name = mysqli_real_escape_string($mycon, $_POST['first_name']);
        $middle_name = mysqli_real_escape_string($mycon, $_POST['middle_name']);
        $last_name = mysqli_real_escape_string($mycon, $_POST['last_name']);
        $user_email = mysqli_real_escape_string($mycon, $_POST['user_email']);
        $phone_number = mysqli_real_escape_string($mycon, $_POST['phone_number']);
        $address = mysqli_real_escape_string($mycon, $_POST['address']);

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

        // Check if email is already taken by another user
        $check_email = "SELECT * FROM tbl_guest WHERE user_email = ? AND guest_id != ?";
        $stmt = mysqli_prepare($mycon, $check_email);
        mysqli_stmt_bind_param($stmt, "si", $user_email, $guest_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Profile update failed: Email already exists.";
            header("Location: settings.php"); // Redirect to settings page as this is for personal info
            exit();
        }

        // Build update query for tbl_guest (personal info only)
        $update_fields = [
            "first_name = ?",
            "middle_name = ?",
            "last_name = ?",
            "user_email = ?",
            "phone_number = ?",
            "address = ?"
        ];
        $params = [$first_name, $middle_name, $last_name, $user_email, $phone_number, $address];
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
            $_SESSION['success'] = "Profile details updated successfully!";
            $did_update = true;
            // Update session variables if changed
            $_SESSION['first_name'] = $first_name;
            $_SESSION['middle_name'] = $middle_name;
            $_SESSION['last_name'] = $last_name;
            $_SESSION['user_email'] = $user_email;
            if ($profile_picture) {
                $_SESSION['profile_picture'] = $profile_picture;
            }
            // Add notification
            add_notification($guest_id, 'user', 'profile', 'Your profile details have been updated.', $mycon, 0, 1); // Admin ID 1 for system notification
        } else {
            $_SESSION['error'] = "Profile update failed: " . mysqli_error($mycon);
        }
        $stmt->close();
    }

    // --- Update Payment Accounts (NEW LOGIC) ---
    if (isset($_POST['payment_account_type'])) {
        $account_type = strtolower(trim($_POST['payment_account_type']));
        $account_number = mysqli_real_escape_string($mycon, $_POST['account_number'] ?? '');
        $account_email = mysqli_real_escape_string($mycon, $_POST['account_email'] ?? '');

        if (!empty($account_type) && (!empty($account_number) || !empty($account_email))) {
            $check_sql = "";
            $check_stmt_types = "";
            $check_stmt_params = [];

            if ($account_type === 'bank_transfer') {
                // If linking 'bank_transfer', check for both 'bank' and 'bank_transfer' as existing types
                $check_sql = "SELECT account_id FROM guest_payment_accounts WHERE guest_id = ? AND (account_type = ? OR account_type = ?) LIMIT 1";
                $check_stmt_types = "iss";
                $check_stmt_params = [$guest_id, 'bank', 'bank_transfer'];
            } else {
                // For other account types, check for exact match
                $check_sql = "SELECT account_id FROM guest_payment_accounts WHERE guest_id = ? AND account_type = ? LIMIT 1";
                $check_stmt_types = "is";
                $check_stmt_params = [$guest_id, $account_type];
            }

            $check_stmt = mysqli_prepare($mycon, $check_sql);
            mysqli_stmt_bind_param($check_stmt, $check_stmt_types, ...$check_stmt_params);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_bind_result($check_stmt, $existing_account_id);
            $has_existing_account = mysqli_stmt_fetch($check_stmt);
            mysqli_stmt_close($check_stmt);

            if ($has_existing_account) {
                // Update existing account and explicitly set account_type to the new normalized value
                $update_account_sql = "UPDATE guest_payment_accounts SET account_type = ?, account_number = ?, account_email = ? WHERE account_id = ?";
                $update_account_stmt = mysqli_prepare($mycon, $update_account_sql);
                mysqli_stmt_bind_param($update_account_stmt, "sssi", $account_type, $account_number, $account_email, $existing_account_id);
                if (mysqli_stmt_execute($update_account_stmt)) {
                    // Add notification for account update
                    $notif_msg = "Your " . ucfirst(str_replace('_', ' ', $account_type)) . " payment account has been updated.";
                    add_notification($guest_id, 'user', 'profile', $notif_msg, $mycon, 0, 1); // Admin ID 1 for system notification
                    echo json_encode(['success' => true, 'message' => "Your " . ucfirst(str_replace('_', ' ', $account_type)) . " account has been updated."]);
                } else {
                    echo json_encode(['success' => false, 'message' => "Failed to update $account_type account: " . mysqli_error($mycon)]);
                }
                mysqli_stmt_close($update_account_stmt);
            } else {
                // Insert new account
                $insert_account_sql = "INSERT INTO guest_payment_accounts (guest_id, account_type, account_number, account_email, balance) VALUES (?, ?, ?, ?, 0.00)";
                $insert_account_stmt = mysqli_prepare($mycon, $insert_account_sql);
                mysqli_stmt_bind_param($insert_account_stmt, "isss", $guest_id, $account_type, $account_number, $account_email);
                if (mysqli_stmt_execute($insert_account_stmt)) {
                    // Add notification for new account linked
                    $notif_msg = "A new " . ucfirst(str_replace('_', ' ', $account_type)) . " payment account has been linked to your profile.";
                    add_notification($guest_id, 'user', 'profile', $notif_msg, $mycon, 0, 1); // Admin ID 1 for system notification
                    echo json_encode(['success' => true, 'message' => "A new " . ucfirst(str_replace('_', ' ', $account_type)) . " account has been linked successfully!"]);
                } else {
                    echo json_encode(['success' => false, 'message' => "Failed to link new $account_type account: " . mysqli_error($mycon)]);
                }
                mysqli_stmt_close($insert_account_stmt);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Payment account update failed: Account type, number, or email cannot be empty."]);
        }
        exit(); // Crucial: exit after JSON response for linking/updating
    }

    // --- Password change ---
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
            // Password validation (simplified, add more if needed)
            if (strlen($new_password) < 8 || !preg_match("/[A-Z]/", $new_password) || !preg_match("/[a-z]/", $new_password) || !preg_match("/[0-9]/", $new_password)) {
                $_SESSION['error'] = "Password update failed: New password must be at least 8 characters long and contain at least one uppercase, one lowercase letter, and one number.";
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
                $did_update = true;
                add_notification($guest_id, 'user', 'profile', 'Your password has been changed.', $mycon, 0, 1); // Admin ID 1 for system notification
            } else {
                $_SESSION['error'] = 'Password update failed: ' . $update->error;
            }
            $update->close();
        } else {
            $_SESSION['error'] = "Password update failed: Current password is incorrect.";
        }
    }

    // --- Delete Payment Account (NEW LOGIC) ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete_payment_account') {
        $account_id = intval($_POST['account_id'] ?? 0);
        if ($account_id > 0) {
            // Ensure the account belongs to the logged-in guest for security
            $check_owner_sql = "SELECT guest_id FROM guest_payment_accounts WHERE account_id = ? LIMIT 1";
            $check_owner_stmt = mysqli_prepare($mycon, $check_owner_sql);
            mysqli_stmt_bind_param($check_owner_stmt, "i", $account_id);
            mysqli_stmt_execute($check_owner_stmt);
            mysqli_stmt_bind_result($check_owner_stmt, $owner_guest_id);
            mysqli_stmt_fetch($check_owner_stmt);
            mysqli_stmt_close($check_owner_stmt);

            if ($owner_guest_id === $guest_id) {
                // Fetch account_type before deleting to use in notification
                $get_account_type_sql = "SELECT account_type FROM guest_payment_accounts WHERE account_id = ? LIMIT 1";
                $get_account_type_stmt = mysqli_prepare($mycon, $get_account_type_sql);
                mysqli_stmt_bind_param($get_account_type_stmt, "i", $account_id);
                mysqli_stmt_execute($get_account_type_stmt);
                mysqli_stmt_bind_result($get_account_type_stmt, $unlinked_account_type);
                mysqli_stmt_fetch($get_account_type_stmt);
                mysqli_stmt_close($get_account_type_stmt);

                $delete_sql = "DELETE FROM guest_payment_accounts WHERE account_id = ?";
                $delete_stmt = mysqli_prepare($mycon, $delete_sql);
                mysqli_stmt_bind_param($delete_stmt, "i", $account_id);
                if (mysqli_stmt_execute($delete_stmt)) {
                    echo json_encode(['success' => true, 'message' => "Payment account unlinked successfully!"]);
                    // Add notification for the user
                    if (!empty($unlinked_account_type)) {
                        $notif_msg = "Your " . ucfirst(str_replace('_', ' ', $unlinked_account_type)) . " payment account has been unlinked from your profile.";
                        add_notification($guest_id, 'user', 'profile', $notif_msg, $mycon, 0, 1); // Admin ID 1 for system notification
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => "Failed to unlink payment account: " . mysqli_error($mycon)]);
                }
                mysqli_stmt_close($delete_stmt);
            } else {
                echo json_encode(['success' => false, 'message' => "Unauthorized attempt to unlink payment account."]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Invalid account ID for unlinking."]);
        }
        exit(); // Crucial: exit after JSON response
    }

    // Redirect after all updates
    if ($did_update) {
        header("Location: update_profile.php?updated=true"); // Redirect back to update_profile for general updates
    } else {
        header("Location: update_profile.php"); // Redirect back without updated flag if no changes
    }
    exit();
}

// If not a POST request, just show the form (nothing to do here)
header("Location: update_profile.php");
exit();
?> 