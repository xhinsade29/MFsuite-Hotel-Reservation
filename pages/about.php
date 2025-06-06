<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
$current_page = 'about.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About Us - MF Suites Hotel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #1e1e2f; color: #fff; font-family: 'Poppins', sans-serif; }
        /* Light mode overrides */
        body.light-mode {
            background: #f8f9fa !important;
            color: #23234a !important;
        }
        body.light-mode .about-container {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode .about-hero,
        body.light-mode .about-hero h1,
        body.light-mode .about-hero p {
            color: #23234a !important;
        }
        body.light-mode .about-hero {
            background: linear-gradient(rgba(255,255,255,0.85), rgba(255,255,255,0.92)), url('../assets/rooms/standard.avif') center/cover no-repeat !important;
        }
        body.light-mode .about-hero h1,
        body.light-mode .about-section-title,
        body.light-mode .developer-section .name {
            color: #ff8c00 !important;
        }
        body.light-mode, body.light-mode .about-list, body.light-mode .about-timeline, body.light-mode .about-contact, body.light-mode .about-team .role, body.light-mode .developer-section .role {
            color: #23234a !important;
        }
        body.light-mode .team-member {
            background: linear-gradient(135deg, #fff 80%, #ffe5b4 100%) !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .team-member .name {
            color: #ff8c00 !important;
        }
        body.light-mode .developer-section {
            background: linear-gradient(135deg, #fff 80%, #ffe5b4 100%) !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4 !important;
        }
        body.light-mode .dev-badge {
            background: #ff8c00 !important;
            color: #fff !important;
        }
        /* End light mode overrides */
        .about-hero {
            background: linear-gradient(rgba(30,30,47,0.85), rgba(35,35,74,0.92)), url('../assets/rooms/standard.avif') center/cover no-repeat;
            padding: 80px 0 40px 0;
            text-align: center;
            margin-top: 0;
            border-radius: 18px 18px 0 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-left: 0;
        }
        .about-hero img { max-width: 120px; margin-bottom: 18px; }
        .about-hero h1 { color: #ffa533; font-weight: 700; font-size: 2.6em; margin-bottom: 12px; }
        .about-hero p { color: #fff; font-size: 1.2em; max-width: 600px; margin: 0 auto 18px; }
        .about-container {
            max-width: 900px;
            margin: 40px auto;
            background: #23234a;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 48px 32px;
        }
        .about-section-title { color: #ffa533; font-weight: 600; margin-top: 32px; margin-bottom: 10px; }
        .about-list { color: #bdbdbd; font-size: 1.05em; margin-bottom: 18px; }
        .about-contact { margin-top: 36px; text-align: center; }
        .about-contact i { color: #ffa533; margin-right: 8px; }
        /* Timeline/Highlights */
        .about-timeline { margin: 40px 0 0 0; padding: 0; list-style: none; }
        .about-timeline li { position: relative; padding-left: 32px; margin-bottom: 28px; }
        .about-timeline li:before { content: '\2022'; color: #ffa533; position: absolute; left: 0; font-size: 1.5em; top: 0; }
        .about-timeline .highlight-title { color: #ffa533; font-weight: 500; }
        /* Team */
        .about-team {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            justify-content: center;
            margin: 40px 0 0 0;
        }
        .team-member {
            background: linear-gradient(135deg, #23234a 80%, #2d2d5a 100%);
            border-radius: 18px;
            padding: 32px 22px 28px 22px;
            text-align: center;
            width: 220px;
            box-shadow: 0 6px 32px rgba(0,0,0,0.18), 0 1.5px 0 #ffa533;
            transition: transform 0.18s, box-shadow 0.18s;
            position: relative;
        }
        .team-member:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 12px 36px rgba(255,140,0,0.18), 0 2px 0 #ffa533;
        }
        .team-member:hover img {
            transform: scale(1.18);
            transition: transform 0.22s cubic-bezier(0.4, 0.2, 0.2, 1);
        }
        .team-member img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 14px;
            border: 3px solid #ffa533;
            box-shadow: 0 2px 12px rgba(255,140,0,0.10);
            transition: transform 0.22s cubic-bezier(0.4, 0.2, 0.2, 1);
        }
        .team-member .name {
            color: #ffa533;
            font-weight: 700;
            margin-bottom: 2px;
            font-size: 1.13em;
        }
        .team-member .role {
            color: #bdbdbd;
            font-size: 1em;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .team-member .about-list {
            color: #bdbdbd;
            font-size: 0.98em;
            margin-top: 10px;
        }
        /* Testimonials */
        .about-testimonials { margin: 48px 0 0 0; }
        .carousel-item { text-align: center; }
        .testimonial-card { background: #18182f; border-radius: 14px; padding: 32px 18px; color: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.10); max-width: 500px; margin: 0 auto; }
        .testimonial-card img { width: 60px; height: 60px; border-radius: 50%; margin-bottom: 12px; }
        .testimonial-card .testimonial-text { color: #bdbdbd; font-style: italic; margin-bottom: 10px; }
        .testimonial-card .testimonial-author { color: #ffa533; font-weight: 600; }
        /* Developer Card */
        .developer-section {
            background: linear-gradient(135deg, #23234a 80%, #23234a 100%);
            border-radius: 22px;
            box-shadow: 0 6px 32px rgba(0,0,0,0.18), 0 1.5px 0 #ffa533;
            padding: 40px 24px 32px 24px;
            margin: 48px auto 32px auto;
            max-width: 340px;
            text-align: center;
            position: relative;
        }
        .developer-section .dev-badge {
            position: absolute;
            top: 18px;
            right: 18px;
            background: #ffa533;
            color: #23234a;
            font-size: 0.85em;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 12px;
            letter-spacing: 1px;
            box-shadow: 0 2px 8px rgba(255,140,0,0.10);
        }
        .developer-section img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 16px;
            border: 3px solid #ffa533;
            box-shadow: 0 2px 12px rgba(255,140,0,0.10);
        }
        .developer-section .name {
            color: #ffa533;
            font-weight: 700;
            font-size: 1.18em;
            margin-bottom: 2px;
        }
        .developer-section .role {
            color: #bdbdbd;
            font-size: 1em;
            font-weight: 500;
            margin-bottom: 10px;
        }
        .developer-section .about-list {
            color: #bdbdbd;
            font-size: 0.98em;
            margin-top: 10px;
        }
        @media (max-width: 991px) {
            .about-hero, .about-container { margin-left: 0 !important; margin-right: 0 !important; }
        }
        @media (max-width: 600px) {
            .about-container { padding: 24px 8px; }
            .about-hero h1 { font-size: 1.4em; }
            .about-team { flex-direction: column; gap: 18px; }
        }
    </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
<?php include '../components/user_navigation.php'; ?>
<div class="about-container">
    <div class="about-hero" style="margin-left:0;">
        <img src="../assets/MFsuites_logo.png" alt="MF Suites Hotel Logo">
        <h1>About MF Suites Hotel</h1>
        <p>Experience luxury, comfort, and exceptional hospitality in the heart of the city. Discover our story, our people, and what makes us the hotel of choice for travelers.</p>
    </div>
    <h4 class="about-section-title">Our Mission</h4>
    <div class="about-list">
        To provide guests with a luxurious and memorable experience through personalized service, modern facilities, and a welcoming atmosphere.
    </div>
    <h4 class="about-section-title">Our Vision</h4>
    <div class="about-list">
        To be the leading hotel of choice for travelers seeking comfort, style, and exceptional hospitality in the city.
    </div>
    <h4 class="about-section-title">Hotel Highlights</h4>
    <ul class="about-timeline">
        <li><span class="highlight-title">2015:</span> MF Suites Hotel opens its doors, setting a new standard for luxury in the city.</li>
        <li><span class="highlight-title">2017:</span> Awarded "Best Urban Hotel" by City Travel Magazine.</li>
        <li><span class="highlight-title">2019:</span> Launched our signature Spa & Wellness Center and rooftop pool.</li>
        <li><span class="highlight-title">2022:</span> Renovated all rooms with smart technology and eco-friendly amenities.</li>
    </ul>
    <h4 class="about-section-title">Meet Our Team</h4>
    <div class="about-team">
        <div class="team-member">
            <img src="../assets/General Manager (2).avif" alt="General Manager">
            <div class="name">Maria Santos</div>
            <div class="role">General Manager</div>
        </div>
        <div class="team-member">
            <img src="../assets/Front Desk Supervisor.avif" alt="Front Desk Supervisor">
            <div class="name">John Lee</div>
            <div class="role">Front Desk Supervisor</div>
        </div>
        <div class="team-member">
            <img src="../assets/Head Chef.jpg" alt="Head Chef">
            <div class="name">Thomaz Cruz</div>
            <div class="role">Head Chef</div>
        </div>
        <div class="team-member">
            <img src="../assets/Concierge.avif" alt="Concierge">
            <div class="name">Samuel Reyes</div>
            <div class="role">Concierge</div>
        </div>
    </div>
    <h3 class="text-warning">System Developer</h3>
    <div class="about-team">
        <div class="team-member">
            <img src="../assets/system_developer.jfif" alt="Lead Developer">
            <div class="name">Kristine L. Lopez</div>
            <div class="role">Lead Developer</div>
        </div>
        <div class="team-member">
            <img src="../assets/project_manager.jpg" alt="Project">
            <div class="name">Evelyn Saldivar</div>
            <div class="role">Project Manager</div>
        </div>
        <div class="team-member">
            <img src="../assets/dev_tester.jpg" alt="Tester">
            <div class="name">Trisha Ann Satina</div>
            <div class="role">Project Tester</div>
        </div>
    </div>
    <h4 class="about-section-title">Contact Us</h4>
    <div class="about-contact">
        <div><i class="bi bi-geo-alt"></i> 123 Hotel Street, City Center</div>
        <div><i class="bi bi-telephone"></i> +1 234 567 8900</div>
        <div><i class="bi bi-envelope"></i> info@mfsuites.com</div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 