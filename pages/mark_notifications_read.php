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