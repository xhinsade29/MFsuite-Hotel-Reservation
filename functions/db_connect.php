<?php
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASSWORD", "");
define("DB_NAME", "db_mfsuite_reservation");

$mycon = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if (!$mycon) {
    die("Error connecting to database: " . mysqli_connect_error());
} else {
    echo "Connected successfully to the database!";
}
?>
