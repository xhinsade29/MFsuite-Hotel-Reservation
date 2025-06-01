<?php
include('../functions/db_connect.php');

// Fetch room types with their services and image
$sql = "SELECT rt.*, 
        GROUP_CONCAT(
            CONCAT(s.service_name, '|', s.service_description) 
            SEPARATOR '||'
        ) as services 
        FROM tbl_room_type rt 
        LEFT JOIN tbl_room_services rs ON rt.room_type_id = rs.room_type_id 
        LEFT JOIN tbl_services s ON rs.service_id = s.service_id 
        GROUP BY rt.room_type_id";
$result = mysqli_query($mycon, $sql);

// Map room types to their image files (fallback)
$room_images = [
    1 => 'standard.avif',      // Standard Room
    2 => 'deluxe1.jpg',        // Deluxe Room
    3 => 'superior.jpg',      // Superior Room
    4 => 'family_suite.jpg',  // Family Suite
    5 => 'executive.jpg',     // Executive Suite
    6 => 'presidential.avif'   // Presidential Suite
];
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
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border-color: var(--primary);
        }

        .card-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .card-content {
            padding: 25px;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: var(--secondary);
            margin: 50px auto;
            padding: 30px;
            width: 96%;
            max-width: 1300px;
            border-radius: 15px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            color: var(--text-light);
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-modal:hover {
            color: var(--primary);
        }

        .modal-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 48px;
            margin-top: 20px;
            align-items: center;
        }

        .modal-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }

        .modal-details {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .modal-details.booking-form {
            background: #23234a;
            border-left: 2px solid rgba(255,255,255,0.10);
            padding: 40px 32px 40px 32px;
            border-radius: 0 16px 16px 0;
            min-width: 420px;
            max-width: 480px;
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.18);
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .modal-details.booking-form h4 {
            margin-bottom: 1.2rem;
            color: var(--primary);
            font-weight: 700;
            text-align: center;
        }

        .modal-details.booking-form form {
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .modal-details.booking-form .form-label {
            color: var(--text-light);
            font-size: 1em;
            margin-bottom: 0.3rem;
            text-align: left;
            font-weight: 500;
        }

        .modal-details.booking-form .form-control {
            background: rgba(255,255,255,0.05);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.10);
            border-radius: 8px;
            margin-bottom: 1.1rem;
            font-size: 1em;
            padding: 0.85rem 1.1rem;
            box-shadow: none;
        }

        .modal-details.booking-form .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.12rem rgba(255,140,0,0.13);
        }

        .modal-details.booking-form button.btn-primary {
            width: 100%;
            padding: 0.9rem 0;
            font-size: 1.1em;
            font-weight: 600;
            border-radius: 8px;
            background: linear-gradient(90deg, var(--primary), #ffa533);
            border: none;
            box-shadow: 0 2px 8px rgba(255,140,0,0.10);
        }

        .modal-details.booking-form button.btn-primary:hover {
            background: linear-gradient(90deg, #e67c00, #ffb366);
            box-shadow: 0 4px 12px rgba(255,140,0,0.18);
        }

        .service-description {
            font-size: 0.85em;
            color: #bdbdbd;
            line-height: 1.4;
        }

        @media (max-width: 1400px) {
            .modal-content {
                max-width: 1100px;
            }
        }

        @media (max-width: 991px) {
            .modal-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .modal-content {
                max-width: 98vw;
            }
            .modal-details.booking-form {
                border-left: none;
                border-top: 2px solid rgba(255,255,255,0.10);
                border-radius: 0 0 16px 16px;
                padding: 24px 6px 24px 6px;
                margin-top: 20px;
                min-width: unset;
                max-width: unset;
            }
        }

        .services-list {
            margin-top: 20px;
        }

        .services-list h3 {
            color: var(--text-light);
            margin-bottom: 15px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .service-item {
            background-color: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .service-item:hover {
            transform: translateY(-5px);
            background-color: rgba(255, 255, 255, 0.1);
        }

        .service-item i {
            color: var(--primary);
            font-size: 1.2em;
            margin-right: 10px;
        }

        .service-name {
            font-weight: 500;
            color: var(--text-light);
            margin-bottom: 5px;
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

            .modal-grid {
                grid-template-columns: 1fr;
            }

            .modal-image {
                height: 300px;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Add these styles to your existing CSS */
        .gallery-container {
            position: relative;
            width: 100%;
            margin-bottom: 20px;
        }

        .gallery-main {
            position: relative;
            width: 100%;
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
        }

        .gallery-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-thumbnails {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            overflow-x: auto;
            padding: 10px 0;
        }

        .gallery-thumbnail {
            width: 80px;
            height: 60px;
            border-radius: 5px;
            cursor: pointer;
            opacity: 0.6;
            transition: all 0.3s ease;
        }

        .gallery-thumbnail:hover,
        .gallery-thumbnail.active {
            opacity: 1;
            transform: scale(1.05);
        }

        .gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 20px;
            transition: all 0.3s ease;
            z-index: 2;
        }

        .gallery-nav:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .gallery-prev {
            left: 10px;
        }

        .gallery-next {
            right: 10px;
        }

        .gallery-counter {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
            z-index: 2;
        }

        .see-details-btn {
            background: linear-gradient(90deg, #FF8C00, #ffa533);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.08em;
            padding: 0.7em 1.6em;
            box-shadow: 0 2px 8px rgba(255,140,0,0.10);
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
            outline: none;
            cursor: pointer;
            letter-spacing: 0.5px;
        }

        .see-details-btn:hover, .see-details-btn:focus {
            background: linear-gradient(90deg, #e67c00, #ffb366);
            box-shadow: 0 4px 16px rgba(255,140,0,0.18);
            transform: translateY(-2px) scale(1.04);
            color: #fff;
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
                    // Use uploaded image if available, else fallback
                    $image_file = !empty($row['image']) ? $row['image'] : ($room_images[$row['room_type_id']] ?? 'standard.avif');
                    echo '<div class="card">';
                    echo '<img src="../assets/rooms/' . htmlspecialchars($image_file) . '" alt="' . htmlspecialchars($row['type_name']) . '" class="card-image" onerror="this.src=\'../assets/rooms/standard.avif\'">';
                    echo '<div class="card-content">';
                    echo '<h3>' . htmlspecialchars($row['type_name']) . '</h3>';
                    echo '<p>' . htmlspecialchars($row['description']) . '</p>';
                    echo '<div class="occupancy"><i class="bi bi-people"></i> Max Occupancy: ' . htmlspecialchars($row['max_occupancy']) . '</div>';
                    echo '<p class="price">₱' . number_format($row['nightly_rate'], 2) . '</p>';
                    echo '<button class="see-details-btn mt-3" onclick="window.location.href=\'booking_form.php?room_type_id=' . urlencode($row['room_type_id']) . '\';">See Details</button>';
                    echo '</div></div>';
                }
            } else {
                echo '<p>No room types available.</p>';
            }
            ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="roomModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div class="modal-grid">
                <div class="gallery-container">
                    <div class="gallery-main">
                        <img id="modalImage" src="" alt="Room Image">
                        <div class="gallery-nav gallery-prev" onclick="changeImage(-1)">
                            <i class="bi bi-chevron-left"></i>
                        </div>
                        <div class="gallery-nav gallery-next" onclick="changeImage(1)">
                            <i class="bi bi-chevron-right"></i>
                        </div>
                        <div class="gallery-counter">
                            <span id="currentImage">1</span> / <span id="totalImages">1</span>
                        </div>
                    </div>
                    <div class="gallery-thumbnails" id="galleryThumbnails">
                        <!-- Thumbnails will be added here dynamically -->
                    </div>
                </div>
                <div class="modal-details">
                    <h2 id="modalTitle"></h2>
                    <p id="modalDescription"></p>
                    <div class="occupancy">
                        <i class="bi bi-people"></i>
                        <span id="modalOccupancy"></span>
                    </div>
                    <p class="price" id="modalPrice"></p>
                    <div class="services-list">
                        <h3>Room Inclusions</h3>
                        <div class="services-grid" id="modalServices"></div>
                    </div>
                    <div class="d-grid mt-4">
                        <a href="booking_form.php?room_type_id=' + encodeURIComponent(roomData.room_type_id) + '" class="btn btn-primary" style="width:100%;font-size:1.1em;">
                            <i class="bi bi-calendar-check"></i> Book Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (required for modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let currentRoomImages = [];
        let currentImageIndex = 0;
        let bookingFormModal;
        let lastRoomData = null;

        function openModal(roomData) {
            const modal = document.getElementById('roomModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            const modalDescription = document.getElementById('modalDescription');
            const modalOccupancy = document.getElementById('modalOccupancy');
            const modalPrice = document.getElementById('modalPrice');
            const modalServices = document.getElementById('modalServices');
            const galleryThumbnails = document.getElementById('galleryThumbnails');

            // Map room types to their image files
            const roomImages = {
                1: ['standard.avif', 'standard2.avif', 'standard3.avif', 'standard4.jpg', 'standard5.avif'],
                2: ['deluxe1.jpg', 'deluxe2.avif', 'deluxe3.avif', 'deluxe4.avif', 'deluxe5.avif'],
                3: ['superior.jpg', 'superior2.jpg', 'superior3.jpg', 'superior4.jpg', 'superior5.jpg'],
                4: ['family_suite.jpg', 'family_suite2.jpg', 'family_suite3.jpg', 'family_suite4.jpg', 'family_suite5.jpg'],
                5: ['executive.jpg', 'executive2.jpg', 'executive3.jpg', 'executive4.jpg', 'executive5.jpg'],
                6: ['presidential.avif', 'presidential2.jpg', 'presidential3.jpg', 'presidential4.jpg', 'presidential5.jpg']
            };

            // Set current room images
            currentRoomImages = roomImages[roomData.room_type_id] || ['standard.avif'];
            currentImageIndex = 0;

            // Update main image
            updateMainImage();

            // Update thumbnails
            updateThumbnails();

            // Update counter
            updateImageCounter();

            modalTitle.textContent = roomData.type_name;
            modalDescription.textContent = roomData.description;
            modalOccupancy.textContent = `Max Occupancy: ${roomData.max_occupancy}`;
            modalPrice.textContent = `₱${parseFloat(roomData.nightly_rate).toLocaleString('en-US', {minimumFractionDigits: 2})}`;

            // Display services with descriptions
            modalServices.innerHTML = '';
            if (roomData.services) {
                const services = roomData.services.split('||');
                services.forEach(service => {
                    const [name, description] = service.split('|');
                    const serviceItem = document.createElement('div');
                    serviceItem.className = 'service-item';
                    serviceItem.innerHTML = `
                        <div class="service-name">
                            <i class="bi bi-check-circle"></i>
                            ${name.trim()}
                        </div>
                        <div class="service-description">
                            ${description.trim()}
                        </div>
                    `;
                    modalServices.appendChild(serviceItem);
                });
            }

            // Set booking form values
            document.getElementById('modalRoomTypeId').value = roomData.room_type_id;
            document.getElementById('modalTotalAmountInput').value = parseFloat(roomData.nightly_rate).toLocaleString('en-US', {minimumFractionDigits:2});

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            lastRoomData = roomData;
        }

        function updateMainImage() {
            const modalImage = document.getElementById('modalImage');
            modalImage.src = `../assets/rooms/${currentRoomImages[currentImageIndex]}`;
            modalImage.onerror = function() {
                this.src = '../assets/rooms/standard.avif';
            };
        }

        function updateThumbnails() {
            const galleryThumbnails = document.getElementById('galleryThumbnails');
            galleryThumbnails.innerHTML = '';

            currentRoomImages.forEach((image, index) => {
                const thumbnail = document.createElement('img');
                thumbnail.src = `../assets/rooms/${image}`;
                thumbnail.alt = `Thumbnail ${index + 1}`;
                thumbnail.className = `gallery-thumbnail ${index === currentImageIndex ? 'active' : ''}`;
                thumbnail.onclick = () => {
                    currentImageIndex = index;
                    updateMainImage();
                    updateThumbnails();
                    updateImageCounter();
                };
                galleryThumbnails.appendChild(thumbnail);
            });
        }

        function updateImageCounter() {
            document.getElementById('currentImage').textContent = currentImageIndex + 1;
            document.getElementById('totalImages').textContent = currentRoomImages.length;
        }

        function changeImage(direction) {
            currentImageIndex = (currentImageIndex + direction + currentRoomImages.length) % currentRoomImages.length;
            updateMainImage();
            updateThumbnails();
            updateImageCounter();
        }

        function closeModal() {
            const modal = document.getElementById('roomModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('roomModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

