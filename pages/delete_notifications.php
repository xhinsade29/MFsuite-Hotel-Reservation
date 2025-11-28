<?php
session_start();
if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit();
}
include('../functions/db_connect.php');
$guest_id = $_SESSION['guest_id'];
if (isset($_POST['notif_ids']) && is_array($_POST['notif_ids']) && count($_POST['notif_ids']) > 0) {
    // Sanitize IDs
    $ids = array_map('intval', $_POST['notif_ids']);
    $in = implode(',', $ids);
    // Hard delete: remove from database
    $sql = "DELETE FROM user_notifications WHERE user_notication_id IN ($in) AND guest_id = ?";
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param('i', $guest_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success'] = 'Selected notifications deleted.';
} else {
    $_SESSION['error'] = 'No notifications selected.';
}
header('Location: notifications.php');
exit(); 