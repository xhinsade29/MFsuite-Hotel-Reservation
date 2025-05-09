<?php
include('../functions/db_connect.php');
include('../functions/service_card.php');

// Just select all services - keep it simple
$services = selectAll('tbl_services');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MF Suites - Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-5">MF Suites Services</h1>
        
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
