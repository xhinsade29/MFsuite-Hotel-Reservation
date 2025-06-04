<?php
session_start();
// Set logout success message using a temporary session
$_SESSION['admin_logout_success'] = true;
// Unset all session variables
$_SESSION = array();
// Destroy the session
session_destroy();
// Start a new session to pass the message
session_start();
$_SESSION['admin_logout_success'] = true;
// Redirect to admin login page
header('Location: admin_login.php');
exit();
?> 