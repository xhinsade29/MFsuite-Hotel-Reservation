<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MF Suites Hotel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles/user_navi_style.css">
</head>
<body>

<!-- HEADER -->
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="index.php" class="logo-container">
                <img src="assets/MFsuites_logo.png" class="nav-logo">
                <h1 class="hotel-name">MF Suites Hotel</h1>
            </a>
        </div>

        <div class="nav-right">
            <div class="search-box">
                <input type="search" placeholder="Search rooms...">
                <i class="bi bi-search"></i>
            </div>
            <button class="notifications">
                <i class="bi bi-bell"></i>
                <span class="badge">2</span>
            </button>
            <button class="profile-trigger">
                <img src="https://ui-avatars.com/api/?name=Guest+User&background=FF8C00&color=fff" 
                     alt="User" class="avatar">
                <span class="username">Guest User</span>
                <i class="bi bi-chevron-down"></i>
            </button>
        </div>
    </div>
</nav>

<!-- SIDEBAR -->
<aside class="sidebar">
    <a href="profile.php" class="active"><i class="bi bi-person"></i> Profile</a>
    <a href="pages/rooms.php"><i class="bi bi-house"></i> Rooms</a>
    <a href="services.php"><i class="bi bi-tools"></i> Services</a>
    <a href="bookings.php"><i class="bi bi-book"></i> Book Now</a>
    <a href="reservations.php"><i class="bi bi-calendar-check"></i> My Reservations</a>
    <a href="notifications.php"><i class="bi bi-bell"></i> Notifications</a>
    <a href="settings.php"><i class="bi bi-gear"></i> Settings</a>
    <hr style="border-color: rgba(255, 255, 255, 0.1);">
    <a href="about.php"><i class="bi bi-info-circle"></i> About</a>
    <a href="privacy.php"><i class="bi bi-shield-check"></i> Privacy</a>
    <a href="logout.php" class="logout-btn"><i class="bi bi-box-arrow-right"></i> Log Out</a>
</aside>

</body>
</html>
