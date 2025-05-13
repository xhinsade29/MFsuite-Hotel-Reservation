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


<!-- Featured Content -->
<section class="mb-5">
  <div class="container">
    <h2 class="section-title text-center mb-4">Featured Offers</h2>
    <p class="text-center text-muted mb-5">Explore our exclusive packages designed to elevate your stay with us.</p>

    <div class="row g-4">
      <!-- Romantic Getaway -->
      <div class="col-md-4">
        <div class="card bg-dark text-white border-0 shadow-lg overflow-hidden position-relative">
          <img src="assets/romantic-atmosphere-valentines-day.jpg" class="card-img" alt="Romantic Getaway">
          <div class="card-img-overlay d-flex flex-column justify-content-end bg-dark bg-opacity-50">
            <h5 class="card-title text-warning">Romantic Getaway</h5>
            <p class="card-text">Book a package with breakfast, spa access, and candlelight dinner.</p>
            <a href="reservation.php" class="btn btn-sm btn-outline-warning mt-2">Book Now</a>
          </div>
        </div>
      </div>

      <!-- Family Package -->
      <div class="col-md-4">
        <div class="card bg-dark text-white border-0 shadow-lg overflow-hidden position-relative">
          <img src="assets/close-up-people-traveling-by-bus.jpg" class="card-img" alt="Family Package">
          <div class="card-img-overlay d-flex flex-column justify-content-end bg-dark bg-opacity-50">
            <h5 class="card-title text-warning">Family Package</h5>
            <p class="card-text">Includes full-board meals and fun activities for everyone.</p>
            <a href="reservation.php" class="btn btn-sm btn-outline-warning mt-2">Book Now</a>
          </div>
        </div>
      </div>

      <!-- Business Stay -->
      <div class="col-md-4">
        <div class="card bg-dark text-white border-0 shadow-lg overflow-hidden position-relative">
          <img src="assets/medium-shot-woman-working-by-pool.jpg" class="card-img" alt="Business Stay">
          <div class="card-img-overlay d-flex flex-column justify-content-end bg-dark bg-opacity-50">
            <h5 class="card-title text-warning">Business Stay</h5>
            <p class="card-text">Work-friendly environment with complimentary breakfast and meeting access.</p>
            <a href="reservation.php" class="btn btn-sm btn-outline-warning mt-2">Book Now</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>


<!-- Room Types -->
<section class="mb-5">
  <div class="container">
    <h2 class="section-title text-center mb-4">Room Types</h2>
    <p class="text-center mb-4">
      Choose from our variety of cozy, elegant, and affordable rooms designed for your comfort.
    </p>
    <div class="row g-4">

      <!-- Deluxe Room -->
      <div class="col-md-4">
        <div class="card shadow h-100">
          <img src="assets/images/room1.jpg" class="card-img-top" alt="Deluxe Room">
          <div class="card-body">
            <h5 class="card-title">Deluxe Room</h5>
            <p class="card-text">Spacious room with king-sized bed, city view, and modern amenities.</p>
            <p class="text-warning fw-bold">₱2,999 / night</p>
          </div>
        </div>
      </div>

      <!-- Suite Room -->
      <div class="col-md-4">
        <div class="card shadow h-100">
          <img src="assets/images/room2.jpg" class="card-img-top" alt="Suite Room">
          <div class="card-body">
            <h5 class="card-title">Suite Room</h5>
            <p class="card-text">Luxurious suite with private living area, ideal for families or long stays.</p>
            <p class="text-warning fw-bold">₱4,500 / night</p>
          </div>
        </div>
      </div>

      <!-- Standard Room -->
      <div class="col-md-4">
        <div class="card shadow h-100">
          <img src="assets/images/room3.jpg" class="card-img-top" alt="Standard Room">
          <div class="card-body">
            <h5 class="card-title">Standard Room</h5>
            <p class="card-text">Affordable comfort with a queen-sized bed and minimalist interior.</p>
            <p class="text-warning fw-bold">₱1,499 / night</p>
          </div>
        </div>
      </div>

      <!-- Family Room -->
      <div class="col-md-4">
        <div class="card shadow h-100">
          <img src="assets/images/room4.jpg" class="card-img-top" alt="Family Room">
          <div class="card-body">
            <h5 class="card-title">Family Room</h5>
            <p class="card-text">Two double beds, dining area, and a cozy space for the whole family.</p>
            <p class="text-warning fw-bold">₱3,200 / night</p>
          </div>
        </div>
      </div>

      <!-- Executive Room -->
      <div class="col-md-4">
        <div class="card shadow h-100">
          <img src="assets/images/room5.jpg" class="card-img-top" alt="Executive Room">
          <div class="card-body">
            <h5 class="card-title">Executive Room</h5>
            <p class="card-text">Designed for business travelers, complete with workspace and fast Wi-Fi.</p>
            <p class="text-warning fw-bold">₱3,700 / night</p>
          </div>
        </div>
      </div>

      <!-- Budget Room -->
      <div class="col-md-4">
        <div class="card shadow h-100">
          <img src="assets/images/room6.jpg" class="card-img-top" alt="Budget Room">
          <div class="card-body">
            <h5 class="card-title">Budget Room</h5>
            <p class="card-text">Simple, clean, and cozy — perfect for short stays and solo travelers.</p>
            <p class="text-warning fw-bold">₱999 / night</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Services -->
