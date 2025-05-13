<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="index.php" class="logo-container">
                <img src="../assets/MFsuites_logo.png" class="nav-logo">
                <h1 class="hotel-name">MF Suites Hotel Reservation</h1>
            </a>
        </div>
        <div class="burger-menu">
            <div class="burger-icon">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="menu-items">
                <a href="profile.php">Profile</a>
                <a href="reservations.php">My Reservations</a>
                <a href="about.php">About</a>
                <a href="privacy.php">Privacy</a>
                <a href="logout.php">Log Out</a>
            </div>
        </div>
    </div>
</nav>

<style>
.navbar {
    background-color: #11101d;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    padding: 0 1rem;
}

.nav-logo {
    height: 40px;
    width: auto;
}

.logo-container {
    display: flex;
    align-items: center;
    text-decoration: none;
    gap: 1rem;
}

.hotel-name {
    color: white;
    font-size: 1.2rem;
    margin: 0;
    font-weight: 500;
}

.burger-menu {
    margin-left: auto;
    position: relative;
}

.burger-icon {
    cursor: pointer;
    padding: 10px;
}

.burger-icon span {
    display: block;
    width: 25px;
    height: 3px;
    background-color: white;
    margin: 5px 0;
    transition: 0.4s;
}

.menu-items {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    background-color: #11101d;
    padding: 1rem;
    border-radius: 4px;
    min-width: 200px;
}

.menu-items.active {
    display: block;
}

.menu-items a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 10px;
    transition: 0.3s;
}

.menu-items a:hover {
    background-color: #2d2b3e;
}
</style>

<script>
document.querySelector('.burger-icon').addEventListener('click', function() {
    document.querySelector('.menu-items').classList.toggle('active');
});

// Close menu when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.burger-menu')) {
        document.querySelector('.menu-items').classList.remove('active');
    }
});
</script>