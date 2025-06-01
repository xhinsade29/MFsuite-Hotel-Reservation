<?php
session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
$current_page = 'privacy.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy - MF Suites Hotel</title>
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
        body.light-mode .privacy-container {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode .privacy-title,
        body.light-mode .privacy-section-title {
            color: #ff8c00 !important;
        }
        body.light-mode .privacy-text,
        body.light-mode .privacy-list,
        body.light-mode .privacy-contact {
            color: #23234a !important;
        }
        /* End light mode overrides */
        .privacy-container {
            max-width: 900px;
            margin: 40px auto;
            background: #23234a;
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            padding: 48px 32px;
        }
        .privacy-title { color: #ffa533; font-weight: 700; text-align: center; margin-bottom: 18px; }
        .privacy-section-title { color: #ffa533; font-weight: 600; margin-top: 32px; margin-bottom: 10px; }
        .privacy-text { color: #bdbdbd; font-size: 1.08em; margin-bottom: 18px; }
        .privacy-list { color: #bdbdbd; font-size: 1.05em; margin-bottom: 18px; padding-left: 18px; }
        .privacy-contact { margin-top: 36px; text-align: center; }
        .privacy-contact i { color: #ffa533; margin-right: 8px; }
        @media (max-width: 600px) {
            .privacy-container { padding: 24px 8px; }
            .privacy-title { font-size: 1.4em; }
        }
    </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">
<?php include '../components/user_navigation.php'; ?>
<div class="privacy-container">
    <img src="../assets/MFsuites_logo.png" alt="MF Suites Hotel Logo" style="display:block; margin:0 auto 18px; max-width:120px;">
    <h1 class="privacy-title">Privacy Policy</h1>
    <div class="privacy-text">
        At MF Suites Hotel, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, and safeguard your data when you use our hotel reservation system.
    </div>
    <h4 class="privacy-section-title">What Information We Collect</h4>
    <ul class="privacy-list">
        <li>Personal details (name, email address, phone number, address)</li>
        <li>Payment and billing information</li>
        <li>Reservation and booking details</li>
        <li>Account credentials and preferences</li>
        <li>Any information you provide through forms or customer support</li>
    </ul>
    <h4 class="privacy-section-title">How We Use Your Information</h4>
    <ul class="privacy-list">
        <li>To process and manage your reservations and bookings</li>
        <li>To communicate with you regarding your account or reservations</li>
        <li>To improve our services and personalize your experience</li>
        <li>To comply with legal obligations and prevent fraud</li>
    </ul>
    <h4 class="privacy-section-title">Data Security</h4>
    <div class="privacy-text">
        We implement industry-standard security measures to protect your personal information from unauthorized access, disclosure, alteration, or destruction. Access to your data is restricted to authorized personnel only.
    </div>
    <h4 class="privacy-section-title">Your Rights</h4>
    <ul class="privacy-list">
        <li>You have the right to access, update, or delete your personal information at any time.</li>
        <li>You may request a copy of your data or ask us to correct inaccuracies.</li>
        <li>To exercise your rights, please contact us using the information below.</li>
    </ul>
    <h4 class="privacy-section-title">Contact Us</h4>
    <div class="privacy-contact">
        <div><i class="bi bi-geo-alt"></i> 123 Hotel Street, City Center</div>
        <div><i class="bi bi-telephone"></i> +1 234 567 8900</div>
        <div><i class="bi bi-envelope"></i> privacy@mfsuites.com</div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 