<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MF Suites Hotel - Luxury Accommodations</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles/main.css">
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to MF Suites Hotel</h1>
            <p>Experience luxury and comfort in the heart of the city</p>
            <a href="pages/rooms.php" class="cta-button">Book Your Stay</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2>Why Choose MF Suites?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <i class="bi bi-star"></i>
                    <h3>Luxury Rooms</h3>
                    <p>Elegantly designed rooms with premium amenities</p>
                </div>
                <div class="feature-card">
                    <i class="bi bi-geo-alt"></i>
                    <h3>Prime Location</h3>
                    <p>Centrally located with easy access to attractions</p>
                </div>
                <div class="feature-card">
                    <i class="bi bi-cup-hot"></i>
                    <h3>Fine Dining</h3>
                    <p>Exquisite culinary experiences at our restaurants</p>
                </div>
                <div class="feature-card">
                    <i class="bi bi-shield-check"></i>
                    <h3>24/7 Security</h3>
                    <p>Round-the-clock security for your peace of mind</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Room Preview Section -->
    <section class="room-preview">
        <div class="container">
            <h2>Our Luxurious Rooms</h2>
            <div class="room-grid">
                <div class="room-card">
                    <img src="assets/room-deluxe.jpg" alt="Deluxe Room">
                    <div class="room-info">
                        <h3>Deluxe Room</h3>
                        <p>Spacious comfort with modern amenities</p>
                        <a href="pages/rooms.php" class="room-link">View Details</a>
                    </div>
                </div>
                <div class="room-card">
                    <img src="assets/room-suite.jpg" alt="Executive Suite">
                    <div class="room-info">
                        <h3>Executive Suite</h3>
                        <p>Luxury living with premium services</p>
                        <a href="pages/rooms.php" class="room-link">View Details</a>
                    </div>
                </div>
                <div class="room-card">
                    <img src="assets/room-presidential.jpg" alt="Presidential Suite">
                    <div class="room-info">
                        <h3>Presidential Suite</h3>
                        <p>Ultimate luxury and exclusivity</p>
                        <a href="pages/rooms.php" class="room-link">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services">
        <div class="container">
            <h2>Our Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <i class="bi bi-cup-straw"></i>
                    <h3>Room Service</h3>
                    <p>24/7 in-room dining service</p>
                </div>
                <div class="service-card">
                    <i class="bi bi-water"></i>
                    <h3>Swimming Pool</h3>
                    <p>Infinity pool with city views</p>
                </div>
                <div class="service-card">
                    <i class="bi bi-spa"></i>
                    <h3>Spa & Wellness</h3>
                    <p>Rejuvenating treatments and massages</p>
                </div>
                <div class="service-card">
                    <i class="bi bi-car-front"></i>
                    <h3>Airport Transfer</h3>
                    <p>Complimentary airport shuttle service</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact">
        <div class="container">
            <h2>Contact Us</h2>
            <div class="contact-grid">
                <div class="contact-info">
                    <div class="info-item">
                        <i class="bi bi-geo-alt"></i>
                        <p>123 Hotel Street, City Center</p>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-telephone"></i>
                        <p>+1 234 567 8900</p>
                    </div>
                    <div class="info-item">
                        <i class="bi bi-envelope"></i>
                        <p>info@mfsuites.com</p>
                    </div>
                </div>
                <div class="contact-form">
                    <form action="process_contact.php" method="POST">
                        <input type="text" name="name" placeholder="Your Name" required>
                        <input type="email" name="email" placeholder="Your Email" required>
                        <textarea name="message" placeholder="Your Message" required></textarea>
                        <button type="submit" class="submit-btn">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="assets/MFsuites_logo.png" alt="MF Suites Logo">
                    <p>Luxury Redefined</p>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <a href="pages/rooms.php">Rooms</a>
                    <a href="pages/services.php">Services</a>
                    <a href="pages/about.php">About Us</a>
                    <a href="pages/contact.php">Contact</a>
                </div>
                <div class="footer-social">
                    <h4>Follow Us</h4>
                    <div class="social-icons">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-twitter"></i></a>
                        <a href="#"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 MF Suites Hotel. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html> 