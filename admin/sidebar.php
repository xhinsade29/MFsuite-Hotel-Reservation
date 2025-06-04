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
        </a>
        <div class="sidebar-dropdown" id="sidebarDropdown">
          <a href="#" class="admin-nav-link sidebar-dropdown-toggle <?php echo (basename($_SERVER['PHP_SELF']) == 'Amenities.php' || basename($_SERVER['PHP_SELF']) == 'services.php' || basename($_SERVER['PHP_SELF']) == 'rooms.php') ? 'active' : ''; ?>">
            <i class="bi bi-cup-hot"></i> Amenities <i class="bi bi-chevron-down ms-2 chevron-icon" style="transition: transform 0.3s;"></i>
          </a>
          <ul class="sidebar-dropdown-menu-modern bg-dark border-0 shadow" style="display:none;">
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
        <a href="reports.php" class="admin-nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
            <i class="bi bi-bar-chart-fill"></i> Reports
        </a>
    
   
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
.sidebar-dropdown-menu-modern {
  position: absolute;
  left: 0;
  top: 100%;
  min-width: 180px;
  z-index: 2000;
  background: #23234a;
  border: none;
  border-radius: 0;
  box-shadow: none;
  padding: 0;
  margin: 0;
  list-style: none;
  opacity: 0;
  transform: translateY(-10px);
  pointer-events: none;
  transition: opacity 0.25s, transform 0.25s;
}
.sidebar-dropdown.open .sidebar-dropdown-menu-modern {
  display: block !important;
  opacity: 1;
  transform: translateY(0);
  pointer-events: auto;
}
.sidebar-dropdown-menu-modern .dropdown-item {
  color: #fff;
  padding: 8px 18px;
  border: none;
  background: none;
  font-weight: 400;
  font-size: 1em;
  border-radius: 0;
  margin: 0;
  transition: background 0.18s, color 0.18s;
  box-shadow: none;
  position: relative;
}
.sidebar-dropdown-menu-modern .dropdown-item.active, .sidebar-dropdown-menu-modern .dropdown-item:hover {
  background: #ffa533;
  color: #23234a;
  box-shadow: none;
}
.sidebar-dropdown-menu-modern .dropdown-item i {
  margin-right: 8px;
  color: #ffa533;
  font-size: 1em;
}
.chevron-icon {
  display: inline-block;
  vertical-align: middle;
  transition: transform 0.3s;
}
.sidebar-dropdown.open .chevron-icon {
  transform: rotate(180deg);
}
</style>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var dropdown = document.getElementById('sidebarDropdown');
  var dropdownToggle = dropdown.querySelector('.sidebar-dropdown-toggle');
  var dropdownMenu = dropdown.querySelector('.sidebar-dropdown-menu-modern');
  var chevron = dropdown.querySelector('.chevron-icon');
  dropdownToggle.addEventListener('click', function(e) {
    e.preventDefault();
    var isOpen = dropdown.classList.contains('open');
    if (isOpen) {
      dropdown.classList.remove('open');
    } else {
      dropdown.classList.add('open');
    }
  });
  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    if (!dropdown.contains(e.target)) {
      dropdown.classList.remove('open');
    }
  });
});
</script>
<!-- Admin Top Navigation Bar (User Style) -->
<nav class="navbar admin-navbar" style="background:#23234a; color:#fff; padding: 0.7rem 2rem; display:flex; align-items:center; justify-content:space-between; position:fixed; top:0; left:240px; right:0; z-index:1100; box-shadow:0 2px 12px rgba(0,0,0,0.10);">
    <!-- Removed logo to avoid duplication -->
    <div></div>
    <div class="d-flex align-items-center gap-4 nav-right">
        <a href="notifications.php" class="notifications position-relative" id="adminNotifBell" style="color:#fff; font-size:1.6em;">
            <i class="bi bi-bell"></i>
        </a>
        <div style="position:relative;">
            <button class="profile-trigger" id="adminProfileDropdownBtn" style="background:none;border:none;display:flex;align-items:center;gap:8px;color:#fff;">
                <?php
                $admin_full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
                $admin_avatar = '';
                if (!empty($_SESSION['profile_picture'])) {
                    $admin_avatar = '../uploads/profile_pictures/' . $_SESSION['profile_picture'];
                } else {
                    $admin_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($admin_full_name) . '&background=FF8C00&color=fff';
                }
                ?>
                <img src="<?php echo $admin_avatar; ?>" alt="Admin User" style="width:36px;height:36px;border-radius:50%;border:2px solid #FF8C00;object-fit:cover;">
                <span class="fw-semibold d-none d-md-inline"> <?php echo htmlspecialchars($admin_full_name); ?> </span>
                <i class="bi bi-chevron-down d-none d-md-inline"></i>
            </button>
            <div class="profile-dropdown" id="adminProfileDropdown" style="display:none;position:absolute;right:0;top:48px;min-width:160px;background:#23234a;color:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(31,38,135,0.18);z-index:3000;padding:0.5rem 0;border:1px solid rgba(255,255,255,0.08);">
                <a href="logout.php" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a>
            </div>
        </div>
    </div>
</nav>
<script>
document.getElementById('adminNotifBell').addEventListener('click', function(e) {
    e.preventDefault(); // Prevent immediate navigation
    var badge = document.getElementById('adminNotifBadge');
    // Mark all as read in backend
    fetch('../pages/mark_notifications_read.php?admin=1')
        .then(function(response) {
            if (badge) badge.remove(); // Remove badge visually
            // Now redirect to notifications page
    window.location.href = 'notifications.php';
        });
});
const adminProfileBtn = document.getElementById('adminProfileDropdownBtn');
const adminProfileDropdown = document.getElementById('adminProfileDropdown');
if (adminProfileBtn && adminProfileDropdown) {
    adminProfileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        adminProfileDropdown.style.display = (adminProfileDropdown.style.display === 'block') ? 'none' : 'block';
    });
    document.addEventListener('click', function(e) {
        if (adminProfileDropdown.style.display === 'block') {
            adminProfileDropdown.style.display = 'none';
        }
    });
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var notifBell = document.querySelector('.admin-nav-link[href="notifications.php"]');
    if (notifBell) {
        notifBell.addEventListener('click', function(e) {
            // Remove badge visually
            var badge = notifBell.querySelector('.badge');
            if (badge) badge.style.display = 'none';
            // Mark all as read in backend
            fetch('notifications.php?readall=1')
                .then(function() {
                    // Optionally reload or update badge
                });
        });
    }
});
</script>
