<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MFsuite Hotel - Home</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"/>
  <link rel="stylesheet" href="styles/home.css">
 
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
