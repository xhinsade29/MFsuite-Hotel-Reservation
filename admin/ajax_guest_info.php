<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized');
}
include '../functions/db_connect.php';
$guest_id = isset($_GET['guest_id']) ? intval($_GET['guest_id']) : 0;
if (!$guest_id) {
    echo '<div class="text-danger">Invalid guest ID.</div>';
    exit;
}
$guest = null;
$reservations = [];
$payments = [];
$preferred_payment = '';
// Fetch guest info
$stmt = $mycon->prepare("SELECT * FROM tbl_guest WHERE guest_id = ? LIMIT 1");
$stmt->bind_param('i', $guest_id);
$stmt->execute();
$guest = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$guest) {
    echo '<div class="text-danger">Guest not found.</div>';
    exit;
}
// Fetch reservations
$res = $mycon->query("SELECT * FROM tbl_reservation WHERE guest_id = $guest_id ORDER BY check_in DESC");
while ($row = $res->fetch_assoc()) {
    $reservations[] = $row;
}
// Fetch payments
$sql = "SELECT p.*, r.check_in, r.check_out FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id WHERE r.guest_id = $guest_id ORDER BY p.created_at DESC";
$res = $mycon->query($sql);
$payment_methods = [];
while ($row = $res->fetch_assoc()) {
    $payments[] = $row;
    if (!empty($row['payment_method'])) {
        $pm = $row['payment_method'];
        $payment_methods[$pm] = ($payment_methods[$pm] ?? 0) + 1;
    }
}
if ($payment_methods) {
    arsort($payment_methods);
    $preferred_payment = array_key_first($payment_methods);
}
$now = date('Y-m-d');
$upcoming = 0;
$past = 0;
$status_counts = [];
foreach ($reservations as $r) {
    if ($r['check_in'] >= $now && in_array($r['status'], ['pending','approved'])) $upcoming++;
    if ($r['check_out'] < $now && $r['status'] === 'completed') $past++;
    $status = ucfirst($r['status']);
    $status_counts[$status] = ($status_counts[$status] ?? 0) + 1;
}
?>
<div class="row g-4 align-items-stretch">
    <!-- Left: Guest Info -->
    <div class="col-md-5 border-end">
        <div class="text-center mb-4">
            <img src="<?php echo $guest['profile_picture'] ? '../uploads/profile_pictures/' . htmlspecialchars($guest['profile_picture']) : 'https://ui-avatars.com/api/?name=' . urlencode(trim($guest['first_name'] . ' ' . $guest['last_name'])) . '&background=FF8C00&color=fff'; ?>" class="profile-avatar shadow" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:4px solid #FF8C00;margin-bottom:18px;box-shadow:0 0 0 6px rgba(255,140,0,0.18),0 2px 16px rgba(0,0,0,0.18);" alt="Guest Avatar">
            <h4 class="fw-semibold mb-0 mt-2"><?php echo htmlspecialchars(trim($guest['first_name'] . ' ' . $guest['middle_name'] . ' ' . $guest['last_name'])); ?></h4>
        </div>
        <div class="mb-2"><span class="text-warning fw-bold">Email:</span> <?php echo htmlspecialchars($guest['user_email']); ?></div>
        <div class="mb-2"><span class="text-warning fw-bold">Phone:</span> <?php echo htmlspecialchars($guest['phone_number']); ?></div>
        <div class="mb-2"><span class="text-warning fw-bold">Address:</span> <?php echo htmlspecialchars($guest['address']); ?></div>
        <div class="mb-2"><span class="text-warning fw-bold">Wallet ID:</span> <?php echo htmlspecialchars($guest['wallet_id']); ?></div>
        <div class="mb-2"><span class="text-warning fw-bold">Wallet Balance:</span> <span class="text-success">₱<?php echo number_format($guest['wallet_balance'],2); ?></span></div>
        <div class="mb-2"><span class="text-warning fw-bold">Preferred Payment Method:</span> <span class="fw-bold text-warning"><?php echo $preferred_payment ?: '-'; ?></span></div>
        <div class="mt-4 p-3 bg-dark border border-warning rounded-3">
            <div class="fw-bold text-warning mb-2"><i class="bi bi-credit-card-2-front me-2"></i>Payment Account Info</div>
            <div class="mb-1"><span class="text-warning">GCash:</span> <?php echo isset($guest['gcash_number']) && $guest['gcash_number'] ? htmlspecialchars($guest['gcash_number']) : '<span class=\'text-secondary\'>-</span>'; ?></div>
            <div class="mb-1"><span class="text-warning">Bank Account #:</span> <?php echo isset($guest['bank_account_number']) && $guest['bank_account_number'] ? htmlspecialchars($guest['bank_account_number']) : '<span class=\'text-secondary\'>-</span>'; ?></div>
            <div class="mb-1"><span class="text-warning">PayPal Email:</span> <?php echo isset($guest['paypal_email']) && $guest['paypal_email'] ? htmlspecialchars($guest['paypal_email']) : '<span class=\'text-secondary\'>-</span>'; ?></div>
            <div class="mb-1"><span class="text-warning">Credit Card #:</span> <?php echo isset($guest['credit_card_number']) && $guest['credit_card_number'] ? htmlspecialchars($guest['credit_card_number']) : '<span class=\'text-secondary\'>-</span>'; ?></div>
        </div>
    </div>
    <!-- Right: Stats & History -->
    <div class="col-md-7">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="bg-secondary bg-opacity-10 rounded-3 p-3 mb-2">
                    <div class="text-warning fw-bold">Total Reservations</div>
                    <div class="fs-4 fw-bold"><?php echo count($reservations); ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-secondary bg-opacity-10 rounded-3 p-3 mb-2">
                    <div class="text-warning fw-bold">Upcoming Bookings</div>
                    <div class="fs-4 fw-bold"><?php echo $upcoming; ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-secondary bg-opacity-10 rounded-3 p-3 mb-2">
                    <div class="text-warning fw-bold">Past Stays</div>
                    <div class="fs-4 fw-bold"><?php echo $past; ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-secondary bg-opacity-10 rounded-3 p-3 mb-2">
                    <div class="text-warning fw-bold">Reservation Status Summary</div>
                    <div>
                        <?php foreach ($status_counts as $status => $cnt) {
                            echo "<span class='badge bg-secondary me-1'>$status: $cnt</span> ";
                        } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="modern-table-wrapper mt-4">
            <div class="d-flex align-items-center mb-3">
                <div class="fw-bold text-warning me-3"><i class="bi bi-list-ul me-2"></i>History</div>
                <select id="historyType" class="form-select form-select-sm w-auto bg-dark text-warning border-warning">
                    <option value="reservation">Reservation History</option>
                    <option value="payment">Payment History</option>
                </select>
            </div>
            <div id="reservation-history-table">
                <div class="fw-bold text-warning mb-2"><i class="bi bi-calendar2-check me-2"></i>Reservation History</div>
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-bordered modern-table mb-0">
                        <thead><tr><th>ID</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if ($reservations): foreach ($reservations as $r): ?>
                            <tr>
                                <td><?php echo $r['reservation_id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($r['check_in'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($r['check_out'])); ?></td>
                                <td><span class="badge bg-<?php
                                    echo $r['status']==='approved'?'success':(
                                        $r['status']==='cancelled'?'danger':(
                                        $r['status']==='denied'?'warning text-dark':(
                                        $r['status']==='completed'?'primary':(
                                        $r['status']==='cancellation_requested'?'info text-dark':'secondary'))));
                                ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center text-secondary">No reservations found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="payment-history-table" style="display:none;">
                <div class="fw-bold text-warning mb-2"><i class="bi bi-receipt me-2"></i>Payment History</div>
                <div class="table-responsive">
                    <table class="table table-dark table-striped table-bordered modern-table mb-0">
                        <thead><tr><th>ID</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php if ($payments): foreach ($payments as $p): ?>
                            <tr>
                                <td><?php echo $p['payment_id']; ?></td>
                                <td>₱<?php echo number_format($p['amount'],2); ?></td>
                                <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                                <td><span class="badge bg-<?php
                                    echo $p['payment_status']==='Paid'?'success':(
                                        $p['payment_status']==='Pending'?'warning text-dark':(
                                        $p['payment_status']==='Refunded'?'info text-dark':'danger'));
                                ?>"><?php echo htmlspecialchars($p['payment_status']); ?></span></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($p['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center text-secondary">No payments found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> 