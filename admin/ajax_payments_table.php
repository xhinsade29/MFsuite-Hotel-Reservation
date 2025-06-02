<?php
// admin/ajax_payments_table.php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}
include '../functions/db_connect.php';

// Allowed columns for sorting
$allowed_sort = [
    'reservation_id' => 'r.reservation_id',
    'guest_name' => "guest_name",
    'payment_method' => 'p.payment_method',
    'amount' => 'p.amount',
    'payment_status' => 'p.payment_status',
    'created_at' => 'p.created_at',
];

// Get sort parameters
$sort_by = $_GET['sort_by'] ?? $_POST['sort_by'] ?? 'created_at';
$sort_dir = strtolower($_GET['sort_dir'] ?? $_POST['sort_dir'] ?? 'desc');
$sort_dir = ($sort_dir === 'asc') ? 'ASC' : 'DESC';
if (!array_key_exists($sort_by, $allowed_sort)) {
    $sort_by = 'created_at';
}

// Get filter parameters
$payment_method = $_GET['payment_method'] ?? $_POST['payment_method'] ?? '';
$payment_status = $_GET['payment_status'] ?? $_POST['payment_status'] ?? '';
$date_from = $_GET['date_from'] ?? $_POST['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? $_POST['date_to'] ?? '';
$search = trim($_GET['search'] ?? $_POST['search'] ?? '');

// Build SQL (add payment_id and guest_id)
$sql = "SELECT p.payment_id, p.amount, p.payment_method, p.payment_status, p.reference_number, p.created_at, r.reservation_id, r.guest_id, CONCAT(g.first_name, ' ', IFNULL(g.middle_name, ''), ' ', g.last_name) AS guest_name FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id ";

$where = [];
$params = [];
$types = '';

if ($payment_method) {
    $where[] = 'p.payment_method = ?';
    $params[] = $payment_method;
    $types .= 's';
}
if ($payment_status) {
    $where[] = 'p.payment_status = ?';
    $params[] = $payment_status;
    $types .= 's';
}
if ($date_from) {
    $where[] = 'DATE(p.created_at) >= ?';
    $params[] = $date_from;
    $types .= 's';
}
if ($date_to) {
    $where[] = 'DATE(p.created_at) <= ?';
    $params[] = $date_to;
    $types .= 's';
}
if ($search) {
    $where[] = '(CONCAT(g.first_name, " ", IFNULL(g.middle_name, ""), " ", g.last_name) LIKE ? OR r.reservation_id LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if ($where) {
    $sql .= 'WHERE ' . implode(' AND ', $where) . ' ';
}

// For guest_name, sort by the alias
if ($sort_by === 'guest_name') {
    $sql .= "ORDER BY guest_name $sort_dir";
} else {
    $sql .= "ORDER BY {$allowed_sort[$sort_by]} $sort_dir";
}

$stmt = $mycon->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['reservation_id'] ?: '-') . '</td>';
        echo '<td>' . htmlspecialchars(trim($row['guest_name']) ?: '-') . '</td>';
        echo '<td>' . htmlspecialchars($row['payment_method']) . '</td>';
        echo '<td>₱' . number_format($row['amount'], 2) . '</td>';
        echo '<td>' . htmlspecialchars($row['payment_status']) . '</td>';
        echo '<td>' . date('M d, Y H:i', strtotime($row['created_at'])) . '</td>';
        echo '<td>';
        echo '<button class="btn btn-sm btn-outline-info me-1 view-receipt-btn" data-payment-id="' . htmlspecialchars($row['payment_id']) . '" data-reservation-id="' . htmlspecialchars($row['reservation_id']) . '" data-guest-id="' . htmlspecialchars($row['guest_id']) . '">View Receipt</button>';
        echo '<button class="btn btn-sm btn-outline-secondary resend-confirmation-btn" data-payment-id="' . htmlspecialchars($row['payment_id']) . '" data-reservation-id="' . htmlspecialchars($row['reservation_id']) . '" data-guest-id="' . htmlspecialchars($row['guest_id']) . '">Re-send Confirmation</button>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="7" class="text-center text-secondary">No payments found.</td></tr>';
}
$stmt->close(); 