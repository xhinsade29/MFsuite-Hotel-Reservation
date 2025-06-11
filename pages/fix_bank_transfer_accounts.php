<?php
// One-time script to fix 'bank' account types in guest_payment_accounts
// Run this ONCE, then delete the file for security

include('../functions/db_connect.php');

$sql = "UPDATE guest_payment_accounts SET account_type = 'bank_transfer' WHERE LOWER(TRIM(account_type)) = 'bank'";
if ($mycon->query($sql) === TRUE) {
    echo "All 'bank' account types have been updated to 'bank_transfer'.<br>";
} else {
    echo "Error updating records: " . $mycon->error . "<br>";
}

echo "Done. Please delete this file for security."; 