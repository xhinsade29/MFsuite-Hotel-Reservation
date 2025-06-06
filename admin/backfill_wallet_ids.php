<?php
require_once '../functions/db_connect.php';

function generate_wallet_id() {
    return bin2hex(random_bytes(16)); // 32-char hex string
}

// For Admins
$admins = mysqli_query($mycon, "SELECT admin_id FROM tbl_admin WHERE wallet_id IS NULL OR wallet_id = ''");
while ($row = mysqli_fetch_assoc($admins)) {
    $wallet_id = generate_wallet_id();
    // Ensure uniqueness
    $exists = mysqli_query($mycon, "SELECT 1 FROM tbl_guest WHERE wallet_id = '$wallet_id' UNION SELECT 1 FROM tbl_admin WHERE wallet_id = '$wallet_id'");
    while (mysqli_num_rows($exists) > 0) {
        $wallet_id = generate_wallet_id();
        $exists = mysqli_query($mycon, "SELECT 1 FROM tbl_guest WHERE wallet_id = '$wallet_id' UNION SELECT 1 FROM tbl_admin WHERE wallet_id = '$wallet_id'");
    }
    mysqli_query($mycon, "UPDATE tbl_admin SET wallet_id = '$wallet_id' WHERE admin_id = {$row['admin_id']}");
}

echo "Wallet IDs assigned to all users without one.\n"; 