<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403); exit();
}
include '../functions/db_connect.php';
$start = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-13 days'));
$end = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');
$start_esc = mysqli_real_escape_string($mycon, $start);
$end_esc = mysqli_real_escape_string($mycon, $end);
// KPIs
$total_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE DATE(date_created) BETWEEN '$start_esc' AND '$end_esc'"))[0];
$pending_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'pending' AND DATE(date_created) BETWEEN '$start_esc' AND '$end_esc'"))[0];
$approved_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'approved' AND DATE(date_created) BETWEEN '$start_esc' AND '$end_esc'"))[0];
$cancelled_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'cancelled' AND DATE(date_created) BETWEEN '$start_esc' AND '$end_esc'"))[0];
$completed_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'completed' AND DATE(date_created) BETWEEN '$start_esc' AND '$end_esc'"))[0];
// Trends data
$labels = [];
$data = [];
$period = (strtotime($end) - strtotime($start)) / 86400;
for ($i = 0; $i <= $period; $i++) {
  $date = date('Y-m-d', strtotime("$start +$i days"));
  $labels[] = date('M d', strtotime($date));
  $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE DATE(date_created) = '$date'"))[0];
  $data[] = (int)$count;
}
// Output HTML (same structure as in reports.php, but only the inner content)
?>
<div class="row mb-4 g-3">
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-label">Total Reservations</div><div class="kpi-value"><?php echo $total_reservations; ?></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-label">Pending</div><div class="kpi-value"><?php echo $pending_reservations; ?></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-label">Approved</div><div class="kpi-value"><?php echo $approved_reservations; ?></div></div></div>
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-label">Completed</div><div class="kpi-value"><?php echo $completed_reservations; ?></div></div></div>
</div>
<div class="row mb-4 g-3">
  <div class="col-md-3"><div class="kpi-card"><div class="kpi-label">Cancelled</div><div class="kpi-value"><?php echo $cancelled_reservations; ?></div></div></div>
</div>
<div class="row mb-4">
  <div class="col-lg-5 col-md-7 mx-auto">
    <div class="card shadow mb-4" style="max-width:400px;width:100%;margin:auto;">
      <div class="card-body">
        <h5 class="card-title mb-4"><i class="bi bi-pie-chart-fill"></i> Reservations by Status</h5>
        <canvas id="reservationStatusChart" height="140" style="max-width:320px;"></canvas>
        <div class="form-check form-switch mt-3">
          <input class="form-check-input" type="checkbox" id="toggleStatusChartType">
          <label class="form-check-label" for="toggleStatusChartType" style="color:#ffa533;">Bar Chart</label>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="row mb-4">
  <div class="col-12">
    <div class="card mb-3"><div class="card-body"><h5 class="card-title"><i class="bi bi-graph-up"></i> Reservation Trends</h5><canvas id="reservationTrendsChart" height="120" style="max-width:600px;"></canvas></div></div>
  </div>
