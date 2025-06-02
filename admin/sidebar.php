<!-- Enhanced Admin Sidebar - User Style Consistency -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<aside class="admin-sidebar">
    <div class="admin-logo">
        <img src="../assets/MFsuites_logo.png" alt="MF Suites Logo">
        <span class="admin-hotel-name">MF Suites Hotel</span>
    </div>
    <nav class="admin-nav">
        <a href="profile.php" class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
            <i class="bi bi-person"></i> Profile
        </a>
        <a href="dashboard.php" class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        <a href="notifications.php" class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'notifications.php') ? 'active' : ''; ?>">
            <i class="bi bi-bell-fill"></i> Notifications
            <?php
            // Fetch unread notifications count
            $notif_count = 0;
            include_once '../functions/db_connect.php';
            $res = mysqli_query($mycon, "SELECT COUNT(*) as cnt FROM notifications WHERE is_read = 0");
            if ($res && $row = mysqli_fetch_assoc($res)) {
                $notif_count = (int)$row['cnt'];
            }
         
            ?>
            <?php if ($notif_count > 0): ?>
                <span class="badge bg-danger ms-2"><?php echo $notif_count; ?></span>
            <?php endif; ?>
        </a>
        <div class="sidebar-dropdown" id="sidebarDropdown">
          <a href="#" class="admin-nav-link sidebar-dropdown-toggle <?php echo (basename($_SERVER['PHP_SELF']) == 'Amenities.php' || basename($_SERVER['PHP_SELF']) == 'services.php' || basename($_SERVER['PHP_SELF']) == 'rooms.php') ? 'active' : ''; ?>">
            <i class="bi bi-cup-hot"></i> Amenities <i class="bi bi-chevron-down ms-2"></i>
          </a>
          <ul class="sidebar-dropdown-menu bg-dark border-0 shadow" style="display:none;">
            <li>
              <a href="services.php" class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'active' : ''; ?>">
                <i class="bi bi-gear"></i> Services
              </a>
            </li>
            <li>
              <a href="rooms.php" class="dropdown-item <?php echo (basename($_SERVER['PHP_SELF']) == 'rooms.php') ? 'active' : ''; ?>">
                <i class="bi bi-door-closed"></i> Rooms
              </a>
            </li>
          </ul>
        </div>
        <a href="reservations.php" class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reservations.php') ? 'active' : ''; ?>">
            <i class="bi bi-calendar2-check-fill"></i> Reservations
        </a>
        <a href="guests.php" class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'guests.php') ? 'active' : ''; ?>">
            <i class="bi bi-people-fill"></i> Guests
        </a>
        <a href="payments.php" class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'payments.php') ? 'active' : ''; ?>">
            <i class="bi bi-credit-card-fill"></i> Payments
        </a>
    </nav>
    <div class="admin-profile">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username'] ?? 'Admin User'); ?>&background=FF8C00&color=fff" alt="Admin User" class="admin-avatar">
        <div class="admin-profile-info">
            <span class="admin-profile-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin User'); ?></span>
            <span class="admin-profile-role"><?php echo htmlspecialchars(ucfirst($_SESSION['role'] ?? 'Administrator')); ?></span>
        </div>
        <a href="logout.php" class="admin-logout-btn" title="Log Out"><i class="bi bi-box-arrow-right"></i></a>
    </div>
</aside>
<style>
:root {
    --primary: #FF8C00;
    --secondary: #11101d;
    --text-light: #ffffff;
    --text-dim: rgba(255, 255, 255, 0.7);
    --sidebar-width: 240px;
    --transition: all 0.3s ease;
}
.admin-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--secondary);
    color: var(--text-light);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 32px;
    z-index: 1000;
    box-shadow: 2px 0 12px rgba(0,0,0,0.12);
}
.admin-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 32px;
}
.admin-logo img {
    width: 60px;
    height: 60px;
    margin-bottom: 8px;
    border-radius: 12px;
    box-shadow: 0 2px 8px #ff8c0033;
}
.admin-hotel-name {
    font-size: 1.1em;
    font-weight: 600;
    color: var(--primary);
    letter-spacing: 1px;
}
.admin-nav {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: auto;
}
.admin-nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 28px;
    color: var(--text-dim);
    font-size: 1em;
    font-weight: 500;
    text-decoration: none;
    border-left: 4px solid transparent;
    transition: var(--transition);
}
.admin-nav-link i {
    font-size: 1.2em;
}
.admin-nav-link.active, .admin-nav-link:hover {
    background: rgba(255, 140, 0, 0.08);
    color: var(--primary);
    border-left: 4px solid var(--primary);
}
.admin-profile {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 18px 28px;
    border-top: 1px solid rgba(255,255,255,0.08);
    margin-top: 32px;
    background: rgba(255,255,255,0.02);
}
.admin-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid var(--primary);
}
.admin-profile-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.admin-profile-name {
    font-size: 1em;
    font-weight: 600;
    color: var(--text-light);
}
.admin-profile-role {
    font-size: 0.85em;
    color: var(--primary);
}
.admin-logout-btn {
    color: #ff4444;
    font-size: 1.3em;
    margin-left: auto;
    background: none;
    border: none;
    outline: none;
    transition: color 0.2s;
}
.admin-logout-btn:hover {
    color: #fff;
    background: #ff4444;
    border-radius: 8px;
    padding: 4px 8px;
}
@media (max-width: 900px) {
    .admin-sidebar {
        width: 70px;
        padding-top: 18px;
    }
    .admin-logo img, .admin-hotel-name, .admin-profile-info, .admin-profile-role, .admin-profile-name {
        display: none;
    }
    .admin-nav-link {
        justify-content: center;
        padding: 12px 0;
        font-size: 1.2em;
    }
    .admin-profile {
        justify-content: center;
        padding: 12px 0;
    }
    .admin-avatar {
        margin: 0;
    }
}
.sidebar-dropdown { position: relative; }
.sidebar-dropdown-menu {
  position: absolute;
  left: 100%;
  top: 0;
  min-width: 180px;
  z-index: 2000;
  background: #23234a;
  border-radius: 12px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.18);
  padding: 0;
  margin: 0;
  list-style: none;
}
.sidebar-dropdown-menu .dropdown-item {
  color: #fff;
  padding: 12px 24px;
  border-bottom: 1px solid rgba(255,255,255,0.05);
  background: none;
  transition: background 0.2s;
}
.sidebar-dropdown-menu .dropdown-item.active, .sidebar-dropdown-menu .dropdown-item:hover {
  background: rgba(255,140,0,0.08);
  color: #ffa533;
}
.sidebar-dropdown-menu .dropdown-item:last-child { border-bottom: none; }
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var dropdownToggle = document.querySelector('.sidebar-dropdown-toggle');
  var dropdownMenu = document.querySelector('.sidebar-dropdown-menu');
  dropdownToggle.addEventListener('click', function(e) {
    e.preventDefault();
    var isOpen = dropdownMenu.style.display === 'block';
    dropdownMenu.style.display = isOpen ? 'none' : 'block';
  });
  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
      dropdownMenu.style.display = 'none';
    }
  });
});
</script>
