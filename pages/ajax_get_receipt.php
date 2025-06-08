<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
// This is the key change: check for GUEST session
if (!isset($_SESSION['guest_id'])) {
    http_response_code(403);
    die('Forbidden. Please log in.');
}

include '../functions/db_connect.php';

$payment_id = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;
if (!$payment_id) {
    die('<div class="alert alert-danger">Invalid payment ID.</div>');
}

// Fetch payment details, ensuring the guest owns this reservation
$guest_id = $_SESSION['guest_id'];
$sql = "SELECT p.amount, p.payment_method, p.payment_status, p.reference_number, p.created_at, 
               r.reservation_id, 
               CONCAT(g.first_name, ' ', g.last_name) AS guest_name, 
               g.user_email as guest_email, 
               rt.type_name AS room_type 
        FROM tbl_payment p 
        JOIN tbl_reservation r ON p.payment_id = r.payment_id 
        JOIN tbl_guest g ON r.guest_id = g.guest_id 
        LEFT JOIN tbl_room rm ON r.assigned_room_id = rm.room_id 
        LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id 
        WHERE p.payment_id = ? AND r.guest_id = ?";
        
$stmt = $mycon->prepare($sql);
if (!$stmt) {
    die("Prepare failed: (" . $mycon->errno . ") " . $mycon->error);
}
$stmt->bind_param('ii', $payment_id, $guest_id);
$stmt->execute();
$result = $stmt->get_result();
$receipt = $result->fetch_assoc();

if (!$receipt) {
    if ($mycon->error) {
        die('<div class="alert alert-danger">SQL Error: ' . $mycon->error . '</div>');
    } else {
        die('<div class="alert alert-danger">Payment not found or you do not have permission to view it.</div>');
    }
}
$stmt->close();
$mycon->close();
?>
<div class="d-flex justify-content-center align-items-center" style="min-height:60vh;">
  <div class="card shadow-lg border-0" style="max-width:480px;width:100%;border-radius:22px;background:#fff;position:relative;font-family:'Inter',Arial,sans-serif;border:1.5px solid #ffa533;">
    <button class="print-btn" title="Print Receipt" style="position:absolute;top:18px;right:18px;background:#fff;border:1px solid #ffa533;color:#ffa533;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:background 0.2s,color 0.2s;z-index:2;"
      onclick="(function(){var printContents=document.querySelector('.card').innerHTML;var w=window.open('','_blank');w.document.write('<html><head><title>Print Receipt</title><link rel=\'stylesheet\' href=\'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css\'><style>body{background:#fff!important;}</style></head><body>'+printContents+'</body></html>');w.document.close();w.focus();setTimeout(function(){w.print();w.close();},500);})();">
      <i class="bi bi-printer"></i>
    </button>
    <div class="card-body p-4">
      <div class="text-center mb-3">
        <div class="mb-2" style="font-size:2.5rem;color:#ffa533;"><i class="bi bi-receipt"></i></div>
        <div class="fw-bold" style="font-size:1.7rem;color:#23234a;letter-spacing:1px;">MF Suites Hotel</div>
        <div class="text-secondary" style="font-size:1.1rem;">Payment Receipt</div>
      </div>
      <hr style="border-top:2px dashed #ffa533;opacity:.5;">
      <div class="row mb-2 g-2">
        <div class="col-6 small text-secondary">Guest</div><div class="col-6 fw-semibold"><?php echo htmlspecialchars($receipt['guest_name']); ?></div>
        <div class="col-6 small text-secondary">Email</div><div class="col-6"><?php echo htmlspecialchars($receipt['guest_email']); ?></div>
        <div class="col-6 small text-secondary">Reservation ID</div><div class="col-6"><?php echo htmlspecialchars($receipt['reservation_id']); ?></div>
        <div class="col-6 small text-secondary">Room Type</div><div class="col-6"><?php echo htmlspecialchars($receipt['room_type'] ?? 'N/A'); ?></div>
        <div class="col-6 small text-secondary">Reference #</div><div class="col-6"><?php echo htmlspecialchars($receipt['reference_number']); ?></div>
        <div class="col-6 small text-secondary">Payment Method</div>
        <div class="col-6">
          <?php echo htmlspecialchars($receipt['payment_method']); ?>
        </div>
        <div class="col-12 small text-secondary">Status</div>
        <div class="col-12">
          <?php
            $status = $receipt['payment_status'];
            $status_class = '';
            if ($status === 'Paid') {
                $status_class = 'text-success';
            } elseif ($status === 'Pending') {
                $status_class = 'text-warning';
            } elseif ($status === 'Failed' || $status === 'Refunded') {
                $status_class = 'text-danger';
            } else {
                $status_class = 'text-secondary';
            }
          ?>
          <div class="fw-bold <?php echo $status_class; ?> text-center" style="font-size:1.2em;">
            <?php echo htmlspecialchars($status); ?>
          </div>
        </div>
        <div class="col-6 small text-secondary">Date</div><div class="col-6"><?php echo date('M d, Y h:i A', strtotime($receipt['created_at'])); ?></div>
      </div>
      <hr style="border-top:2px dashed #ffa533;opacity:.5;">
      <div class="text-center mb-3">
        <div class="small text-secondary">Amount Paid</div>
        <div class="fw-bold text-success" style="font-size:2.2rem;">₱<?php echo number_format($receipt['amount'],2); ?></div>
      </div>
      <div class="text-center mb-3">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=MFsuiteReceipt-<?php echo $payment_id; ?>" alt="QR Code" class="rounded-3 border" style="background:#fff;" />
        <div class="small text-secondary mt-2">Scan for authenticity</div>
      </div>
      <div class="text-center text-secondary small" style="font-size:0.98em;">This is an electronically generated receipt.<br>Thank you for your payment!</div>
    </div>
  </div>
</div> 