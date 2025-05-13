<!-- Enhanced Hotel Management Sidebar with modern design -->
<div class="sidebar" id="sidebar">
    <!-- Logo and brand section with animated accent -->
    <div class="sidebar-header">
        <div class="brand-wrapper">
            <div class="logo-accent"></div>
            <i class="bi bi-buildings-fill"></i>
            <span class="brand-name">MFsuite Hotel</span>
        </div>
        <button class="sidebar-toggle d-md-none" id="sidebarToggle">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    
    <!-- Navigation links with hover effects and active states -->
    <ul class="nav-links">
        <li class="nav-item">
            <a href="pages/dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <div class="icon-wrapper">
                    <i class="bi bi-grid-1x2-fill"></i>
                </div>
                <span>Dashboard</span>
                <div class="hover-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="pages/rooms.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rooms.php' ? 'active' : ''; ?>">
                <div class="icon-wrapper">
                    <i class="bi bi-door-closed-fill"></i>
                </div>
                <span>Rooms</span>
                <div class="hover-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="pages/reservations.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : ''; ?>">
                <div class="icon-wrapper">
                    <i class="bi bi-calendar2-check-fill"></i>
                </div>
                <span>Reservations</span>
                <div class="hover-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="pages/guests.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'guests.php' ? 'active' : ''; ?>">
                <div class="icon-wrapper">
                    <i class="bi bi-people-fill"></i>
                </div>
                <span>Guests</span>
                <div class="hover-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="pages/services.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'services.php' ? 'active' : ''; ?>">
                <div class="icon-wrapper">
                    <i class="bi bi-gem"></i>
                </div>
                <span>Services</span>
                <div class="hover-indicator"></div>
            </a>
        </li>
        <li class="nav-item">
            <a href="pages/payments.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>">
                <div class="icon-wrapper">
                    <i class="bi bi-credit-card-fill"></i>
                </div>
                <span>Payments</span>
                <div class="hover-indicator"></div>
            </a>
        </li>
    </ul>
    
    <!-- Hotel status summary cards -->
    <div class="status-section">
        <div class="status-card">
            <div class="status-icon available">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="status-info">
                <span class="status-label">Available</span>
                <span class="status-value">24</span>
            </div>
        </div>
        <div class="status-card">
            <div class="status-icon occupied">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="status-info">
                <span class="status-label">Occupied</span>
                <span class="status-value">18</span>
            </div>
        </div>
    </div>
    
    <!-- User profile section with enhanced design -->
    <div class="profile-section">
        <div class="profile-avatar">
            <img src="https://ui-avatars.com/api/?name=Admin+User&background=4869DE&color=fff" alt="Admin User" class="avatar-img">
            <span class="status-indicator online"></span>
        </div>
        <div class="profile-info">
            <h6 class="profile-name">Admin User</h6>
            <p class="profile-role">Administrator</p>
        </div>
        <div class="profile-actions">
            <button class="action-btn">
                <i class="bi bi-gear-fill"></i>
            </button>
            <button class="action-btn logout">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
/* Modern hotel management sidebar styling */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

