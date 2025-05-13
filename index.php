<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MFsuite Hotel - Home</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"/>
  <link rel="stylesheet" href="assets/css/styles.css"/>
  <style>
    body {
      color: #ffffff;
      font-family: 'Poppins', sans-serif;
      padding-top: 80px;
    }

    /* Hero Section with Image Overlay */
    .hero-section {
      position: relative;
      background: url('assets/image.png') no-repeat center center;
      background-size: cover;
      height: 500px;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
    }

    .hero-section:before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5); /* Overlay */
    }

    .hero-section h1 {
      font-size: 3rem;
      font-weight: 700;
      color: #FF8C00;
      text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.7);
    }

    .hero-section p {
      font-size: 1.2rem;
      color: #e5e5e5;
    }

    .btn-primary {
      background-color: #FF8C00;
      color: #fff;
      border-radius: 50px;
      padding: 12px 30px;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #d47f00;
      transform: scale(1.05);
    }

    /* Section Titles */
    .section-title {
      color: #FF8C00;
      font-weight: 700;
      margin-bottom: 1.5rem;
      text-transform: uppercase;
      font-size: 2.5rem;
      text-align: center;
      letter-spacing: 1.5px;
    }

    /* Card Style */
    .card {
      background-color: #1f1d2e;
      border: none;
      color: #fff;
      border-radius: 12px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 12px rgba(0, 0, 0, 0.5);
    }

    .card-img-top {
      border-radius: 12px 12px 0 0;
      height: 200px;
      object-fit: cover;
    }

    .card-body h5 {
      color: #FF8C00;
      font-size: 1.25rem;
      font-weight: 600;
    }

    .service-icon {
      font-size: 3rem;
      color: #FF8C00;
    }

    /* Testimonials Section */
    .testimonial-card {
      background-color: #1f1d2e;
      color: #fff;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
      padding: 2rem;
      text-align: center;
      transition: all 0.3s ease;
    }

    .testimonial-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 12px rgba(0, 0, 0, 0.4);
    }

    .testimonial-card h5 {
      color: #FF8C00;
      font-size: 1.5rem;
      margin-bottom: 1rem;
    }

    .testimonial-card p {
      color: #e5e5e5;
      font-style: italic;
    }

    .testimonial-card img {
      border-radius: 50%;
      width: 80px;
      height: 80px;
      object-fit: cover;
      margin-bottom: 1rem;
    }

    /* Contact Section */
    .contact-form input, .contact-form textarea {
      background-color: #1f1d2e;
      border: 1px solid #333;
      border-radius: 8px;
      color: #fff;
      width: 100%;
      padding: 12px;
      margin-bottom: 1rem;
    }

    .contact-form button {
      background-color: #FF8C00;
      color: #fff;
      border-radius: 12px;
      padding: 10px 30px;
      border: none;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    .contact-form button:hover {
      background-color: #d47f00;
    }

    /* Footer Section */
    footer {
      background-color: #11101d;
      padding: 4rem 0;
      color: #e5e5e5;
    }

    footer .social-icons a {
      font-size: 1.5rem;
      color: #FF8C00;
      margin-right: 15px;
      transition: color 0.3s;
    }

    footer .social-icons a:hover {
      color: #d47f00;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .hero-section h1 {
        font-size: 2rem;
      }
      .section-title {
        font-size: 1.8rem;
      }
    }
  </style>
</head>
<body>

  <?php include 'components/user_navigation.php'; ?>

<div class="container-fluid">
    <!-- Hero Section with Image Overlay -->
    <section class="hero-section">
    <div class="container position-relative"> <!-- Added position-relative -->
        <div class="content position-relative"> <!-- Added wrapper div with position-relative -->
            <h1 class="fw-bold">Welcome to MFsuites Hotel</h1>
            <p>Luxury & Comfort in One Place</p>
            <p class="lead mb-4">Experience world-class hospitality at MFsuites Hotel, where modern luxury meets traditional comfort. Our premium accommodations, exceptional service, and prime location make us the perfect choice for both business and leisure travelers.</p>
            <a href="reservation.php" class="btn btn-primary mt-3">Book a Room</a>
        </div>
    </div>
