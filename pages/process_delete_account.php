<?php
session_start();
include('../functions/db_connect.php');
if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit();
}
$guest_id = $_SESSION['guest_id'];
// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit();
}
// Check if AJAX
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
// Confirm deletion (CSRF protection can be added for extra security)
if (!isset($_POST['confirm_delete'])) {
    // Check for active reservations
    $active_sql = "SELECT COUNT(*) FROM tbl_reservation WHERE guest_id = ? AND status IN ('pending','approved')";
    $stmt = $mycon->prepare($active_sql);
    $stmt->bind_param('i', $guest_id);
    $stmt->execute();
    $stmt->bind_result($active_count);
    $stmt->fetch();
    $stmt->close();
    if ($active_count > 0) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You cannot delete your account while you have active reservations (pending or approved). Please cancel all active reservations first.']);
            exit();
        }
        // Show message: cannot delete account with active reservations
        echo '<!DOCTYPE html><html><head><title>Cannot Delete Account</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-dark text-light"><div class="container py-5"><div class="card bg-dark text-light p-4 mx-auto" style="max-width:480px;"><h3 class="mb-3 text-danger">Cannot Delete Account</h3><p>You cannot delete your account while you have active reservations (pending or approved). Please cancel all active reservations first.</p><a href="settings.php" class="btn btn-secondary">Back to Settings</a></div></div></body></html>';
        exit();
    }
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'confirm' => true]);
        exit();
    }
    // Show confirmation form
    echo '<!DOCTYPE html><html><head><title>Confirm Account Deletion</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="bg-dark text-light"><div class="container py-5"><div class="card bg-dark text-light p-4 mx-auto" style="max-width:480px;"><h3 class="mb-3 text-danger">Confirm Account Deletion</h3><p>Are you sure you want to delete your account? This action cannot be undone. All your reservations, payments, and data will be permanently removed.</p><form method="POST"><input type="hidden" name="confirm_delete" value="1"><button type="submit" class="btn btn-danger">Yes, Delete My Account</button> <a href="settings.php" class="btn btn-secondary ms-2">Cancel</a></form></div></div></body></html>';
    exit();
}
// Soft delete: set is_deleted = 1 and anonymize sensitive info
$stmt = $mycon->prepare("UPDATE tbl_guest SET is_deleted = 1, first_name = 'Deleted', last_name = 'User', user_email = NULL, phone_number = NULL, address = NULL, bank_account_number = NULL, paypal_email = NULL, credit_card_number = NULL, gcash_number = NULL, profile_picture = NULL WHERE guest_id = ?");
$stmt->bind_param('i', $guest_id);
$stmt->execute();
$stmt->close();
session_unset();
session_destroy();
if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'deleted' => true]);
    exit();
}
header('Location: login.php?msg=Account+deleted+successfully');
exit(); 