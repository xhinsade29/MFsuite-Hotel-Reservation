<nav class="navbar">
    <div class="nav-container">
        <div class="logo">
            <a href="index.php">
                <img src="assets/MFsuites_logo.png" alt="MFsuite Hotel Logo" class="nav-logo">
            </a>
        </div>
        
    </div>
</nav>

<style>
.navbar {
    background-color: #fff;
    padding: 1rem 0;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 2rem;
}

.nav-logo {
    height: 50px;
    width: auto;
}

.nav-links {
    list-style: none;
    display: flex;
    gap: 2rem;
}

.nav-links li a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: color 0.3s ease;
}

.nav-links li a:hover {
    color: #007bff;
}
</style>