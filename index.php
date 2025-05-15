<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MFsuite Hotel - Home</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"/>
  <link rel="stylesheet" href="./styles/home.css">

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
<style>
  #packageCarousel {
  max-width: 900px;      /* or adjust to your layout needs */
  margin: 0 auto;
}
#packageCarousel .carousel-inner {
  width: 100%;
  aspect-ratio: 3 / 2;   /* This keeps all slides 3:2 ratio */
  overflow: hidden;
  border-radius: 0.5rem;
  background: #222;      /* optional: fallback bg color */
}
#packageCarousel .carousel-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
}

</style>

<section class="container my-5">
  <h2 class="text-center mb-4">Special Packages</h2>
  <div id="packageCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner rounded shadow-sm">
      <div class="carousel-item active">
        <img src="assets/romantic.jpg" alt="Romantic Package">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-2">
          <h5>Romantic Getaway</h5>
          <p>Private dinner, spa, and a room with a view for couples.</p>
        </div>
      </div>
      <div class="carousel-item">
        <img src="assets/family.jpg" alt="Family Package">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-2">
          <h5>Family Fun Package</h5>
          <p>Includes family suite, kids’ activities, and free breakfast.</p>
        </div>
      </div>
      <div class="carousel-item">
        <img src="assets/business.jpg" alt="Business Package">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-2">
          <h5>Business Traveler</h5>
          <p>High-speed Wi-Fi, meeting room access, and express check-in.</p>
        </div>
      </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#packageCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#packageCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>
</section>


<!-- Room Types Carousel Section -->
<section class="container my-5">
  <h2 class="text-center mb-4">Explore Our Room Types</h2>
  <div id="roomTypesCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner rounded shadow-sm">
      <div class="carousel-item active">
        <img src="assets/rooms/deluxe.jpg" class="d-block w-100" alt="Deluxe Room">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-2">
          <h5>Deluxe Room</h5>
          <p>Modern comfort with a touch of luxury.</p>
        </div>
      </div>
      <div class="carousel-item">
        <img src="assets/rooms/suite.jpg" class="d-block w-100" alt="Suite Room">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-2">
          <h5>Executive Suite</h5>
          <p>Spacious living with premium amenities.</p>
        </div>
      </div>
      <div class="carousel-item">
        <img src="assets/rooms/family.jpg" class="d-block w-100" alt="Family Room">
        <div class="carousel-caption bg-dark bg-opacity-50 rounded p-2">
          <h5>Family Room</h5>
          <p>Perfect for families, with extra space and comfort.</p>
        </div>
      </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#roomTypesCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#roomTypesCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>
</section>

<!-- Services Carousel Section -->
<section class="container my-5">
  <h2 class="text-center mb-4">Our Services</h2>
  <div id="servicesCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner rounded shadow-sm">
      <div class="carousel-item active">
        <img src="assets/services/spa.jpg" class="d-block w-100" alt="Spa Service">
        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
          <h5>Spa & Wellness</h5>
          <p>Relax and rejuvenate with our world-class spa treatments.</p>
        </div>
      </div>
      <div class="carousel-item">
        <img src="assets/services/dining.jpg" class="d-block w-100" alt="Dining Service">
        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
          <h5>Fine Dining</h5>
          <p>Enjoy gourmet cuisine in our elegant restaurant.</p>
        </div>
      </div>
      <div class="carousel-item">
        <img src="assets/services/pool.jpg" class="d-block w-100" alt="Pool Service">
        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-2">
          <h5>Infinity Pool</h5>
          <p>Swim and relax in our stunning rooftop pool.</p>
        </div>
      </div>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#servicesCarousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#servicesCarousel" data-bs-slide="next">
      <span class="carousel-control-next-icon"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>
</section>



   <!-- Service Quality Testimonies Section -->
<section class="container my-5">
  <h2 class="text-center mb-4">What Our Guests Say</h2>
  <div class="row g-4">
    <div class="col-md-4">
      <div class="card shadow-sm border-0 text-center">
        <img src="assets/testimonials/guest1.jpg" class="rounded-circle mx-auto mt-3" style="width: 80px;" alt="Guest 1">
        <div class="card-body">
          <h5 class="card-title">Alexandra P.</h5>
          <p class="card-text">"Impeccable service and attention to detail. Highly recommend!"</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm border-0 text-center">
        <img src="assets/testimonials/guest2.jpg" class="rounded-circle mx-auto mt-3" style="width: 80px;" alt="Guest 2">
        <div class="card-body">
          <h5 class="card-title">Michael T.</h5>
          <p class="card-text">"The rooms were spotless and the staff was incredibly friendly."</p>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card shadow-sm border-0 text-center">
        <img src="assets/testimonials/guest3.jpg" class="rounded-circle mx-auto mt-3" style="width: 80px;" alt="Guest 3">
        <div class="card-body">
          <h5 class="card-title">Sara L.</h5>
          <p class="card-text">"Loved the spa and the breakfast buffet. Will come back!"</p>
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
