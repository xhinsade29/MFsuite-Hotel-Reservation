<?php
if (!defined('DB_HOST')) {
    define("DB_HOST", "localhost");
}
if (!defined('DB_USER')) {
    define("DB_USER", "root");
}
if (!defined('DB_PASSWORD')) {
    define("DB_PASSWORD", "");
}
if (!defined('DB_NAME')) {
    define("DB_NAME", "db_mfsuite_reservation");
}

$mycon = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if (!$mycon) {
    die("Error connecting to database: " . mysqli_connect_error());
}
?>
