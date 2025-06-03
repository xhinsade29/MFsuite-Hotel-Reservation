<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';

// Fetch all guests for table
$guests = [];
$res = $mycon->query("SELECT guest_id, first_name, middle_name, last_name, user_email, phone_number FROM tbl_guest ORDER BY first_name, last_name");
while ($row = $res->fetch_assoc()) {
    $guests[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guests - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        .guests-container { margin-left: 240px; margin-top: 70px; padding: 40px 24px 24px 24px; max-width: 1400px; }
        .guests-title { font-size: 2.2rem; font-weight: 700; color: #ffa533; margin-bottom: 32px; letter-spacing: 1px; }
        .modern-table-wrapper { background: rgba(255,255,255,0.03); border-radius: 20px; box-shadow: 0 2px 16px rgba(255,140,0,0.07); padding: 24px 16px 16px 16px; margin-bottom: 0; }
        .modern-table { border-radius: 18px !important; overflow: hidden; box-shadow: 0 2px 12px rgba(255,140,0,0.06); font-size: 1.08em; }
        .modern-table th, .modern-table td { border: none !important; background: transparent !important; vertical-align: middle; font-size: 1.07em; }
        .modern-table thead th { background: rgba(255,140,0,0.08) !important; color: #ffa533; font-weight: 600; font-size: 1.12em; letter-spacing: 0.5px; }
        .modern-table tbody tr { transition: background 0.2s; }
        .modern-table tbody tr:hover { background: rgba(255,140,0,0.07) !important; }
        @media (max-width: 991px) { .guests-container { margin-left: 0; padding: 18px 4px; } }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="guests-container">
    <div class="guests-title">Guests</div>
    <div class="modern-table-wrapper mt-4">
        <div class="table-responsive">
            <table class="table table-dark table-striped table-bordered modern-table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($guests): foreach ($guests as $i => $g): ?>
                    <tr>
                        <td><?php echo $i+1; ?></td>
                        <td><?php echo htmlspecialchars(trim($g['first_name'] . ' ' . $g['middle_name'] . ' ' . $g['last_name'])); ?></td>
                        <td><?php echo htmlspecialchars($g['user_email']); ?></td>
                        <td><?php echo htmlspecialchars($g['phone_number']); ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm see-info-btn" data-guest-id="<?php echo $g['guest_id']; ?>">
                                <i class="bi bi-eye"></i> See Information
                            </button>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-center text-secondary">No guests found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="guestInfoModal" tabindex="-1" aria-labelledby="guestInfoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header">
        <h5 class="modal-title" id="guestInfoModalLabel">Guest Information</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="guest-info-modal-body">
        <div class="text-center text-secondary py-5">
            <div class="spinner-border text-warning" role="status"></div>
            <div>Loading...</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('guestInfoModal'));
    document.querySelectorAll('.see-info-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const guestId = this.getAttribute('data-guest-id');
            const modalBody = document.getElementById('guest-info-modal-body');
            modalBody.innerHTML = `<div class='text-center text-secondary py-5'><div class='spinner-border text-warning' role='status'></div><div>Loading...</div></div>`;
            modal.show();
            fetch('ajax_guest_info.php?guest_id=' + guestId)
                .then(res => res.text())
                .then(html => {
                    modalBody.innerHTML = html;
                    if (window.setupHistoryDropdown) window.setupHistoryDropdown();
                })
                .catch(() => {
                    modalBody.innerHTML = '<div class="text-danger">Failed to load guest information.</div>';
                });
        });
    });
});

function setupHistoryDropdown() {
    var historyType = document.getElementById('historyType');
    var reservationTable = document.getElementById('reservation-history-table');
    var paymentTable = document.getElementById('payment-history-table');
    if (historyType) {
        // Remove previous event listeners by cloning
        var newHistoryType = historyType.cloneNode(true);
        historyType.parentNode.replaceChild(newHistoryType, historyType);
        newHistoryType.value = "reservation";
        reservationTable.style.display = '';
        paymentTable.style.display = 'none';
        newHistoryType.addEventListener('change', function() {
            if (this.value === 'reservation') {
                reservationTable.style.display = '';
                paymentTable.style.display = 'none';
            } else {
                reservationTable.style.display = 'none';
                paymentTable.style.display = '';
            }
        });
    }
}
</script>
</body>
</html> 