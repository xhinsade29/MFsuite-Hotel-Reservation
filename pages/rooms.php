<?php
include('../functions/db_connect.php');

// Fetch room types from the database
$sql = "SELECT type_name, description, max_occupancy, room_price FROM tbl_room_type";
$result = mysqli_query($mycon, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <title>Room Types</title>

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
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background-color: #1e1e2f;
            color: var(--text-light);
            padding-top: var(--header-height);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
            max-width: 1200px;
            margin: 0 auto;
            margin-left: var(--sidebar-width);
            flex: 1;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 600;
            color: var(--text-light);
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .card {
            background-color: var(--secondary);
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border-color: var(--primary);
        }

        .card h3 {
            margin: 0 0 15px;
            font-weight: 600;
            color: var(--text-light);
            font-size: 1.5em;
        }

        .card p {
            margin: 10px 0;
            color: var(--text-dim);
            line-height: 1.6;
        }

        .card .price {
            font-size: 1.4em;
            font-weight: bold;
            color: var(--primary);
            margin-top: 20px;
            padding: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card .occupancy {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: var(--text-dim);
            margin: 10px 0;
        }

        .card .occupancy i {
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            
            .card-container {
                grid-template-columns: 1fr;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include('../components/user_navigation.php'); ?>
    
    <div class="content">
        <h1>Room Types</h1>
        <div class="card-container">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="card">';
                    echo '<h3>' . htmlspecialchars($row['type_name']) . '</h3>';
                    echo '<p>' . htmlspecialchars($row['description']) . '</p>';
                    echo '<div class="occupancy"><i class="bi bi-people"></i> Max Occupancy: ' . htmlspecialchars($row['max_occupancy']) . '</div>';
                    echo '<p class="price">₱' . number_format($row['room_price'], 2) . '</p>';
                    echo '</div>';
                }
            } else {
                echo '<p>No room types available.</p>';
            }
            ?>
        </div>
    </div>

    <?php include('../components/footer.php'); ?>
</body>
</html>