</section>

    <!-- Room Types -->
    <section class="mb-5">
      <div class="container">
        <h2 class="section-title text-center">Room Types</h2>
        <div class="row g-4">
          <div class="col-md-4">
            <div class="card shadow">
              <img src="assets/images/room1.jpg" class="card-img-top" alt="Deluxe Room">
              <div class="card-body">
                <h5 class="card-title">Deluxe Room</h5>
                <p class="card-text">Spacious room with king-sized bed, city view, and modern amenities.</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card shadow">
              <img src="assets/images/room2.jpg" class="card-img-top" alt="Suite Room">
              <div class="card-body">
                <h5 class="card-title">Suite Room</h5>
                <p class="card-text">Perfect for families, with separate living space and luxurious comfort.</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card shadow">
              <img src="assets/images/room3.jpg" class="card-img-top" alt="Standard Room">
              <div class="card-body">
                <h5 class="card-title">Standard Room</h5>
                <p class="card-text">Affordable yet stylish option with all essential facilities included.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Services -->
    <section class="mb-5">
      <div class="container">
        <h2 class="section-title text-center">Our Services</h2>
        <div class="row g-4 text-center">
          <div class="col-md-3 col-sm-6">
            <div class="card p-3 h-100 shadow">
              <div class="service-icon mb-2"><i class="bi bi-wifi"></i></div>
              <h5>Free Wi-Fi</h5>
              <p class="text-muted">High-speed internet access throughout the hotel.</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card p-3 h-100 shadow">
              <div class="service-icon mb-2"><i class="bi bi-cup-hot"></i></div>
              <h5>Room Service</h5>
              <p class="text-muted">Enjoy delicious meals and drinks delivered to your room.</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card p-3 h-100 shadow">
              <div class="service-icon mb-2"><i class="bi bi-car-front-fill"></i></div>
              <h5>Free Parking</h5>
              <p class="text-muted">Ample and secure parking space available for all guests.</p>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="card p-3 h-100 shadow">
              <div class="service-icon mb-2"><i class="bi bi-shield-check"></i></div>
              <h5>24/7 Security</h5>
              <p class="text-muted">Your safety is our priority with around-the-clock surveillance.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Content -->
    <section class="mb-5">
      <div class="container">
        <h2 class="section-title">Featured Offers</h2>
        <div class="row g-4">
          <div class="col-md-4">
            <div class="card shadow">
              <img src="assets/images/feature1.jpg" class="card-img-top" alt="Special Offer 1">
              <div class="card-body">
                <h5 class="card-title">Romantic Getaway</h5>
                <p class="card-text">Book a romantic getaway package with breakfast, spa, and more!</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card shadow">
              <img src="assets/images/feature2.jpg" class="card-img-top" alt="Special Offer 2">
              <div class="card-body">
                <h5 class="card-title">Family Package</h5>
                <p class="card-text">Perfect for families, includes meals and activities for all ages!</p>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="card shadow">
              <img src="assets/images/feature3.jpg" class="card-img-top" alt="Special Offer 3">
              <div class="card-body">
                <h5 class="card-title">Business Stay</h5>
                <p class="card-text">Enjoy convenience with all the amenities you need for work and relaxation.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="mb-5">
      <div class="container">
        <h2 class="section-title text-center">What Our Guests Say</h2>
        <div class="row g-4">
          <div class="col-md-4">
            <div class="testimonial-card">
              <img src="assets/images/testimonial1.jpg" alt="Testimonial 1">
              <h5>Jane Doe</h5>
              <p>"The best hotel experience I've ever had. Amazing service and luxurious rooms."</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="testimonial-card">
              <img src="assets/images/testimonial2.jpg" alt="Testimonial 2">
              <h5>John Smith</h5>
              <p>"Perfect place for business trips! Comfortable and convenient."</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="testimonial-card">
              <img src="assets/images/testimonial3.jpg" alt="Testimonial 3">
              <h5>Emily Johnson</h5>
              <p>"I loved the spa and the view from my room. Will definitely come back."</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section mb-5">
      <div class="container">
        <h2 class="section-title text-center">Contact Us</h2>
        <form action="#" method="POST" class="contact-form">
          <input type="text" name="name" placeholder="Your Name" required/>
          <input type="email" name="email" placeholder="Your Email" required/>
          <textarea name="message" placeholder="Your Message" rows="5" required></textarea>
          <button type="submit">Send Message</button>
        </form>
      </div>
    </section>
  </div>

  <?php include 'components/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
