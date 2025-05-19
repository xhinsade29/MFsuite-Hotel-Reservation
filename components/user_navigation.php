<?php
$current_page = basename($_SERVER['PHP_SELF']);
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
    <link rel="stylesheet" href="../styles/user_navi_style.css">
</head>
<body>

<!-- HEADER -->
<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="../index.php" class="logo-container">
                <img src="../assets/MFsuites_logo.png" class="nav-logo" alt="MF Suites Logo">
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
    <a href="../pages/profile.php" class="<?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
        <i class="bi bi-person"></i> Profile
    </a>
    <a href="../pages/rooms.php" class="<?php echo ($current_page == 'rooms.php') ? 'active' : ''; ?>">
        <i class="bi bi-house"></i> Rooms
    </a>
    <a href="../pages/bookings.php" class="<?php echo ($current_page == 'bookings.php') ? 'active' : ''; ?>">
        <i class="bi bi-book"></i> Book Now
    </a>
    <a href="../pages/reservations.php" class="<?php echo ($current_page == 'reservations.php') ? 'active' : ''; ?>">
        <i class="bi bi-calendar-check"></i> My Reservations
    </a>
    <a href="../pages/notifications.php" class="<?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
        <i class="bi bi-bell"></i> Notifications
    </a>
    <a href="../pages/settings.php" class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>">
        <i class="bi bi-gear"></i> Settings
    </a>
    <hr>
    <a href="../pages/about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">
        <i class="bi bi-info-circle"></i> About
    </a>
    <a href="../pages/privacy.php" class="<?php echo ($current_page == 'privacy.php') ? 'active' : ''; ?>">
        <i class="bi bi-shield-check"></i> Privacy
    </a>
    <a href="../pages/logout.php" class="logout-btn">
        <i class="bi bi-box-arrow-right"></i> Log Out
    </a>
</aside>

</body>
</html>
