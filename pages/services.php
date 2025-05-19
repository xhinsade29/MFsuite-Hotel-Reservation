<?php
include('../functions/db_connect.php');
include('../functions/service_card.php');

// Just select all services - keep it simple
$services = selectAll('tbl_services');
?>

<?php include '../components/user_navigation.php'; ?>
<?php include '../components/footer.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MF Suites - Services</title>
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

        .card {
            background: var(--secondary);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
            border-color: var(--primary);
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }

        .card-body {
            padding: 1.5rem;
        }

        .card-title {
            color: var(--text-light);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .card-text {
            color: var(--text-dim);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <h1><i class="fas fa-concierge-bell"></i> MF Suites Services</h1>
        
        <div class="row g-4">
            <?php
            if (mysqli_num_rows($services) > 0) {
                while($row = mysqli_fetch_assoc($services)) {
                    ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <?php if(!empty($row['service_image'])): ?>
                                <img src="../uploads/services/<?php echo $row['service_image']; ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo $row['service_name']; ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $row['service_name']; ?></h5>
                                <p class="card-text"><?php echo $row['service_description']; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo "<div class='col-12 text-center'>No services found</div>";
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
