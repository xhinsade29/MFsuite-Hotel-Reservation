<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MF Suites Hotel - Luxury Accommodations</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="styles/main.css">
    <style>
        :root {
            --primary: #FF8C00;
            --secondary: #11101d;
            --text-light: #ffffff;
            --text-dim: rgba(255, 255, 255, 0.7);
        }

        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--text-light);
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero {
            position: relative;
            height: 100vh;
            overflow: hidden;
        }

        .hero-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7));
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 20px;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        .cta-button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(45deg, var(--primary), #ffa533);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.3);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 140, 0, 0.4);
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: var(--secondary);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .section-title {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--primary);
        }

        .feature-card i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        /* Room Preview Section */
        .room-preview {
            padding: 100px 0;
            background: #1a1a2e;
        }

        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .room-card {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }

        .room-card:hover {
            transform: translateY(-10px);
        }

        .room-card img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .room-card:hover img {
            transform: scale(1.1);
        }

        .room-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 30px;
            background: linear-gradient(transparent, rgba(0,0,0,0.9));
            color: white;
        }

        .room-info h3 {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .room-link {
            display: inline-block;
            padding: 10px 25px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .room-link:hover {
            background: #ffa533;
            transform: translateY(-2px);
        }

        /* Testimonials Section */
        .testimonials {
            padding: 100px 0;
            background: var(--secondary);
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .testimonial-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
        }

        .testimonial-card img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 20px;
        }

        .testimonial-text {
            font-style: italic;
            margin-bottom: 20px;
        }

        .testimonial-author {
            font-weight: 600;
            color: var(--primary);
        }

        /* Services Section */
        .services {
            padding: 100px 0;
            background: #1a1a2e;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .service-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.1);
        }

        .service-card i {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        /* Contact Section */
        .contact {
            padding: 100px 0;
            background: var(--secondary);
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }

        .contact-info {
            display: grid;
            gap: 30px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .info-item i {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .contact-form {
            background: rgba(255, 255, 255, 0.05);
            padding: 40px;
            border-radius: 20px;
        }

        .contact-form input,
        .contact-form textarea {
            width: 100%;
            padding: 15px;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            border-radius: 10px;
            color: white;
        }

        .contact-form textarea {
            height: 150px;
            resize: none;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: #ffa533;
        }

        /* Footer */
        .footer {
            background: #11101d;
            padding: 80px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-logo img {
            max-width: 150px;
            margin-bottom: 20px;
        }

        .footer-links a {
            display: block;
            color: var(--text-dim);
            text-decoration: none;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .social-icons {
            display: flex;
            gap: 15px;
        }

        .social-icons a {
            color: var(--text-dim);
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .social-icons a:hover {
            color: var(--primary);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.2rem;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }

            .section-title h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <video class="hero-video" autoplay muted loop>
            <source src="assets/hero.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-content" data-aos="fade-up">
            <h1>Welcome to MF Suites Hotel</h1>
            <p>Experience luxury and comfort in the heart of the city</p>
            <a href="pages/rooms.php" class="cta-button">Book Your Stay</a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Why Choose MF Suites?</h2>
            </div>
            <div class="features-grid">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <i class="bi bi-star"></i>
                    <h3>Luxury Rooms</h3>
                    <p>Elegantly designed rooms with premium amenities</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <i class="bi bi-geo-alt"></i>
                    <h3>Prime Location</h3>
                    <p>Centrally located with easy access to attractions</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <i class="bi bi-cup-hot"></i>
                    <h3>Fine Dining</h3>
                    <p>Exquisite culinary experiences at our restaurants</p>
                </div>
                <div class="feature-card" data-aos="fade-up" data-aos-delay="400">
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
            <div class="section-title" data-aos="fade-up">
                <h2>Our Luxurious Rooms</h2>
            </div>
            <div class="room-grid">
                <div class="room-card" data-aos="fade-up" data-aos-delay="100">
                    <img src="assets/rooms/deluxe1.jpg" alt="Deluxe Room">
                    <div class="room-info">
                        <h3>Deluxe Room</h3>
                        <p>Spacious comfort with modern amenities</p>
                        <a href="pages/rooms.php" class="room-link">View Details</a>
                    </div>
                </div>
                <div class="room-card" data-aos="fade-up" data-aos-delay="200">
                    <img src="assets/rooms/executive.jpg" alt="Executive Suite">
                    <div class="room-info">
                        <h3>Executive Suite</h3>
                        <p>Luxury living with premium services</p>
                        <a href="pages/rooms.php" class="room-link">View Details</a>
                    </div>
                </div>
                <div class="room-card" data-aos="fade-up" data-aos-delay="300">
                    <img src="assets/rooms/presidential.avif" alt="Presidential Suite">
                    <div class="room-info">
                        <h3>Presidential Suite</h3>
                        <p>Ultimate luxury and exclusivity</p>
                        <a href="pages/rooms.php" class="room-link">View Details</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Guest Reviews</h2>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="100">
                    <img src="assets/testimonial1.jpg" alt="Guest">
                    <p class="testimonial-text">"An unforgettable experience! The service was impeccable and the rooms were luxurious."</p>
                    <p class="testimonial-author">- John Smith</p>
                </div>
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="200">
                    <img src="assets/testimonial2.jpg" alt="Guest">
                    <p class="testimonial-text">"The best hotel I've ever stayed in. Everything was perfect from check-in to check-out."</p>
                    <p class="testimonial-author">- Sarah Johnson</p>
                </div>
                <div class="testimonial-card" data-aos="fade-up" data-aos-delay="300">
                    <img src="assets/testimonial3.jpg" alt="Guest">
                    <p class="testimonial-text">"Amazing location and stunning views. The staff went above and beyond to make our stay special."</p>
                    <p class="testimonial-author">- Michael Brown</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Our Services</h2>
            </div>
            <div class="services-grid">
                <div class="service-card" data-aos="fade-up" data-aos-delay="100">
                    <i class="bi bi-cup-straw"></i>
                    <h3>Room Service</h3>
                    <p>24/7 in-room dining service</p>
                </div>
                <div class="service-card" data-aos="fade-up" data-aos-delay="200">
                    <i class="bi bi-water"></i>
                    <h3>Swimming Pool</h3>
                    <p>Infinity pool with city views</p>
                </div>
                <div class="service-card" data-aos="fade-up" data-aos-delay="300">
                    <i class="bi bi-spa"></i>
                    <h3>Spa & Wellness</h3>
                    <p>Rejuvenating treatments and massages</p>
                </div>
                <div class="service-card" data-aos="fade-up" data-aos-delay="400">
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
            <div class="section-title" data-aos="fade-up">
                <h2>Contact Us</h2>
            </div>
            <div class="contact-grid">
                <div class="contact-info" data-aos="fade-right">
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
                <div class="contact-form" data-aos="fade-left">
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

    <!-- AOS Animation Library -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 1000,
            once: true
        });
    </script>
</body>
</html> 