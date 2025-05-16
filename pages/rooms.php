<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "db_mfsuite_reservation");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch room types from the database
$sql = "SELECT type_name, description, max_occupancy, room_price FROM tbl_room_type";
$result = $conn->query($sql);
?>

<?php include './components/user_navigation.php'; ?>
<?php include './components/footer.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Room Types</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #1e1e2f;
            color: #fff;
            display: flex;
        }
        .sidebar {
            position: fixed;
            height: 100vh;
            width: 260px;
            background-color: #11101d;
            padding-top: 20px;
        }
        .content {
            margin-left: 260px;
            padding: 40px;
            width: 100%;
            max-width: 900px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
        }
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        .card {
            background-color: #2a2a40;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.5);
            width: 300px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.7);
        }
        .card h3 {
            margin: 0 0 10px;
            font-weight: 600;
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
    <div class="sidebar">
        <?php include '../components/sidebar.php'; ?>
    </div>
    <div class="content">
        <h1>Room Types</h1>
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
    </div>
</body>
</html>

<?php
$conn->close();
?>
