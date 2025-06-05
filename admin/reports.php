<?php
session_start();
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: admin_login.php');
    exit();
}
include '../functions/db_connect.php';
// --- Reservation KPIs ---
$total_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation"))[0];
$pending_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'pending'"))[0];
$approved_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'approved'"))[0];
$cancelled_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'cancelled'"))[0];
$completed_reservations = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'completed'"))[0];
// --- Payment KPIs ---
$total_revenue = mysqli_fetch_row(mysqli_query($mycon, "SELECT SUM(amount) FROM tbl_payment WHERE payment_status = 'Paid'"))[0] ?? 0;
$paid_payments = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_payment WHERE payment_status = 'Paid'"))[0];
$unpaid_payments = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_payment WHERE payment_status = 'Unpaid'"))[0];
$refunded_payments = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_payment WHERE payment_status = 'Refunded'"))[0];
// --- PHP DATA FOR CHARTS ---
// 1. Daily Income (last 14 days)
$daily_income_labels = [];
$daily_income_data = [];
for ($i = 13; $i >= 0; $i--) {
  $date = date('Y-m-d', strtotime("-$i days"));
  $daily_income_labels[] = date('M d', strtotime($date));
  $sum = mysqli_fetch_row(mysqli_query($mycon, "SELECT SUM(amount) FROM tbl_payment WHERE payment_status = 'Paid' AND DATE(payment_created) = '$date'"))[0] ?? 0;
  $daily_income_data[] = floatval($sum);
}
// 2. Payment Methods Used (for paid payments)
$methods_res = mysqli_query($mycon, "SELECT payment_method, COUNT(*) as cnt FROM tbl_payment WHERE payment_status = 'Paid' GROUP BY payment_method");
$payment_methods_labels = [];
$payment_methods_data = [];
while ($row = mysqli_fetch_assoc($methods_res)) {
  $payment_methods_labels[] = $row['payment_method'] ?: 'N/A';
  $payment_methods_data[] = intval($row['cnt']);
}
// 3. Top 5 Customers by total paid amount
$top_customers_res = mysqli_query($mycon, "SELECT g.first_name, g.last_name, SUM(p.amount) as total FROM tbl_guest g JOIN tbl_reservation r ON g.guest_id = r.guest_id JOIN tbl_payment p ON r.payment_id = p.payment_id WHERE p.payment_status = 'Paid' GROUP BY g.guest_id ORDER BY total DESC LIMIT 5");
$top_customers_labels = [];
$top_customers_data = [];
while ($row = mysqli_fetch_assoc($top_customers_res)) {
  $top_customers_labels[] = trim($row['first_name'].' '.$row['last_name']);
  $top_customers_data[] = floatval($row['total']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports - MF Suites Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #181818;
            color: #fff;
            font-family: 'Inter', 'Poppins', 'Segoe UI', Arial, sans-serif;
        }
        .report-container {
            margin-left: 240px;
            margin-top: 70px;
            padding: 40px 24px 24px 24px;
        }
        .report-title {
            font-size: 2.1rem;
            font-weight: 700;
            color: #ffa533;
            margin-bottom: 28px;
            letter-spacing: 0.5px;
            text-shadow: none;
        }
        .kpi-card {
            background: #232323;
            border-radius: 18px;
            box-shadow: none;
            padding: 18px 0 14px 0;
            margin-bottom: 10px;
            min-width: 100px; max-width: 140px;
            display: flex; flex-direction: column; align-items: center;
            border: 2px solid #232323;
            transition: box-shadow 0.18s, background 0.18s;
        }
        .kpi-card:hover {
            box-shadow: 0 4px 24px #ffa53322;
            background: #181818;
            border-color: #ffa533;
        }
        .kpi-label {
            color: #bdbdbd;
            font-size: 0.97em;
            margin-bottom: 2px;
            font-weight: 500;
            letter-spacing: 0.1px;
        }
        .kpi-value {
            color: #ffa533;
            font-size: 1.22rem;
            font-weight: 700;
            font-family: 'Inter', 'Poppins', Arial, sans-serif;
            letter-spacing: 0.5px;
            text-shadow: none;
        }
        .nav-tabs {
            border-bottom: none;
        }
        .nav-tabs .nav-link.active {
            background: #232323;
            color: #ffa533;
            border: none;
            font-weight: 600;
            box-shadow: none;
            border-radius: 16px 16px 0 0;
        }
        .nav-tabs .nav-link {
            color: #fff;
            border-radius: 16px 16px 0 0;
            border: none;
            margin-right: 2px;
            transition: background 0.18s;
            font-weight: 500;
        }
        .nav-tabs .nav-link:hover {
            background: #ffa53322;
            color: #ffa533;
        }
        .tab-content {
            background: #181818;
            border-radius: 0 0 18px 18px;
            box-shadow: none;
            padding: 28px 12px;
            border: none;
        }
        .card {
            background: #232323;
            border-radius: 18px;
            box-shadow: none;
            border: 2px solid #232323;
            margin-bottom: 18px;
            transition: box-shadow 0.18s, background 0.18s;
        }
        .card-title {
            color: #ffa533;
            font-size: 1.13rem;
            font-weight: 700;
            letter-spacing: 0.2px;
        }
        .card-body {
            min-height: 220px;
        }
        .table {
            background: #181818;
            border-radius: 16px;
            overflow: hidden;
            border-collapse: separate;
            border-spacing: 0;
            box-shadow: none;
        }
        .table thead {
            background: #232323;
            color: #ffa533;
            border-radius: 16px 16px 0 0;
        }
        .table thead th {
            border: none;
            font-weight: 700;
            font-size: 1.01em;
            letter-spacing: 0.2px;
            background: #232323;
            color: #ffa533;
        }
        .table tbody tr {
            transition: background 0.18s;
        }
        .table tbody tr:hover {
            background: #292929 !important;
        }
        .table td, .table th {
            border: none;
            vertical-align: middle;
            font-size: 0.98em;
            color: #232323;
        }
        .table-bordered {
            border: none;
        }
        .table-responsive {
            border-radius: 16px;
            overflow: hidden;
        }
        .form-control, .form-select {
            background: #181818;
            color: #fff;
            border: 1.5px solid #ffa53322;
            border-radius: 10px;
            font-size: 0.98em;
        }
        .form-control:focus, .form-select:focus {
            border-color: #ffa533;
            box-shadow: 0 0 0 0.10rem #ffa53333;
        }
        .btn-outline-warning, .btn-outline-danger, .btn-outline-secondary {
            border-radius: 10px;
            font-size: 0.97em;
        }
        .btn-outline-warning {
            border-color: #ffa533;
            color: #ffa533;
            background: none;
        }
        .btn-outline-warning:hover {
            background: #ffa533;
            color: #181818;
        }
        .btn-outline-danger {
            border-color: #ff4d4d;
            color: #ff4d4d;
            background: none;
        }
        .btn-outline-danger:hover {
            background: #ff4d4d;
            color: #fff;
        }
        .btn-outline-secondary {
            border-color: #bdbdbd;
            color: #bdbdbd;
            background: none;
        }
        .btn-outline-secondary:hover {
            background: #bdbdbd;
            color: #181818;
        }
        .form-check-input:checked {
            background-color: #ffa533;
            border-color: #ffa533;
        }
        .form-check-input {
            border-radius: 8px;
        }
        .flatpickr-input {
            background: #181818;
            color: #fff;
            border: 1.5px solid #ffa53322;
            border-radius: 10px;
            font-size: 0.98em;
        }
        .flatpickr-calendar {
            background: #232323;
            color: #fff;
            border-radius: 14px;
            border: 1.5px solid #ffa53333;
        }
        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
            background: #ffa533;
            color: #181818;
            border-radius: 8px;
        }
        .flatpickr-day:hover {
            background: #ffa53333;
            color: #ffa533;
        }
        /* IChart.js minimal style */
        canvas { background: none !important; border-radius: 14px; min-height: 180px; max-width: 100%; }
        .chartjs-render-monitor { box-shadow: none; }
        /* Minimal grid lines */
        .chartjs-grid { stroke: #292929 !important; }
        /* Hide chart legend if only one dataset */
        .chartjs-legend { display: none !important; }
        /* Progress bar style */
        .progress {
            background: #232323;
            border-radius: 8px;
            height: 18px;
            box-shadow: none;
        }
        .progress-bar {
            background: linear-gradient(90deg, #ffa533 60%, #ffc107 100%);
            border-radius: 8px;
            font-weight: 600;
            color: #181818;
        }
        /* Responsive tweaks */
        @media (max-width: 900px) { .report-container { margin-left: 70px; padding: 18px 4px; } }
        @media (max-width: 600px) { .kpi-card { min-width: 80px; max-width: 100px; padding: 7px 0; } .report-title { font-size: 1.2rem; } }
        .card-body.chart-card {
            height: 380px !important;
            min-height: 260px !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 18px 8px 8px 8px;
        }
        canvas {
            max-width: 100%;
            max-height: 340px;
            min-height: 180px;
        }
        .kpi-card {
            max-width: 480px !important;
            padding: 0 0 0 0;
        }
        .kpi-card .kpi-label {
            font-size: 1.15em;
        }
        .kpi-card .kpi-value {
            font-size: 2.1em;
        }
        .card-title {
            font-size: 1.45rem !important;
            padding-bottom: 10px;
        }
        .table, .table th, .table td {
            color: #232323 !important;
        }
        @media (max-width: 991px) {
          .card-body.chart-card { height: 300px !important; min-height: 180px !important; }
          canvas { max-height: 200px; }
        }
        @media (max-width: 767px) {
          .col-lg-4 { flex: 0 0 100%; max-width: 100%; }
          .card-body.chart-card { height: 220px !important; min-height: 120px !important; }
          canvas { max-height: 120px; }
        }
    </style>
</head>
<body>
<?php include './sidebar.php'; ?>
<div class="report-container">
    <div class="report-title">Admin Reports</div>
    <ul class="nav nav-tabs mb-4" id="reportTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="reservations-tab" data-bs-toggle="tab" data-bs-target="#reservations" type="button" role="tab" aria-controls="reservations" aria-selected="true"><i class="bi bi-calendar2-check"></i> Reservations</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false"><i class="bi bi-cash"></i> Payments</button>
      </li>
    </ul>
    <div class="tab-content" id="reportTabsContent">
      <!-- Reservations Tab -->
      <div class="tab-pane fade show active" id="reservations" role="tabpanel" aria-labelledby="reservations-tab">
        <div class="d-flex flex-wrap align-items-center mb-3 gap-3">
          <div>
            <label for="reservationDateRange" class="form-label mb-0 me-2 text-warning"><i class="bi bi-calendar-range"></i> Date Range:</label>
            <input type="text" id="reservationDateRange" class="form-control d-inline-block" style="width:220px;display:inline-block;" autocomplete="off">
          </div>
        </div>
        <div id="reservationsReportContent">
        <div class="row mb-4 g-3 align-items-stretch">
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow mb-4" style="width:100%;margin:auto;">
              <div class="card-body chart-card" style="height:380px;min-height:260px;">
                <div class="d-flex justify-content-between align-items-center mb-2 w-100">
                  <h5 class="card-title mb-0" style="font-size:1.18rem;"><i class="bi bi-graph-up"></i> Bookings Over Time</h5>
                  <select id="bookingsRangeSelect" class="form-select form-select-sm ms-2" style="width:auto;min-width:120px;">
                    <option value="7">Last 7 Days</option>
                    <option value="14">Last 14 Days</option>
                    <option value="12m">Last 12 Months</option>
                  </select>
                </div>
                <canvas id="bookingsLineChart" height="260"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-6 mb-4">
            <div class="card shadow mb-4" style="width:100%;margin:auto;">
              <div class="card-body chart-card" style="height:380px;min-height:260px;">
                <h5 class="card-title mb-3" style="font-size:1.18rem;"><i class="bi bi-bar-chart-steps"></i> Bookings by Room Type (Monthly)</h5>
                <canvas id="roomTypesBarChart" height="260"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-4 col-md-12 mb-4">
            <div class="card shadow mb-4" style="width:100%;margin:auto;">
              <div class="card-body chart-card" style="height:380px;min-height:260px;">
                <h5 class="card-title mb-3" style="font-size:1.18rem;"><i class="bi bi-pie-chart-fill"></i> Reservations by Status</h5>
                <canvas id="reservationStatusChart"></canvas>
                <div class="form-check form-switch mt-2">
                  <input class="form-check-input" type="checkbox" id="toggleStatusChartType">
                  <label class="form-check-label" for="toggleStatusChartType" style="color:#ffa533;font-size:0.95em;">Bar Chart</label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row mb-4">
          <div class="col-lg-6"><div class="card mb-3"><div class="card-body"><h5 class="card-title d-flex justify-content-between align-items-center"><span><i class="bi bi-table"></i> Reservation Summary</span>
            <span>
              <button class="btn btn-sm btn-outline-warning me-1 export-csv-btn" data-table="reservation-summary-table"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
              <button class="btn btn-sm btn-outline-danger export-pdf-btn" data-table="reservation-summary-table"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            </span>
          </h5>
          <div class="table-responsive">
            <table id="reservation-summary-table" class="table table-bordered table-hover align-middle text-center rounded-4 overflow-hidden" style="background:#23234a;color:#fff;">
              <thead>
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
                  $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = '$status'"))[0];
                  echo "<tr><td>".$status_labels[$status]."</td><td>".$count."</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="mt-4">
            <h6 class="text-warning mb-2"><i class="bi bi-door-open"></i> By Room Type</h6>
            <div class="table-responsive">
              <table class="table table-bordered table-hover align-middle text-center" style="background:#23234a;color:#fff;">
                <thead>
                  <tr>
                    <th>Room Type</th>
                    <th>Reservations</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $roomtype_res = mysqli_query($mycon, "SELECT rt.type_name, COUNT(*) as cnt FROM tbl_reservation r LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id GROUP BY rt.type_name");
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
          <div class="col-lg-6"><div class="card mb-3"><div class="card-body"><h5 class="card-title d-flex justify-content-between align-items-center"><span><i class="bi bi-star"></i> Top Guests</span>
            <span>
              <button class="btn btn-sm btn-outline-warning me-1 export-csv-btn" data-table="top-guests-table"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
              <button class="btn btn-sm btn-outline-danger export-pdf-btn" data-table="top-guests-table"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            </span>
          </h5>
          <div class="table-responsive">
            <table id="top-guests-table" class="table table-bordered table-hover align-middle text-center rounded-4 overflow-hidden" style="background:#23234a;color:#fff;">
              <thead>
                <tr>
                  <th>Guest Name</th>
                  <th>Email</th>
                  <th>Total Bookings</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $top_guests_res = mysqli_query($mycon, "SELECT g.first_name, g.last_name, g.user_email, COUNT(r.reservation_id) as total FROM tbl_guest g JOIN tbl_reservation r ON g.guest_id = r.guest_id GROUP BY g.guest_id ORDER BY total DESC LIMIT 5");
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
          <div class="col-12"><div class="card mb-3"><div class="card-body"><h5 class="card-title d-flex justify-content-between align-items-center"><span><i class="bi bi-clock-history"></i> Recent Reservations</span>
            <span>
              <button class="btn btn-sm btn-outline-warning me-1 export-csv-btn" data-table="recent-reservations-table"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
              <button class="btn btn-sm btn-outline-danger export-pdf-btn" data-table="recent-reservations-table"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            </span>
          </h5>
          <div class="table-responsive">
            <table id="recent-reservations-table" class="table table-bordered table-hover align-middle text-center rounded-4 overflow-hidden" style="background:#23234a;color:#fff;">
              <thead>
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
                $recent_res = mysqli_query($mycon, "SELECT r.check_in, r.check_out, r.status, g.first_name, g.last_name, rt.type_name, p.amount FROM tbl_reservation r LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id LEFT JOIN tbl_room rm ON r.room_id = rm.room_id LEFT JOIN tbl_room_type rt ON rm.room_type_id = rt.room_type_id LEFT JOIN tbl_payment p ON r.payment_id = p.payment_id ORDER BY r.date_created DESC LIMIT 10");
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
        </div>
        </div>
      </div>
      <!-- Payments Tab -->
      <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
        <div class="row mb-4 g-3">
          <div class="col-md-3"><div class="kpi-card"><div class="kpi-label">Total Revenue</div><div class="kpi-value">₱<?php echo number_format($total_revenue,2); ?></div></div></div>
          <div class="col-md-3"><div class="kpi-card"><div class="kpi-label">Paid</div><div class="kpi-value"><?php echo $paid_payments; ?></div></div></div>
          <div class="col-md-3"><div class="kpi-card"><div class="kpi-label">Unpaid</div><div class="kpi-value"><?php echo $unpaid_payments; ?></div></div></div>
          <div class="col-md-3"><div class="kpi-card"><div class="kpi-label">Refunded</div><div class="kpi-value"><?php echo $refunded_payments; ?></div></div></div>
        </div>
        
        <div class="row mb-4">
          <!-- Daily Income Line Chart -->
          <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4" style="width:100%;margin:auto;">
              <div class="card-body chart-card">
                <h5 class="card-title mb-4"><i class="bi bi-graph-up"></i> Daily Income (Last 14 Days)</h5>
                <canvas id="dailyIncomeChart" height="120"></canvas>
              </div>
            </div>
          </div>
          <!-- Payment Methods Pie Chart -->
          <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4" style="width:100%;margin:auto;">
              <div class="card-body chart-card">
                <h5 class="card-title mb-4"><i class="bi bi-pie-chart"></i> Payment Methods Used</h5>
                <canvas id="paymentMethodsChart" height="120"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="row mb-4">
          <!-- Top 5 Customers Bar Chart -->
          <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4" style="width:100%;margin:auto;">
              <div class="card-body chart-card">
                <h5 class="card-title mb-4"><i class="bi bi-bar-chart"></i> Top 5 Customers (by Paid Amount)</h5>
                <canvas id="topCustomersChart" height="120"></canvas>
              </div>
            </div>
          </div>
          <!-- Payment Status Donut Chart (already present, just ensure it's styled as a donut) -->
          <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4" style="width:100%;margin:auto;">
              <div class="card-body">
                <h5 class="card-title mb-4"><i class="bi bi-pie-chart-fill"></i> Payment Statuses</h5>
                <canvas id="paymentStatusChart"></canvas>
                <div class="form-check form-switch mt-3">
                  <input class="form-check-input" type="checkbox" id="togglePaymentStatusChartType">
                  <label class="form-check-label" for="togglePaymentStatusChartType" style="color:#ffa533;">Bar Chart</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      
        <div class="row mb-4">
          <div class="col-lg-6"><div class="card mb-3"><div class="card-body"><h5 class="card-title d-flex justify-content-between align-items-center"><span><i class="bi bi-table"></i> Payment Summary</span>
            <span>
              <button class="btn btn-sm btn-outline-warning me-1 export-csv-btn" data-table="payment-summary-table"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
              <button class="btn btn-sm btn-outline-danger export-pdf-btn" data-table="payment-summary-table"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            </span>
          </h5>
          <div class="table-responsive mb-4">
            <table id="payment-summary-table" class="table table-bordered table-hover align-middle text-center rounded-4 overflow-hidden" style="background:#23234a;color:#fff;">
              <thead>
                <tr>
                  <th>Status</th>
                  <th>Count</th>
                  <th>Total Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $statuses = ['Paid','Unpaid','Refunded'];
                foreach ($statuses as $status) {
                  $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_payment WHERE payment_status = '$status'"))[0];
                  $sum = mysqli_fetch_row(mysqli_query($mycon, "SELECT SUM(amount) FROM tbl_payment WHERE payment_status = '$status'"))[0] ?? 0;
                  echo "<tr><td>$status</td><td>$count</td><td>₱".number_format($sum,2)."</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          <div class="mt-4">
            <h6 class="text-warning mb-2"><i class="bi bi-credit-card"></i> By Payment Method
              <span class="float-end">
                <button class="btn btn-sm btn-outline-warning me-1 export-csv-btn" data-table="payment-method-table"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
                <button class="btn btn-sm btn-outline-danger export-pdf-btn" data-table="payment-method-table"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
              </span>
            </h6>
            <div class="table-responsive">
              <table id="payment-method-table" class="table table-bordered table-hover align-middle text-center rounded-4 overflow-hidden" style="background:#23234a;color:#fff;">
                <thead>
                  <tr>
                    <th>Payment Method</th>
                    <th>Count</th>
                    <th>Total Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $method_res = mysqli_query($mycon, "SELECT payment_method, COUNT(*) as cnt, SUM(amount) as total FROM tbl_payment GROUP BY payment_method");
                  while ($row = mysqli_fetch_assoc($method_res)) {
                    $method = $row['payment_method'] ?: 'N/A';
                    $cnt = $row['cnt'];
                    $total = $row['total'] ?? 0;
                    echo "<tr><td>".htmlspecialchars($method)."</td><td>$cnt</td><td>₱".number_format($total,2)."</td></tr>";
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
          </div></div></div>
          <div class="col-lg-6"><div class="card mb-3"><div class="card-body"><h5 class="card-title d-flex justify-content-between align-items-center"><span><i class="bi bi-clock-history"></i> Recent Payments</span>
            <span>
              <button class="btn btn-sm btn-outline-warning me-1 export-csv-btn" data-table="recent-payments-table"><i class="bi bi-file-earmark-spreadsheet"></i> CSV</button>
              <button class="btn btn-sm btn-outline-danger export-pdf-btn" data-table="recent-payments-table"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            </span>
          </h5>
          <div class="table-responsive">
            <table id="recent-payments-table" class="table table-bordered table-hover align-middle text-center rounded-4 overflow-hidden" style="background:#23234a;color:#fff;">
              <thead>
                <tr>
                  <th>Guest</th>
                  <th>Amount</th>
                  <th>Method</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $recent_pay = mysqli_query($mycon, "SELECT p.amount, p.payment_method, p.payment_status, p.payment_created, g.first_name, g.last_name FROM tbl_payment p LEFT JOIN tbl_reservation r ON p.payment_id = r.payment_id LEFT JOIN tbl_guest g ON r.guest_id = g.guest_id ORDER BY p.payment_created DESC LIMIT 10");
                while ($row = mysqli_fetch_assoc($recent_pay)) {
                  $guest = htmlspecialchars(trim(($row['first_name'] ?? '').' '.($row['last_name'] ?? '')));
                  $amount = isset($row['amount']) ? '₱'.number_format($row['amount'],2) : '-';
                  $method = htmlspecialchars($row['payment_method'] ?? '-');
                  $status = htmlspecialchars($row['payment_status'] ?? '-');
                  $date = $row['payment_created'] ? date('M d, Y h:i A', strtotime($row['payment_created'])) : '-';
                  echo "<tr><td>$guest</td><td>$amount</td><td>$method</td><td>$status</td><td>$date</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
          </div></div></div>
        </div>
      </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.7.0/dist/jspdf.plugin.autotable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
<script>
// Date range picker setup
flatpickr("#reservationDateRange", {
  mode: "range",
  dateFormat: "Y-m-d",
  defaultDate: [new Date(new Date().setDate(new Date().getDate()-13)), new Date()],
  onClose: function(selectedDates, dateStr, instance) {
    if (selectedDates.length === 2) {
      fetchReservationsReport(selectedDates[0], selectedDates[1]);
    }
  }
});
function fetchReservationsReport(startDate, endDate) {
  const params = new URLSearchParams({ start: startDate.toISOString().slice(0,10), end: endDate.toISOString().slice(0,10) });
  fetch('ajax_report_reservations.php?' + params)
    .then(res => res.text())
    .then(html => {
      document.getElementById('reservationsReportContent').innerHTML = html;
    });
}
// Reservation Status Chart
const reservationStatusData = {
  labels: ['Pending', 'Approved', 'Cancelled', 'Completed'],
  data: [
    <?php echo intval(mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'pending'"))[0]); ?>,
    <?php echo intval(mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'approved'"))[0]); ?>,
    <?php echo intval(mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'cancelled'"))[0]); ?>,
    <?php echo intval(mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE status = 'completed'"))[0]); ?>
  ],
  colors: ['#ffc107', '#2ecc71', '#ff4d4d', '#0d6efd']
};
let statusChartType = 'doughnut';
let reservationStatusChart = new Chart(document.getElementById('reservationStatusChart'), {
  type: statusChartType,
  data: {
    labels: reservationStatusData.labels,
    datasets: [{
      data: reservationStatusData.data,
      backgroundColor: reservationStatusData.colors,
      borderColor: '#181818',
      borderWidth: 4,
      hoverOffset: 10
    }]
  },
  options: {
    cutout: '78%',
    plugins: {
      legend: {
        display: true,
        labels: {
          color: '#ffa533',
          font: { weight: '600', size: 14, family: 'Inter, Poppins, Arial' },
          padding: 18
        }
      },
      tooltip: {
        backgroundColor: '#232323',
        titleColor: '#ffa533',
        bodyColor: '#fff',
        borderColor: '#ffa533',
        borderWidth: 1.5,
        cornerRadius: 10,
        padding: 12,
        titleFont: { weight: '700', size: 15 },
        bodyFont: { size: 14 }
      }
    },
    layout: { padding: 8 },
    animation: { duration: 900, easing: 'easeOutQuart' },
    responsive: true,
    maintainAspectRatio: false,
    backgroundColor: '#181818'
  }
});
document.getElementById('toggleStatusChartType').addEventListener('change', function() {
  statusChartType = this.checked ? 'bar' : 'doughnut';
  reservationStatusChart.destroy();
  reservationStatusChart = new Chart(document.getElementById('reservationStatusChart'), {
    type: statusChartType,
    data: {
      labels: reservationStatusData.labels,
      datasets: [{
        label: 'Reservations',
        data: reservationStatusData.data,
        backgroundColor: reservationStatusData.colors,
        borderColor: statusChartType === 'bar' ? '#ffa533' : '#181818',
        borderWidth: 4,
        borderRadius: statusChartType === 'bar' ? 14 : 0,
        hoverOffset: statusChartType === 'doughnut' ? 10 : 0
      }]
    },
    options: Object.assign({}, statusChartType === 'bar' ? {
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#232323',
          titleColor: '#ffa533',
          bodyColor: '#fff',
          borderColor: '#ffa533',
          borderWidth: 1.5,
          cornerRadius: 10,
          padding: 12,
          titleFont: { weight: '700', size: 15 },
          bodyFont: { size: 14 }
        }
      },
      layout: { padding: 8 },
      animation: { duration: 900, easing: 'easeOutQuart' },
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: '#232323' },
          ticks: { color: '#ffa533', font: { size: 13 } }
        },
        x: {
          grid: { display: false },
          ticks: { color: '#ffa533', font: { size: 13 } }
        }
      },
      backgroundColor: '#181818'
    } : {
      cutout: '78%',
      plugins: {
        legend: {
          display: true,
          labels: {
            color: '#ffa533',
            font: { weight: '600', size: 14, family: 'Inter, Poppins, Arial' },
            padding: 18
          }
        },
        tooltip: {
          backgroundColor: '#232323',
          titleColor: '#ffa533',
          bodyColor: '#fff',
          borderColor: '#ffa533',
          borderWidth: 1.5,
          cornerRadius: 10,
          padding: 12,
          titleFont: { weight: '700', size: 15 },
          bodyFont: { size: 14 }
        }
      },
      layout: { padding: 8 },
      animation: { duration: 900, easing: 'easeOutQuart' },
      responsive: true,
      maintainAspectRatio: false,
      backgroundColor: '#181818'
    })
  });
});
// Payment Status Chart
const paymentStatusData = {
  labels: ['Paid', 'Unpaid', 'Refunded'],
  data: [
    <?php echo intval(mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_payment WHERE payment_status = 'Paid'"))[0]); ?>,
    <?php echo intval(mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_payment WHERE payment_status = 'Unpaid'"))[0]); ?>,
    <?php echo intval(mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_payment WHERE payment_status = 'Refunded'"))[0]); ?>
  ],
  colors: ['#2ecc71', '#ffc107', '#6c757d']
};
let paymentChartType = 'doughnut';
let paymentStatusChart = new Chart(document.getElementById('paymentStatusChart'), {
  type: paymentChartType,
  data: {
    labels: paymentStatusData.labels,
    datasets: [{
      data: paymentStatusData.data,
      backgroundColor: paymentStatusData.colors,
      borderColor: '#181818',
      borderWidth: 4,
      hoverOffset: 10
    }]
  },
  options: {
    cutout: '75%',
    plugins: {
      legend: {
        display: true,
        labels: {
          color: '#ffa533',
          font: { weight: '600', size: 14, family: 'Inter, Poppins, Arial' },
          padding: 18
        }
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            const total = context.dataset.data.reduce((a, b) => a + b, 0);
            const value = context.parsed;
            const percent = total ? ((value / total) * 100).toFixed(1) : 0;
            return `${context.label}: ${value} (${percent}%)`;
          }
        },
        backgroundColor: '#232323',
        titleColor: '#ffa533',
        bodyColor: '#fff',
        borderColor: '#ffa533',
        borderWidth: 1.5,
        cornerRadius: 10,
        padding: 12,
        titleFont: { weight: '700', size: 15 },
        bodyFont: { size: 14 }
      },
      datalabels: {
        color: '#fff',
        font: { weight: 'bold', size: 16 },
        formatter: function(value, context) {
          const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
          const percent = total ? ((value / total) * 100).toFixed(1) : 0;
          return value + '\n' + percent + '%';
        }
      }
    },
    layout: { padding: 8 },
    animation: { duration: 900, easing: 'easeOutQuart' },
    responsive: true,
    maintainAspectRatio: false,
    backgroundColor: '#181818'
  },
  plugins: [ChartDataLabels]
});
document.getElementById('togglePaymentStatusChartType').addEventListener('change', function() {
  paymentChartType = this.checked ? 'bar' : 'doughnut';
  paymentStatusChart.destroy();
  paymentStatusChart = new Chart(document.getElementById('paymentStatusChart'), {
    type: paymentChartType,
    data: {
      labels: paymentStatusData.labels,
      datasets: [{
        data: paymentStatusData.data,
        backgroundColor: paymentStatusData.colors,
        borderColor: '#181818',
        borderWidth: 4,
        hoverOffset: 10
      }]
    },
    options: {
      cutout: '75%',
      plugins: {
        legend: {
          display: true,
          labels: {
            color: '#ffa533',
            font: { weight: '600', size: 14, family: 'Inter, Poppins, Arial' },
            padding: 18
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const total = context.dataset.data.reduce((a, b) => a + b, 0);
              const value = context.parsed;
              const percent = total ? ((value / total) * 100).toFixed(1) : 0;
              return `${context.label}: ${value} (${percent}%)`;
            }
          },
          backgroundColor: '#232323',
          titleColor: '#ffa533',
          bodyColor: '#fff',
          borderColor: '#ffa533',
          borderWidth: 1.5,
          cornerRadius: 10,
          padding: 12,
          titleFont: { weight: '700', size: 15 },
          bodyFont: { size: 14 }
        },
        datalabels: {
          color: '#fff',
          font: { weight: 'bold', size: 16 },
          formatter: function(value, context) {
            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
            const percent = total ? ((value / total) * 100).toFixed(1) : 0;
            return value + '\n' + percent + '%';
          }
        }
      },
      layout: { padding: 8 },
      animation: { duration: 900, easing: 'easeOutQuart' },
      responsive: true,
      maintainAspectRatio: false,
      backgroundColor: '#181818'
    },
    plugins: [ChartDataLabels]
  });
});
// Bookings Over Time Chart Data
const bookings7Data = <?php
  $labels = [];
  $data = [];
  for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('D', strtotime($date));
    $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE DATE(date_created) = '$date'"))[0];
    $data[] = (int)$count;
  }
  echo json_encode(['labels' => $labels, 'data' => $data]);
?>;
const bookings14Data = <?php
  $labels = [];
  $data = [];
  for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('M d', strtotime($date));
    $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE DATE(date_created) = '$date'"))[0];
    $data[] = (int)$count;
  }
  echo json_encode(['labels' => $labels, 'data' => $data]);
?>;
const bookings12mData = <?php
  $labels = [];
  $data = [];
  for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $labels[] = date('M Y', strtotime($month.'-01'));
    $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation WHERE DATE_FORMAT(date_created, '%Y-%m') = '$month'"))[0];
    $data[] = (int)$count;
  }
  echo json_encode(['labels' => $labels, 'data' => $data]);
?>;
let bookingsLineChart = new Chart(document.getElementById('bookingsLineChart'), {
  type: 'line',
  data: {
    labels: bookings7Data.labels,
    datasets: [{
      label: 'Bookings',
      data: bookings7Data.data,
      borderColor: '#ffa533',
      backgroundColor: 'rgba(255,165,51,0.15)',
      tension: 0.4,
      fill: true,
      pointRadius: 7,
      pointBackgroundColor: '#ffa533',
      pointBorderColor: '#fff',
      pointHoverRadius: 10
    }]
  },
  options: {
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#232323',
        titleColor: '#ffa533',
        bodyColor: '#fff',
        borderColor: '#ffa533',
        borderWidth: 2,
        cornerRadius: 12,
        padding: 16,
        titleFont: { weight: '700', size: 20 },
        bodyFont: { size: 18 }
      }
    },
    scales: {
      y: { beginAtZero: true, ticks: { color: '#fff', font: { size: 18 } } },
      x: { ticks: { color: '#fff', font: { size: 18 } } }
    }
  }
});
document.getElementById('bookingsRangeSelect').addEventListener('change', function() {
  let val = this.value;
  if (val === '7') {
    bookingsLineChart.data.labels = bookings7Data.labels;
    bookingsLineChart.data.datasets[0].data = bookings7Data.data;
  } else if (val === '14') {
    bookingsLineChart.data.labels = bookings14Data.labels;
    bookingsLineChart.data.datasets[0].data = bookings14Data.data;
  } else if (val === '12m') {
    bookingsLineChart.data.labels = bookings12mData.labels;
    bookingsLineChart.data.datasets[0].data = bookings12mData.data;
  }
  bookingsLineChart.update();
});
// Bookings by Room Type Stacked Bar Chart Data (last 12 months)
const roomTypesBarData = <?php
  // Get all room types
  $room_types = [];
  $rt_res = mysqli_query($mycon, "SELECT room_type_id, type_name FROM tbl_room_type ORDER BY type_name");
  while ($row = mysqli_fetch_assoc($rt_res)) {
    $room_types[$row['room_type_id']] = $row['type_name'];
  }
  // Get last 12 months
  $months = [];
  for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $months[] = $month;
  }
  // Build datasets for each room type
  $datasets = [];
  $colors = [];
  foreach ($room_types as $rt_id => $rt_name) {
    $data = [];
    foreach ($months as $month) {
      $count = mysqli_fetch_row(mysqli_query($mycon, "SELECT COUNT(*) FROM tbl_reservation r LEFT JOIN tbl_room rm ON r.room_id = rm.room_id WHERE rm.room_type_id = $rt_id AND DATE_FORMAT(r.date_created, '%Y-%m') = '$month'"))[0];
      $data[] = (int)$count;
    }
    $color = '#' . substr(md5($rt_name), 0, 6);
    $datasets[] = [
      'label' => $rt_name,
      'data' => $data,
      'backgroundColor' => $color,
      'borderColor' => $color,
      'borderWidth' => 1
    ];
    $colors[] = $color;
  }
  $labels = array_map(function($m) { return date('M Y', strtotime($m.'-01')); }, $months);
  echo json_encode(['labels' => $labels, 'datasets' => $datasets]);
