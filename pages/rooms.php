<?php

// Database connection
$conn = new mysqli("localhost", "root", "", "db_hotel_reservation");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch room types from the database
$sql = "SELECT type_name, description, max_occupancy, room_price FROM tbl_room_type";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Types</title>
    <style>
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .card {
            border: 1px solid #ccc;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 300px;
            padding: 20px;
            text-align: center;
        }
        .card h3 {
            margin: 0 0 10px;
        }
        .card p {
            margin: 5px 0;
        }
        .card .price {
            font-size: 1.2em;
            font-weight: bold;
            color: #007BFF;
        }
    </style>
</head>
<body>
    <h1 style="text-align: center;">Room Types</h1>
    <div class="card-container">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<div class="card">';
                echo '<h3>' . htmlspecialchars($row['type_name']) . '</h3>';
                echo '<p>' . htmlspecialchars($row['description']) . '</p>';
                echo '<p>Max Occupancy: ' . htmlspecialchars($row['max_occupancy']) . '</p>';
                echo '<p class="price">₱' . number_format($row['room_price'], 2) . '</p>';
                echo '</div>';
            }
        } else {
            echo '<p>No room types available.</p>';
        }
        ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>