</div>
<div class="row mb-4">
  <div class="col-lg-6"><div class="card mb-3"><div class="card-body"><h5 class="card-title"><i class="bi bi-table"></i> Reservation Summary</h5>
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle text-center rounded-4 overflow-hidden" style="background:#23234a;color:#fff;">
      <thead class="table-dark rounded-top-4">
        <tr>
          <th>Status</th>
          <th>Count</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $statuses = ['pending','approved','cancelled','completed'];
        $status_labels = ['pending'=>'Pending','approved'=>'Approved','cancelled'=>'Cancelled','completed'=>'Completed'];
        foreach ($statuses as $status) {
          $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = '$status' AND DATE(date_created) BETWEEN '$start_esc' AND '$end_esc'"))[0];
          echo "<tr><td>".$status_labels[$status]."</td><td>".$count."</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
  <div class="mt-4">
    <h6 class="text-warning mb-2"><i class="bi bi-door-open"></i> By Room Type</h6>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle text-center rounded-4 overflow-hidden" style="background:#23234a;color:#fff;">
        <thead class="table-dark rounded-top-4">
          <tr>
            <th>Room Type</th>
            <th>Reservations</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $roomtype_res = mysqli_query($mycon, "SELECT rt.type_name, COUNT(*) as cnt FROM tbl_reservation r LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id WHERE DATE(r.date_created) BETWEEN '$start_esc' AND '$end_esc' GROUP BY rt.type_name");
          while ($row = mysqli_fetch_assoc($roomtype_res)) {
            $type = $row['type_name'] ?: 'N/A';
            echo "<tr><td>".htmlspecialchars($type)."</td><td>".$row['cnt']."</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  </div></div></div>
  <div class="col-lg-6"><div class="card mb-3"><div class="card-body"><h5 class="card-title"><i class="bi bi-star"></i> Top Guests</h5>
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle text-center rounded-4 overflow-hidden" style="background:#23234a;color:#fff;">
      <thead class="table-dark rounded-top-4">
        <tr>
          <th>Guest Name</th>
          <th>Email</th>
          <th>Total Bookings</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $top_guests_res = mysqli_query($mycon, "SELECT g.first_name, g.last_name, g.user_email, COUNT(r.reservation_id) as total FROM tbl_guest g JOIN tbl_reservation r ON g.guest_id = r.guest_id WHERE DATE(r.date_created) BETWEEN '$start_esc' AND '$end_esc' GROUP BY g.guest_id ORDER BY total DESC LIMIT 5");
        while ($row = mysqli_fetch_assoc($top_guests_res)) {
          $name = htmlspecialchars(trim($row['first_name'].' '.$row['last_name']));
          $email = htmlspecialchars($row['user_email']);
          $total = $row['total'];
          echo "<tr><td>$name</td><td>$email</td><td>$total</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
  </div></div></div>
</div>
<div class="row mb-4">
  <div class="col-12"><div class="card mb-3"><div class="card-body"><h5 class="card-title"><i class="bi bi-clock-history"></i> Recent Reservations</h5>
  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle text-center rounded-4 overflow-hidden" style="background:#23234a;color:#fff;">
      <thead class="table-dark rounded-top-4">
        <tr>
          <th>Guest</th>
          <th>Room Type</th>
          <th>Status</th>
          <th>Check-in</th>
          <th>Check-out</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $recent_res = mysqli_query($mycon, "SELECT r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, p.amount FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE DATE(r.date_created) BETWEEN '$start_esc' AND '$end_esc' ORDER BY r.date_created DESC LIMIT 10");
        while ($row = mysqli_fetch_assoc($recent_res)) {
          $guest = htmlspecialchars(trim($row['first_name'].' '.$row['last_name']));
          $room = htmlspecialchars($row['type_name']);
          $status = ucfirst($row['status']);
          $checkin = date('M d, Y', strtotime($row['check_in']));
          $checkout = date('M d, Y', strtotime($row['check_out']));
          $amount = isset($row['amount']) ? '₱'.number_format($row['amount'],2) : '-';
          echo "<tr><td>$guest</td><td>$room</td><td>$status</td><td>$checkin</td><td>$checkout</td><td>$amount</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
  </div></div></div>
</div>
<script>
// Reservation Status Chart (filtered)
const reservationStatusData = <?php
  $status_labels = ['Pending', 'Approved', 'Cancelled', 'Completed'];
  $status_colors = ['#ffc107', '#28a745', '#dc3545', '#0d6efd'];
  $status_counts = [];
  foreach ([
    'pending',
    'approved',
    'cancelled',
    'completed'
  ] as $status) {
    $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = '$status' AND DATE(date_created) BETWEEN '$start_esc' AND '$end_esc'"))[0];
    $status_counts[] = (int)$count;
  }
  echo json_encode([
    'labels' => $status_labels,
    'data' => $status_counts,
    'colors' => $status_colors
  ]);
?>;
let statusChartType = 'pie';
let reservationStatusChart = new Chart(document.getElementById('reservationStatusChart'), {
  type: statusChartType,
  data: {
    labels: reservationStatusData.labels,
    datasets: [{
      data: reservationStatusData.data,
      backgroundColor: reservationStatusData.colors,
      borderColor: '#23234a',
      borderWidth: 2
    }]
  },
  options: {
    plugins: { legend: { labels: { color: '#fff', font: { weight: 'bold' } } } }
  }
});
document.getElementById('toggleStatusChartType').addEventListener('change', function() {
  statusChartType = this.checked ? 'bar' : 'pie';
  reservationStatusChart.destroy();
  reservationStatusChart = new Chart(document.getElementById('reservationStatusChart'), {
    type: statusChartType,
    data: {
      labels: reservationStatusData.labels,
      datasets: [{
        label: 'Reservations',
        data: reservationStatusData.data,
        backgroundColor: reservationStatusData.colors,
        borderColor: '#23234a',
        borderWidth: 2,
        borderRadius: statusChartType === 'bar' ? 8 : 0
      }]
    },
    options: {
      plugins: { legend: { display: statusChartType === 'pie', labels: { color: '#fff', font: { weight: 'bold' } } } },
      scales: statusChartType === 'bar' ? { y: { beginAtZero: true, ticks: { color: '#fff' } }, x: { ticks: { color: '#fff' } } } : {}
    }
  });
});
// Reservation Trends Chart (filtered)
const reservationTrendsData = <?php echo json_encode(['labels' => $labels, 'data' => $data]); ?>;
new Chart(document.getElementById('reservationTrendsChart'), {
  type: 'line',
  data: {
    labels: reservationTrendsData.labels,
    datasets: [{
      label: 'Reservations',
      data: reservationTrendsData.data,
      borderColor: '#ffa533',
      backgroundColor: 'rgba(255,165,51,0.10)',
      tension: 0.4,
      fill: true,
      pointRadius: 4,
      pointBackgroundColor: '#ffa533',
      pointBorderColor: '#fff',
      pointHoverRadius: 6
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { color: '#fff' } }, x: { ticks: { color: '#fff' } } }
  }
});
</script> 