?>;
new Chart(document.getElementById('roomTypesBarChart'), {
  type: 'bar',
  data: {
    labels: roomTypesBarData.labels,
    datasets: roomTypesBarData.datasets
  },
  options: {
    plugins: {
      legend: { labels: { color: '#fff', font: { weight: 'bold', size: 16 } } },
      tooltip: {
        backgroundColor: '#232323',
        titleColor: '#ffa533',
        bodyColor: '#fff',
        borderColor: '#ffa533',
        borderWidth: 2,
        cornerRadius: 12,
        padding: 16,
        titleFont: { weight: '700', size: 18 },
        bodyFont: { size: 16 }
      }
    },
    responsive: true,
    maintainAspectRatio: false,
    scales: {
      x: {
        stacked: true,
        ticks: { color: '#fff', font: { size: 15 } },
        grid: { color: '#292929' }
      },
      y: {
        stacked: true,
        beginAtZero: true,
        ticks: { color: '#fff', font: { size: 15 } },
        grid: { color: '#292929' }
      }
    }
  }
});
document.querySelectorAll('.export-csv-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const tableId = this.getAttribute('data-table');
    exportTableToCSV(document.getElementById(tableId));
  });
});
document.querySelectorAll('.export-pdf-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const tableId = this.getAttribute('data-table');
    exportTableToPDF(document.getElementById(tableId));
  });
});
function exportTableToCSV(table) {
  let csv = [];
  for (let row of table.rows) {
    let rowData = [];
    for (let cell of row.cells) {
      rowData.push('"' + cell.innerText.replace(/"/g, '""') + '"');
    }
    csv.push(rowData.join(","));
  }
  const csvContent = csv.join("\n");
  const blob = new Blob([csvContent], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = (table.id || 'table') + '.csv';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}
function exportTableToPDF(table) {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  doc.autoTable({ html: table, theme: 'grid', styles: { fillColor: [35,35,74], textColor: [255,255,255], lineColor: [255,165,51] }, headStyles: { fillColor: [255,140,0], textColor: [35,35,74] }, margin: { top: 20 } });
  doc.save((table.id || 'table') + '.pdf');
}
// --- Interactive Chart Filtering for Reservations ---
let activeReservationStatusFilter = null;
reservationStatusChart.options.onClick = function(evt, elements) {
  if (elements.length > 0) {
    const idx = elements[0].index;
    const status = reservationStatusData.labels[idx];
    filterRecentReservationsByStatus(status);
    setActiveStatusFilterBtn(status);
  }
};
function filterRecentReservationsByStatus(status) {
  const table = document.getElementById('recent-reservations-table');
  if (!table) return;
  for (let i = 1; i < table.rows.length; i++) {
    const row = table.rows[i];
    const cell = row.cells[2]; // Status column
    if (!cell) continue;
    if (status === null || cell.innerText.trim().toLowerCase() === status.toLowerCase()) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  }
  activeReservationStatusFilter = status;
  document.getElementById('clearReservationStatusFilterBtn').style.display = '';
}
function setActiveStatusFilterBtn(status) {
  const btn = document.getElementById('clearReservationStatusFilterBtn');
  if (btn) btn.innerHTML = `<i class='bi bi-x-circle'></i> Clear Filter: <b>${status}</b>`;
}
document.addEventListener('DOMContentLoaded', function() {
  const clearBtn = document.createElement('button');
  clearBtn.className = 'btn btn-sm btn-outline-secondary ms-2';
  clearBtn.id = 'clearReservationStatusFilterBtn';
  clearBtn.style.display = 'none';
  clearBtn.innerHTML = `<i class='bi bi-x-circle'></i> Clear Filter`;
  clearBtn.onclick = function() {
    filterRecentReservationsByStatus(null);
    this.style.display = 'none';
  };
  const recentCardTitle = document.querySelector('#recent-reservations-table').closest('.card-body').querySelector('.card-title');
  recentCardTitle.appendChild(clearBtn);
});
// --- Interactive Chart Filtering for Payments ---
let activePaymentStatusFilter = null;
paymentStatusChart.options.onClick = function(evt, elements) {
  if (elements.length > 0) {
    const idx = elements[0].index;
    const status = paymentStatusData.labels[idx];
    filterRecentPaymentsByStatus(status);
    setActivePaymentStatusFilterBtn(status);
  }
};
function filterRecentPaymentsByStatus(status) {
  const table = document.getElementById('recent-payments-table');
  if (!table) return;
  for (let i = 1; i < table.rows.length; i++) {
    const row = table.rows[i];
    const cell = row.cells[3]; // Status column
    if (!cell) continue;
    if (status === null || cell.innerText.trim().toLowerCase() === status.toLowerCase()) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  }
  activePaymentStatusFilter = status;
  document.getElementById('clearPaymentStatusFilterBtn').style.display = '';
}
function setActivePaymentStatusFilterBtn(status) {
  const btn = document.getElementById('clearPaymentStatusFilterBtn');
  if (btn) btn.innerHTML = `<i class='bi bi-x-circle'></i> Clear Filter: <b>${status}</b>`;
}
document.addEventListener('DOMContentLoaded', function() {
  const clearBtn = document.createElement('button');
  clearBtn.className = 'btn btn-sm btn-outline-secondary ms-2';
  clearBtn.id = 'clearPaymentStatusFilterBtn';
  clearBtn.style.display = 'none';
  clearBtn.innerHTML = `<i class='bi bi-x-circle'></i> Clear Filter`;
  clearBtn.onclick = function() {
    filterRecentPaymentsByStatus(null);
    this.style.display = 'none';
  };
  const recentCardTitle = document.querySelector('#recent-payments-table').closest('.card-body').querySelector('.card-title');
  recentCardTitle.appendChild(clearBtn);
});
// --- Daily Income Line Chart ---
const dailyIncomeData = {
  labels: <?php echo json_encode($daily_income_labels); ?>,
  datasets: [{
    label: 'Income',
    data: <?php echo json_encode($daily_income_data); ?>,
    borderColor: '#ffa533',
    backgroundColor: 'rgba(255,165,51,0.10)',
    tension: 0.4,
    fill: true,
    pointRadius: 4,
    pointBackgroundColor: '#ffa533',
    pointBorderColor: '#fff',
    pointHoverRadius: 6
  }]
};
new Chart(document.getElementById('dailyIncomeChart'), {
  type: 'line',
  data: dailyIncomeData,
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { color: '#fff' } }, x: { ticks: { color: '#fff' } } }
  }
});
// --- Payment Methods Pie Chart ---
const paymentMethodsData = {
  labels: <?php echo json_encode($payment_methods_labels); ?>,
  datasets: [{
    data: <?php echo json_encode($payment_methods_data); ?>,
    backgroundColor: ['#ffa533', '#2ecc71', '#0d6efd', '#ff4d4d', '#ffc107', '#6c757d', '#8e44ad'],
    borderColor: '#23234a',
    borderWidth: 2
  }]
};
new Chart(document.getElementById('paymentMethodsChart'), {
  type: 'pie',
  data: paymentMethodsData,
  options: {
    plugins: { legend: { labels: { color: '#fff', font: { weight: 'bold' } } } }
  }
});
// --- Top 5 Customers Bar Chart ---
const topCustomersData = {
  labels: <?php echo json_encode($top_customers_labels); ?>,
  datasets: [{
    label: 'Total Paid',
    data: <?php echo json_encode($top_customers_data); ?>,
    backgroundColor: '#ffa533',
    borderColor: '#23234a',
    borderWidth: 2
  }]
};
new Chart(document.getElementById('topCustomersChart'), {
  type: 'bar',
  data: topCustomersData,
  options: {
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(ctx) { return '₱' + ctx.parsed.y.toLocaleString(); } } } },
    scales: { y: { beginAtZero: true, ticks: { color: '#fff', callback: function(val) { return '₱' + val; } } }, x: { ticks: { color: '#fff' } } }
  }
});
</script>
</body>
</html> 