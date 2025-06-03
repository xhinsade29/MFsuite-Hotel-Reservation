<?php
session_start();
include('../functions/db_connect.php');
if (isset($_SESSION['guest_id'])) {
    $guest_id = $_SESSION['guest_id'];
    $mycon->query("UPDATE user_notifications SET is_read = 1 WHERE guest_id = $guest_id");
    echo 'success';
} else {
    echo 'not_logged_in';
}

if (isset($_GET['admin']) && $_GET['admin'] == '1' && isset($_SESSION['admin_id'])) {
    $admin_id = intval($_SESSION['admin_id']);
    mysqli_query($mycon, "UPDATE user_notifications SET is_read = 1 WHERE admin_id = $admin_id");
    exit;
} 