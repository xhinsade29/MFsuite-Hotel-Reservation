<?php

// Database connection
$conn = new mysqli("localhost", "root", "", "db_hotel_reservation");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch available room types
$rooms_sql = "SELECT type_id, type_name FROM tbl_room_type";
$rooms_result = $conn->query($rooms_sql);

// Fetch available service types
$services_sql = "SELECT service_id, service_name FROM tbl_services";
$services_result = $conn->query($services_sql);

// Fetch available payment types
$payments_sql = "SELECT payment_id, payment_type FROM tbl_payment";
$payments_result = $conn->query($payments_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Make a Reservation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e1e2f 0%, #2d2b42 100%);
            color: #fff;
            min-height: 100vh;
        }
        .sidebar {
            position: fixed;
            height: 100vh;
            width: 260px;
            background: rgba(17, 16, 29, 0.95);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        .content {
            margin-left: 260px;
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
            margin-left: 300px;
        }
        h1 {
            text-align: center;
            margin-bottom: 40px;
            font-weight: 600;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .reservation-form {
            background: rgba(42, 42, 64, 0.9);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-section {
            margin-bottom: 35px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        .section-title {
            font-size: 1.2em;
            color: #007BFF;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(0, 123, 255, 0.3);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #fff;
        }
        input[type="text"],
        input[type="datetime-local"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        input:focus,
        select:focus {
            outline: none;
            border-color: #007BFF;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
            background: rgba(255, 255, 255, 0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #007BFF, #00C6FF);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-submit:hover {
            background: linear-gradient(45deg, #0056b3, #009dc4);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .grid-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
            .content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../components/navigation.php'; ?>
    
    <div class="content">
        <h1><i class="fas fa-calendar-alt"></i> Make a Reservation</h1>
        
        <form class="reservation-form" action="process_reservation.php" method="POST">
            <!-- Personal Information Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-user"></i> Personal Information</h2>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" name="first_name" id="first_name" required />
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" name="middle_name" id="middle_name" />
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" name="last_name" id="last_name" required />
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="phone_number">Phone Number</label>
                        <input type="text" name="phone_number" id="phone_number" required />
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" name="address" id="address" required />
                    </div>
                </div>
            </div>

            <!-- Booking Details Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-bed"></i> Booking Details</h2>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="room_id">Room Type</label>
                        <select name="room_id" id="room_id" required>
                            <option value="">Select a Room Type</option>
                            <?php
                            if ($rooms_result->num_rows > 0) {
                                while ($room = $rooms_result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($room['type_id']) . '">' . 
                                         htmlspecialchars($room['type_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="service_id">Service Type</label>
                        <select name="service_id" id="service_id" required>
                            <option value="">Select a Service Type</option>
                            <?php
                            if ($services_result && $services_result->num_rows > 0) {
                                while ($service = $services_result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($service['service_id']) . '">' . 
                                         htmlspecialchars($service['service_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label for="check_in">Check-In Date</label>
                        <input type="datetime-local" name="check_in" id="check_in" required />
                    </div>
                    <div class="form-group">
                        <label for="check_out">Check-Out Date</label>
                        <input type="datetime-local" name="check_out" id="check_out" required />
                    </div>
                </div>
            </div>

            <!-- Payment Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-credit-card"></i> Payment Details</h2>
                <div class="form-group">
                    <label for="payment_id">Payment Method</label>
                    <select name="payment_id" id="payment_id" required>
                        <option value="">Select Payment Method</option>
                        <?php
                        if ($payments_result && $payments_result->num_rows > 0) {
                            while ($payment = $payments_result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($payment['payment_id']) . '">' . 
                                     htmlspecialchars($payment['payment_type']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-check-circle"></i> Confirm Reservation
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>