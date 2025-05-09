<div class="sidebar">
    <div class="logo-details">
        <i class='bx bxs-hotel'></i>
        <span class="logo_name">MFsuite Hotel</span>
    </div>
    <ul class="nav-links">
    
        <li>
            <a href="rooms.php" class="<?php echo $page == 'rooms' ? 'active' : ''; ?>">
                <i class='bx bxs-door'></i>
                <span class="link_name">Rooms</span>
            </a>
        </li>
        <li>
            <a href="reservations.php" class="<?php echo $page == 'reservations' ? 'active' : ''; ?>">
                <i class='bx bxs-calendar'></i>
                <span class="link_name">Reservations</span>
            </a>
        </li>
        <li>
            <a href="service.php" class="<?php echo $page == 'guests' ? 'active' : ''; ?>">
                <i class='bx bxs-user'></i>
                <span class="link_name">Service</span>
            </a>
        </li>
        <li>
        
        <li>
            <a href="logout.php">
                <i class='bx bxs-log-out'></i>
                <span class="link_name">Logout</span>
            </a>
        </li>
    </ul>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    width: 260px;
    background: #11101d;
    z-index: 100;
    padding: 20px 0;
}

.sidebar .logo-details {
    height: 60px;
    width: 100%;
    display: flex;
    align-items: center;
    padding: 0 20px;
}

.sidebar .logo-details i {
    font-size: 30px;
    color: #fff;
    margin-right: 10px;
}

.sidebar .logo-details .logo_name {
    color: #fff;
    font-size: 22px;
    font-weight: 600;
}

.sidebar .nav-links {
    margin-top: 20px;
    padding: 0;
}

.sidebar .nav-links li {
    list-style: none;
}

.sidebar .nav-links li a {
    display: flex;
    align-items: center;
    text-decoration: none;
    padding: 14px 20px;
    color: #fff;
    transition: all 0.3s ease;
}

.sidebar .nav-links li a:hover,
.sidebar .nav-links li a.active {
    background: #1d1b31;
}

.sidebar .nav-links li a i {
    font-size: 22px;
    margin-right: 15px;
}

.sidebar .nav-links li a .link_name {
    font-size: 16px;
    font-weight: 400;
}
</style>