:root {
    /* Main color scheme */
    --primary: #4869DE;
    --primary-light: rgba(72, 105, 222, 0.15);
    --primary-dark: #3451C6;
    --secondary: #2A394F;
    --text-primary: #FFFFFF;
    --text-secondary: rgba(255, 255, 255, 0.7);
    --accent: #FFB74D;
    
    /* Status colors */
    --available: #4CAF50;
    --occupied: #F44336;
    --maintenance: #FF9800;
    --online: #4CAF50;
    
    /* Animation speeds */
    --transition-fast: 0.2s;
    --transition-normal: 0.3s;
    --transition-slow: 0.5s;
    
    /* Layout */
    --sidebar-width: 280px;
    --sidebar-collapsed-width: 80px;
    --border-radius: 12px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

.sidebar {
    position: fixed;
    height: 100vh;
    width: var(--sidebar-width);
    background: linear-gradient(135deg, var(--secondary) 0%, #1A2536 100%);
    color: var(--text-primary);
    overflow-y: auto;
    z-index: 1000;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    transition: all var(--transition-normal) ease;
    display: flex;
    flex-direction: column;
}

/* Custom scrollbar for sidebar */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

/* Logo and brand section */
.sidebar-header {
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
}

.brand-wrapper {
    display: flex;
    align-items: center;
    position: relative;
}

.logo-accent {
    position: absolute;
    left: -5px;
    top: -5px;
    height: 40px;
    width: 40px;
    background: var(--accent);
    border-radius: 8px;
    opacity: 0.8;
    transform: rotate(-10deg);
    z-index: -1;
    transition: all var(--transition-normal) ease;
}

.brand-wrapper:hover .logo-accent {
    transform: rotate(0deg) scale(1.1);
}

.brand-wrapper i {
    font-size: 24px;
    color: var(--text-primary);
    margin-right: 12px;
    z-index: 1;
}

.brand-name {
    font-size: 20px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.sidebar-toggle {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    font-size: 20px;
    cursor: pointer;
    transition: all var(--transition-fast) ease;
}

.sidebar-toggle:hover {
    color: var(--text-primary);
}

/* Navigation section */
.nav-links {
    list-style: none;
    padding: 10px 15px;
    margin: 10px 0;
    flex: 1;
}

.nav-item {
    margin-bottom: 8px;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-radius: var(--border-radius);
    text-decoration: none;
    color: var(--text-secondary);
    transition: all var(--transition-normal) ease;
    position: relative;
    overflow: hidden;
}

.nav-link .icon-wrapper {
    min-width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    margin-right: 12px;
    background: transparent;
    transition: all var(--transition-normal) ease;
}

.nav-link i {
    font-size: 18px;
    transition: all var(--transition-normal) ease;
}

.nav-link span {
    font-size: 15px;
    font-weight: 500;
    white-space: nowrap;
    transition: all var(--transition-normal) ease;
}

.hover-indicator {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 5px;
    background: var(--primary);
    border-radius: 0 3px 3px 0;
    transform: translateX(-5px);
    opacity: 0;
    transition: all var(--transition-normal) ease;
}

.nav-link:hover {
    color: var(--text-primary);
    background: var(--primary-light);
}

.nav-link:hover .icon-wrapper {
    background: var(--primary);
}

.nav-link:hover .hover-indicator {
    transform: translateX(0);
    opacity: 1;
}

.nav-link.active {
    color: var(--text-primary);
    background: var(--primary);
}

.nav-link.active .icon-wrapper {
    background: rgba(255, 255, 255, 0.2);
}

/* Status section with cards */
.status-section {
    padding: 15px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.status-card {
    background: rgba(255, 255, 255, 0.07);
    border-radius: var(--border-radius);
    padding: 12px;
    display: flex;
    align-items: center;
    transition: all var(--transition-normal) ease;
}

.status-card:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-3px);
}

.status-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
}

.status-icon.available {
    background: rgba(76, 175, 80, 0.2);
    color: var(--available);
}

.status-icon.occupied {
    background: rgba(244, 67, 54, 0.2);
    color: var(--occupied);
}

.status-info {
    display: flex;
    flex-direction: column;
}

.status-label {
    font-size: 12px;
    color: var(--text-secondary);
}

.status-value {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
}

/* Profile section */
.profile-section {
    margin-top: auto;
    padding: 15px;
    background: rgba(0, 0, 0, 0.2);
    display: flex;
    align-items: center;
    position: relative;
}

.profile-avatar {
    position: relative;
    margin-right: 12px;
}

.avatar-img {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.status-indicator {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid var(--secondary);
}

.status-indicator.online {
    background-color: var(--online);
}

.profile-info {
    flex: 1;
}

.profile-name {
    font-size: 14px;
    font-weight: 500;
    margin: 0;
    color: var(--text-primary);
}

.profile-role {
    font-size: 12px;
    color: var(--text-secondary);
    margin: 0;
}

.profile-actions {
    display: flex;
    align-items: center;
}

.action-btn {
    background: transparent;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    margin-left: 5px;
    cursor: pointer;
    transition: all var(--transition-fast) ease;
}

.action-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
}

.action-btn.logout:hover {
    background: rgba(244, 67, 54, 0.2);
    color: #F44336;
}

/* Responsive design */
@media (max-width: 992px) {
    .sidebar {
        width: var(--sidebar-collapsed-width);
    }
    
    .brand-name, 
    .nav-link span, 
    .status-section,
    .profile-info {
        display: none;
    }
    
    .nav-link {
        justify-content: center;
        padding: 12px;
    }
    
    .nav-link .icon-wrapper {
        margin-right: 0;
    }
    
    .profile-section {
        justify-content: center;
    }
    
    .profile-avatar {
        margin-right: 0;
    }
    
    .profile-actions {
        position: absolute;
        bottom: 70px;
        right: 50%;
        transform: translateX(50%);
        flex-direction: column;
    }
    
    .action-btn {
        margin-left: 0;
        margin-bottom: 5px;
    }
    
    .sidebar.expanded {
        width: var(--sidebar-width);
    }
    
    .sidebar.expanded .brand-name, 
    .sidebar.expanded .nav-link span, 
    .sidebar.expanded .status-section,
    .sidebar.expanded .profile-info {
        display: block;
    }
    
    .sidebar.expanded .nav-link {
        justify-content: flex-start;
        padding: 12px 15px;
    }
    
    .sidebar.expanded .nav-link .icon-wrapper {
        margin-right: 12px;
    }
    
    .sidebar.expanded .profile-section {
        justify-content: flex-start;
    }
    
    .sidebar.expanded .profile-avatar {
        margin-right: 12px;
    }
    
    .sidebar.expanded .profile-actions {
        position: static;
        transform: none;
        flex-direction: row;
    }
    
    .sidebar.expanded .action-btn {
        margin-left: 5px;
        margin-bottom: 0;
    }
}

/* Main content area adjustment */
.main-content {
    margin-left: var(--sidebar-width);
    padding: 20px;
    transition: all var(--transition-normal) ease;
}

@media (max-width: 992px) {
    .main-content {
        margin-left: var(--sidebar-collapsed-width);
    }
    
    .sidebar.expanded + .main-content {
        margin-left: var(--sidebar-width);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    
    // Toggle sidebar expansion on mobile
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('expanded');
            
            // Update toggle icon
            const icon = this.querySelector('i');
            if (sidebar.classList.contains('expanded')) {
                icon.classList.remove('bi-list');
                icon.classList.add('bi-x-lg');
            } else {
                icon.classList.remove('bi-x-lg');
                icon.classList.add('bi-list');
            }
        });
    }
    
    // Add hover animations
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            if (!this.classList.contains('active')) {
                this.querySelector('.icon-wrapper').style.transform = 'scale(1.1)';
            }
        });
        
        link.addEventListener('mouseleave', function() {
            if (!this.classList.contains('active')) {
                this.querySelector('.icon-wrapper').style.transform = 'scale(1)';
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            sidebar.classList.remove('expanded');
        }
    });
    
    // Status cards animation
    const statusCards = document.querySelectorAll('.status-card');
    statusCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.querySelector('.status-icon').style.transform = 'scale(1.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.querySelector('.status-icon').style.transform = 'scale(1)';
        });
    });
    
    // Profile actions
    const logoutBtn = document.querySelector('.action-btn.logout');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            // Add logout functionality here
            console.log('Logout clicked');
        });
    }
});
</script>