<section class="mb-5">
  <div class="container">
    <h2 class="section-title text-center mb-4">Our Services</h2>
    <p class="text-center mb-4">Experience top-notch hospitality with a wide range of services crafted for your comfort and convenience.</p>
    <div class="row g-4 text-center">

      <!-- Free Wi-Fi -->
      <div class="col-md-4 col-sm-6">
        <div class="card p-4 h-100 shadow">
          <div class="service-icon mb-3"><i class="bi bi-wifi"></i></div>
          <h5 class="text-warning">High-Speed Wi-Fi</h5>
          <p class="text-muted">Unlimited access to fast and reliable internet in all rooms and public areas.</p>
        </div>
      </div>

      <!-- In-Room Dining -->
      <div class="col-md-4 col-sm-6">
        <div class="card p-4 h-100 shadow">
          <div class="service-icon mb-3"><i class="bi bi-cup-hot"></i></div>
          <h5 class="text-warning">In-Room Dining</h5>
          <p class="text-muted">Order meals, snacks, and drinks 24/7 with our convenient room service menu.</p>
        </div>
      </div>

      <!-- Housekeeping -->
      <div class="col-md-4 col-sm-6">
        <div class="card p-4 h-100 shadow">
          <div class="service-icon mb-3"><i class="bi bi-broom"></i></div>
          <h5 class="text-warning">Daily Housekeeping</h5>
          <p class="text-muted">Spotless rooms with daily cleaning and linen replacement for your comfort.</p>
        </div>
      </div>

      <!-- Parking -->
      <div class="col-md-4 col-sm-6">
        <div class="card p-4 h-100 shadow">
          <div class="service-icon mb-3"><i class="bi bi-car-front-fill"></i></div>
          <h5 class="text-warning">Secure Parking</h5>
          <p class="text-muted">Free and safe on-site parking with 24/7 monitoring.</p>
        </div>
      </div>

      <!-- Airport Shuttle -->
      <div class="col-md-4 col-sm-6">
        <div class="card p-4 h-100 shadow">
          <div class="service-icon mb-3"><i class="bi bi-bus-front-fill"></i></div>
          <h5 class="text-warning">Airport Shuttle</h5>
          <p class="text-muted">Hassle-free transfers to and from the airport upon request.</p>
        </div>
      </div>

      <!-- 24/7 Security -->
      <div class="col-md-4 col-sm-6">
        <div class="card p-4 h-100 shadow">
          <div class="service-icon mb-3"><i class="bi bi-shield-check"></i></div>
          <h5 class="text-warning">24/7 Security</h5>
          <p class="text-muted">Feel safe with round-the-clock surveillance and professional security staff.</p>
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
