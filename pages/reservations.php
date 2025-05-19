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

<?php include '../components/user_navigation.php'; ?>
<?php include '../components/footer.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Make a Reservation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #FF8C00;
            --secondary: #11101d;
            --text-light: #ffffff;
            --text-dim: rgba(255, 255, 255, 0.7);
            --header-height: 70px;
            --sidebar-width: 240px;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #1e1e2f;
            color: var(--text-light);
            padding-top: var(--header-height);
        }

        .content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
            max-width: 1200px;
            margin: 0 auto;
            margin-left: var(--sidebar-width);
        }

        h1 {
            text-align: center;
            margin-bottom: 40px;
            font-weight: 600;
            color: var(--text-light);
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .reservation-form {
            background: var(--secondary);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
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
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 140, 0, 0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-light);
        }

        input[type="text"],
        input[type="datetime-local"],
        select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-light);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 140, 0, 0.25);
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, var(--primary), #ffa533);
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
            background: linear-gradient(45deg, #e67e00, #ff8c00);
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
                        <label for="check_in">Check-in Date</label>
                        <input type="datetime-local" name="check_in" id="check_in" required />
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="check_out">Check-out Date</label>
                        <input type="datetime-local" name="check_out" id="check_out" required />
                    </div>
                    <div class="form-group">
                        <label for="guests">Number of Guests</label>
                        <input type="number" name="guests" id="guests" min="1" required />
                    </div>
                </div>
            </div>

            <!-- Additional Services Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-concierge-bell"></i> Additional Services</h2>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="service_id">Select Services</label>
                        <select name="service_id" id="service_id" multiple>
                            <?php
                            if ($services_result->num_rows > 0) {
                                while ($service = $services_result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($service['service_id']) . '">' . 
                                         htmlspecialchars($service['service_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="special_requests">Special Requests</label>
                        <textarea name="special_requests" id="special_requests" rows="4" 
                                  class="form-control" style="background: rgba(255, 255, 255, 0.05); 
                                  border: 1px solid rgba(255, 255, 255, 0.1); 
                                  color: var(--text-light); 
                                  border-radius: 8px;"></textarea>
                    </div>
                </div>
            </div>

            <!-- Payment Section -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-credit-card"></i> Payment Information</h2>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="payment_type">Payment Method</label>
                        <select name="payment_type" id="payment_type" required>
                            <option value="">Select Payment Method</option>
                            <?php
                            if ($payments_result->num_rows > 0) {
                                while ($payment = $payments_result->fetch_assoc()) {
                                    echo '<option value="' . htmlspecialchars($payment['payment_id']) . '">' . 
                                         htmlspecialchars($payment['payment_type']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
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

<?php
$conn->close();
?>