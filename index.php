<?php
include 'functions/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MF Suites Hotel - Luxury Accommodations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #1e1e2f; color: #fff; }
        .hero-section { position: relative; height: 100vh; overflow: hidden; }
        .hero-section video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; z-index: 0; }
        .hero-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)); z-index: 1; }
        .hero-content { position: relative; z-index: 2; height: 100vh; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; color: #fff; }
        .hero-content img { max-width: 160px; margin-bottom: 1.5rem; filter: drop-shadow(0 2px 8px #ff8c0033); }
        .hero-content h1 { font-size: 3.2rem; font-weight: 700; margin-bottom: 1rem; text-shadow: 2px 2px 8px rgba(0,0,0,0.4); }
        .hero-content p { font-size: 1.3rem; margin-bottom: 2rem; }
        .cta-btn { padding: 14px 38px; background: linear-gradient(45deg, #FF8C00, #ffa533); color: #fff; border: none; border-radius: 50px; font-weight: 600; font-size: 1.1rem; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(255,140,0,0.3); transition: all 0.3s; }
        .cta-btn:hover { background: linear-gradient(45deg, #e67c00, #ffb366); transform: translateY(-2px); }
        .section-title { text-align: center; margin-bottom: 48px; font-size: 2.2rem; font-weight: 700; color: #ffa533; }
        .about-section { background: #23234a; padding: 70px 0 40px; }
        .about-section .about-content { max-width: 800px; margin: 0 auto; text-align: center; }
        .about-section img { max-width: 120px; margin-bottom: 1.2rem; }
        .about-section h2 { color: #ffa533; font-weight: 700; margin-bottom: 1rem; }
        .about-section p { color: #fff; font-size: 1.1rem; }
        .rooms-section { background: #1a1a2e; padding: 80px 0; }
        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 32px; }
        .room-card { background: #23234a; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); overflow: hidden; transition: all 0.3s; border: 1px solid rgba(255,255,255,0.08); }
        .room-card:hover { transform: translateY(-8px); border-color: #ffa533; }
        .room-card img { width: 100%; height: 220px; object-fit: cover; }
        .room-card .card-body { padding: 28px 22px 22px; }
        .room-card h4 { color: #ffa533; font-weight: 700; margin-bottom: 10px; }
        .room-card .desc { color: #bdbdbd; font-size: 1em; margin-bottom: 10px; }
        .room-card .occupancy { color: #fff; font-size: 0.98em; margin-bottom: 8px; }
        .room-card .price { color: #FF8C00; font-weight: 600; font-size: 1.1em; margin-bottom: 10px; }
        .room-card .btn { width: 100%; }
        .services-section { background: #23234a; padding: 80px 0; }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 36px 32px;
            margin-bottom: 0;
        }
        .service-card {
            background: #1a1a2e;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
            padding: 36px 26px 32px 26px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid rgba(255,255,255,0.06);
            min-height: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }
        .service-card:hover { transform: translateY(-6px); border-color: #ffa533; }
        .service-card img { width: 60px; height: 60px; object-fit: cover; border-radius: 50%; margin-bottom: 16px; }
        .service-card h5 { color: #ffa533; font-weight: 600; margin-bottom: 8px; }
        .service-card p { color: #bdbdbd; font-size: 0.98em; }
        @media (max-width: 991px) {
            .services-grid {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 28px 18px;
            }
            .service-card {
                min-height: 260px;
                padding: 28px 12px 24px 12px;
            }
        }
        @media (max-width: 600px) {
            .services-grid {
                grid-template-columns: 1fr;
                gap: 18px 0;
            }
            .service-card {
                min-height: unset;
                padding: 18px 6px 18px 6px;
            }
        }
        .offers-section { background: #1a1a2e; padding: 80px 0; }
        .offers-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px; }
        .offer-card { background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); overflow: hidden; text-align: center; }
        .offer-card img { width: 100%; height: 200px; object-fit: cover; }
        .offer-card .card-body { padding: 24px 18px; }
        .offer-card h5 { color: #ffa533; font-weight: 600; margin-bottom: 10px; }
        .testimonials-section { background: #23234a; padding: 80px 0; }
        .testimonials-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 32px; }
        .testimonial-card { background: #1a1a2e; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.12); padding: 32px 22px; text-align: center; }
        .testimonial-card img { width: 70px; height: 70px; border-radius: 50%; margin-bottom: 16px; }
        .testimonial-card .testimonial-text { color: #bdbdbd; font-style: italic; margin-bottom: 12px; }
        .testimonial-card .testimonial-author { color: #ffa533; font-weight: 600; }
        .contact-section { background: #1a1a2e; padding: 80px 0; }
        .contact-card { background: #23234a; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.18); padding: 40px 32px; max-width: 600px; margin: 0 auto; }
        .footer { background: #11101d; padding: 60px 0 20px; color: #bdbdbd; }
        .footer .footer-logo img { max-width: 120px; margin-bottom: 10px; }
        .footer .footer-links a { color: #bdbdbd; text-decoration: none; margin-right: 18px; }
        .footer .footer-links a:hover { color: #ffa533; }
        .footer .social-icons a { color: #bdbdbd; font-size: 1.4rem; margin-right: 12px; }
        .footer .social-icons a:hover { color: #ffa533; }
        @media (max-width: 768px) { .hero-content h1 { font-size: 2rem; } .section-title { font-size: 1.4rem; } }
    </style>
</head>
<body>
    <!-- Toast Container -->
    <div class="toast-container" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="toast toast-success" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="toast-header">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
        <div class="toast toast-error" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="toast-header">
                <i class="bi bi-exclamation-circle-fill text-danger me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <!-- Hero Section -->
    <section class="hero-section">
        <video autoplay muted loop playsinline>
            <source src="assets/hero.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <img src="assets/MFsuites_logo.png" alt="MF Suites Logo">
            <h1>Welcome to MF Suites Hotel</h1>
            <p>Experience luxury and comfort in the heart of the city</p>
            <a href="#rooms" class="cta-btn">Book Your Stay</a>
        </div>
    </section>
    <!-- About Section -->
    <section class="about-section">
        <div class="about-content">
            <img src="assets/MFsuites_logo.png" alt="Hotel Logo">
            <h2>About MF Suites Hotel</h2>
            <p>MF Suites Hotel offers a blend of luxury, comfort, and convenience. Located in the heart of the city, our hotel features elegantly designed rooms, world-class amenities, and exceptional service to make your stay unforgettable.</p>
        </div>
    </section>
    <!-- Rooms Section -->
    <section class="rooms-section" id="rooms">
        <div class="container">
            <div class="section-title">Our Rooms</div>
            <div class="rooms-grid">
                <?php
                // Fetch room types with their services
                $sql = "SELECT rt.*, GROUP_CONCAT(CONCAT(s.service_name, '|', s.service_description) SEPARATOR '||') as services FROM tbl_room_type rt LEFT JOIN tbl_room_services rs ON rt.room_type_id = rs.room_type_id LEFT JOIN tbl_services s ON rs.service_id = s.service_id GROUP BY rt.room_type_id";
                $result = mysqli_query($mycon, $sql);
                $room_images = [
                    1 => 'standard.avif',
                    2 => 'deluxe1.jpg',
                    3 => 'superior.jpg',
                    4 => 'family_suite.jpg',
                    5 => 'executive.jpg',
                    6 => 'presidential.avif'
                ];
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $image_file = $room_images[$row['room_type_id']] ?? 'standard.avif';
                        echo '<div class="room-card">';
                        echo '<img src="assets/rooms/' . htmlspecialchars($image_file) . '" alt="' . htmlspecialchars($row['type_name']) . '" onerror="this.src=\'assets/rooms/standard.avif\'">';
                        echo '<div class="card-body">';
                        echo '<h4>' . htmlspecialchars($row['type_name']) . '</h4>';
                        echo '<div class="desc">' . htmlspecialchars($row['description']) . '</div>';
                        echo '<div class="occupancy"><i class="bi bi-people"></i> Max Occupancy: ' . htmlspecialchars($row['max_occupancy']) . '</div>';
                        echo '<div class="price">₱' . (isset($row['nightly_rate']) ? number_format($row['nightly_rate'], 2) : 'N/A') . '</div>';
                        echo '<a href="pages/booking_form.php?room_type_id=' . urlencode($row['room_type_id']) . '" class="btn btn-warning mt-2"><i class="bi bi-calendar-check"></i> Book Now</a>';
                        echo '</div></div>';
                    }
                } else {
                    echo '<p class="text-center">No room types available.</p>';
                }
                ?>
            </div>
        </div>
    </section>
    <!-- Services Section -->
    <section class="services-section" id="services">
        <div class="container">
            <div class="section-title">Our Services</div>
            <div class="services-grid">
                <?php
                $services_sql = "SELECT * FROM tbl_services";
                $services_result = mysqli_query($mycon, $services_sql);
                function normalize_service_key($name) {
                    // Remove all non-alphanumeric, then remove spaces, then lowercase
                    return strtolower(str_replace(' ', '', trim(preg_replace('/[^a-zA-Z0-9 ]/', '', $name))));
                }
                $service_icons = [
                    'spa' => 'bi-spa',
                    'swimmingpool' => 'bi-water',
                    'restaurant' => 'bi-cup-straw',
                    'airportshuttle' => 'bi-bus-front',
                    'businesscenter' => 'bi-briefcase',
                    'concierge' => 'bi-person-badge',
                    'fitnesscenter' => 'bi-barbell',
                    'luggagestorage' => 'bi-suitcase',
                    'laundrydrycleaning' => 'bi-droplet',
                    'roomservice' => 'bi-door-open',
                    'housekeeping' => 'bi-bucket',
                    'conferenceroom' => 'bi-easel',
                    'wifi' => 'bi-wifi'
                ];
                if ($services_result && $services_result->num_rows > 0) {
                    while ($service = $services_result->fetch_assoc()) {
                        $key = normalize_service_key($service['service_name']);
                        $icon = $service_icons[$key] ?? 'bi-star';
                        echo '<div class="service-card">';
                        echo '<i class="bi ' . htmlspecialchars($icon) . '" style="font-size:2.5rem;color:#ffa533;margin-bottom:18px;"></i>';
                        echo '<h5>' . htmlspecialchars($service['service_name']) . '</h5>';
                        echo '<p>' . htmlspecialchars($service['service_description']) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p class="text-center">No services available.</p>';
                }
                ?>
            </div>
        </div>
    </section>
    <!-- Special Offers Section -->
    <section class="offers-section" id="offers">
        <div class="container">
            <div class="section-title">Special Offers & Packages</div>
            <div class="offers-grid">
                <div class="offer-card"><img src="assets/family.jpg" alt="Family Package"><div class="card-body"><h5>Family Getaway</h5><p>Spacious suites and fun amenities for the whole family. Enjoy exclusive discounts and perks!</p></div></div>
                <div class="offer-card"><img src="assets/business.jpg" alt="Business Package"><div class="card-body"><h5>Business Traveler</h5><p>Modern workspaces, high-speed WiFi, and business center access for your productivity.</p></div></div>
                <div class="offer-card"><img src="assets/romantic.jpg" alt="Romantic Package"><div class="card-body"><h5>Romantic Escape</h5><p>Luxurious rooms, spa treatments, and candlelit dinners for couples.</p></div></div>
            </div>
        </div>
    </section>
    <!-- Testimonials Section -->
    <section class="testimonials-section" id="testimonials">
        <div class="container">
            <div class="section-title">Guest Reviews</div>
            <div class="testimonials-grid">
                <div class="testimonial-card"><img src="assets/testimonial1.jpg" alt="Guest"><div class="testimonial-text">"An unforgettable experience! The service was impeccable and the rooms were luxurious."</div><div class="testimonial-author">- Samanita Smith</div></div>
                <div class="testimonial-card"><img src="assets/testimonial2.avif" alt="Guest"><div class="testimonial-text">"The best hotel I've ever stayed in. Everything was perfect from check-in to check-out."</div><div class="testimonial-author">- Sarah Johnson</div></div>
                <div class="testimonial-card"><img src="assets/testimonial3.avif" alt="Guest"><div class="testimonial-text">"Amazing location and stunning views. The staff went above and beyond to make our stay special."</div><div class="testimonial-author">- Michelle Brown</div></div>
            </div>
        </div>
    </section>
    <!-- Contact Section -->
    <section class="contact-section" id="contact">
        <div class="container">
            <div class="section-title">Contact Us</div>
            <div class="contact-card">
                <form action="pages/process_contact.php" method="POST">
                    <div class="mb-3"><input type="text" class="form-control" name="name" placeholder="Your Name" required></div>
                    <div class="mb-3"><input type="email" class="form-control" name="email" placeholder="Your Email" required></div>
                    <div class="mb-3"><textarea class="form-control" name="message" placeholder="Your Message" rows="4" required></textarea></div>
                    <button type="submit" class="btn btn-warning w-100">Send Message</button>
                    </form>
                <div class="mt-4 text-center">
                    <div><i class="bi bi-geo-alt"></i> 123 Hotel Street, City Center</div>
                    <div><i class="bi bi-telephone"></i> +1 234 567 8900</div>
                    <div><i class="bi bi-envelope"></i> info@mfsuites.com</div>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <div class="footer-logo mb-3"><img src="assets/MFsuites_logo.png" alt="MF Suites Logo"></div>
            <div class="footer-links mb-2">
                <a href="#rooms">Rooms</a>|
                <a href="#services">Services</a>|
                <a href="#offers">Offers</a>|
                <a href="#contact">Contact</a>
                </div>
            <div class="social-icons mb-2">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                    </div>
            <div>&copy; 2024 MF Suites Hotel. All rights reserved.</div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 