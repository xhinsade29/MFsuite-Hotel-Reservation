<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$theme_preference = $_SESSION['theme_preference'] ?? 'dark';
include_once '../functions/db_connect.php';
$current_page = basename($_SERVER['PHP_SELF']);
$unread_count = 0;
if (isset($_SESSION['guest_id'])) {
    $guest_id = $_SESSION['guest_id'];
    $sql = "SELECT COUNT(*) as cnt FROM user_notifications WHERE guest_id = ? AND is_read = 0";
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param("i", $guest_id);
    $stmt->execute();
    $stmt->bind_result($unread_count);
    $stmt->fetch();
    $stmt->close();
}
// Fetch guest name for profile display
$display_name = 'Guest User';
$avatar_name = 'Guest User';
if (isset($_SESSION['guest_id'])) {
    $guest_id = $_SESSION['guest_id'];
    $sql = "SELECT first_name, last_name FROM tbl_guest WHERE guest_id = ? LIMIT 1";
    $stmt = $mycon->prepare($sql);
    $stmt->bind_param("i", $guest_id);
    $stmt->execute();
    $stmt->bind_result($first_name, $last_name);
    if ($stmt->fetch()) {
        $display_name = trim($first_name . ' ' . $last_name);
        $avatar_name = urlencode(trim($first_name . ' ' . $last_name));
    }
    $stmt->close();
}
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
    <style>
        /* Enhanced Notification Bell */
        .notifications {
            position: relative;
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.5em;
            cursor: pointer;
            padding: 5px;
            transition: color 0.2s;
        }
        .notifications .bi-bell {
            transition: color 0.2s, transform 0.2s;
        }
        .notifications.has-unread .bi-bell {
            color: #ffa533;
            animation: bell-shake 0.8s cubic-bezier(.36,.07,.19,.97) both infinite;
        }
        @keyframes bell-shake {
            0%, 100% { transform: rotate(0); }
            10%, 30%, 50%, 70% { transform: rotate(-15deg); }
            20%, 40%, 60%, 80% { transform: rotate(15deg); }
            90% { transform: rotate(-8deg); }
        }
        .notifications .badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: linear-gradient(90deg, #ff8c00 60%, #ffa533 100%);
            color: #fff;
            font-size: 0.78em;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(255,140,0,0.15);
            border: 2px solid #23234a;
        }
        /* Notification Dropdown */
        .notification-dropdown {
            display: none;
            position: absolute;
            top: 38px;
            right: 0;
            min-width: 320px;
            background: #23234a;
            color: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(31,38,135,0.18);
            z-index: 2001;
            padding: 0.5rem 0;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .notification-dropdown.active {
            display: block;
            animation: fadeIn 0.2s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            transition: background 0.15s;
        }
        .notification-item:last-child { border-bottom: none; }
        .notification-item:hover { background: rgba(255,140,0,0.07); }
        .notification-icon {
            font-size: 1.3em;
            color: #ffa533;
            margin-top: 2px;
        }
        .notification-content {
            flex: 1;
        }
        .notification-title {
            font-weight: 600;
            color: #ffa533;
            font-size: 1em;
            margin-bottom: 2px;
        }
        .notification-time {
            color: #bdbdbd;
            font-size: 0.88em;
        }
        .notification-empty {
            text-align: center;
            color: #bdbdbd;
            padding: 18px 0 10px 0;
            font-size: 1em;
        }
        @media (max-width: 600px) {
            .notification-dropdown { min-width: 90vw; right: -30vw; }
        }
        body.light-mode, .light-mode {
            background: #f8f9fa !important;
            color: #23234a !important;
        }
        body.light-mode .navbar, .light-mode .navbar {
            background: #fff !important;
            color: #23234a !important;
        }
        body.light-mode .sidebar, .light-mode .sidebar {
            background: #f1f1f1 !important;
            color: #23234a !important;
        }
        body.light-mode .sidebar a, .light-mode .sidebar a {
            color: #23234a !important;
        }
        body.light-mode .sidebar a.active, .light-mode .sidebar a.active {
            background: #ffe5b4 !important;
            color: #ff8c00 !important;
        }
        body.light-mode .notifications, .light-mode .notifications {
            color: #23234a !important;
        }
        body.light-mode .notifications .bi-bell, .light-mode .notifications .bi-bell {
            color: #ff8c00 !important;
        }
        body.light-mode .notification-dropdown, .light-mode .notification-dropdown {
            background: #fff !important;
            color: #23234a !important;
            border: 1px solid #ffe5b4;
        }
        body.light-mode .notification-item, .light-mode .notification-item {
            border-bottom: 1px solid #ffe5b4;
        }
        body.light-mode .notification-title, .light-mode .notification-title {
            color: #ff8c00 !important;
        }
        body.light-mode .notification-empty, .light-mode .notification-empty {
            color: #bdbdbd !important;
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 18px;
            position: relative; /* Ensure dropdown is positioned relative to this */
        }
        .profile-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 60px;
            min-width: 180px;
            background: #23234a;
            color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(31,38,135,0.18);
            z-index: 3000;
            padding: 0.5rem 0;
            border: 1px solid rgba(255,255,255,0.08);
        }
        .profile-dropdown .dropdown-item {
            display: block;
            padding: 12px 22px;
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.15s;
        }
        .profile-dropdown .dropdown-item:hover {
            background: #ff8c00;
            color: #fff;
        }
        .search-results-dropdown {
            border: 1px solid #ffe5b4;
            border-top: none;
            padding: 0;
        }
        .search-results-dropdown .search-result-item {
            padding: 12px 18px;
            cursor: pointer;
            border-bottom: 1px solid #ffe5b4;
            background: #fff;
            color: #23234a;
            font-weight: 500;
            transition: background 0.13s;
        }
        .search-results-dropdown .search-result-item:last-child {
            border-bottom: none;
        }
        .search-results-dropdown .search-result-item:hover {
            background: #ffe5b4;
            color: #ff8c00;
        }
        body.light-mode .search-results-dropdown {
            background: #fff !important;
            color: #23234a !important;
        }
    </style>
</head>
<body class="<?php echo ($theme_preference === 'light') ? 'light-mode' : ''; ?>">

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
            <div class="search-box" style="position:relative;">
                <input type="search" placeholder="Search rooms..." id="roomSearchInput" autocomplete="off">
                <i class="bi bi-search"></i>
                <div id="roomSearchResults" class="search-results-dropdown" style="display:none;position:absolute;top:110%;left:0;width:100%;background:#fff;color:#23234a;z-index:3001;border-radius:0 0 12px 12px;box-shadow:0 8px 32px rgba(31,38,135,0.10);max-height:320px;overflow-y:auto;"></div>
            </div>
            <a href="#" class="notifications <?php echo ($unread_count > 0) ? 'has-unread' : ''; ?>" id="notifBell" style="position:relative;">
                <i class="bi bi-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="badge" id="notifBadge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <div style="position:relative;">
            <button class="profile-trigger" id="profileDropdownBtn">
                <img src="https://ui-avatars.com/api/?name=<?php echo $avatar_name; ?>&background=FF8C00&color=fff" 
                     alt="User" class="avatar">
                <span class="username"><?php echo htmlspecialchars($display_name); ?></span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div class="profile-dropdown" id="profileDropdown">
                <a href="../pages/logout.php" class="dropdown-item">
                    <i class="bi bi-box-arrow-right me-2"></i> Log Out
                </a>
            </div>
            </div>
        </div>
    </div>
</nav>

<!-- SIDEBAR -->
<aside class="sidebar">
    <a href="../pages/update_profile.php" class="<?php echo ($current_page == 'update_profile.php') ? 'active' : ''; ?>">
        <i class="bi bi-person"></i> Profile
    </a>
    <a href="../pages/rooms.php" class="<?php echo ($current_page == 'rooms.php') ? 'active' : ''; ?>">
        <i class="bi bi-house"></i> Rooms
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
    
</aside>

<script>
function toggleNotifDropdown(e) {
    e.stopPropagation();
    var dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('active');
}
document.addEventListener('click', function(e) {
    var dropdown = document.getElementById('notifDropdown');
    if (dropdown && dropdown.classList.contains('active')) {
        dropdown.classList.remove('active');
    }
});

document.getElementById('notifBell').addEventListener('click', function(e) {
    e.preventDefault();
    fetch('../pages/mark_notifications_read.php')
        .then(response => response.text())
        .then(data => {
            if (data.trim() === 'success') {
                var badge = document.getElementById('notifBadge');
                if (badge) badge.remove();
                window.location.href = '../pages/notifications.php';
            } else {
                window.location.href = '../pages/notifications.php';
            }
        })
        .catch(() => {
            window.location.href = '../pages/notifications.php';
        });
});

// Profile dropdown logic
const profileBtn = document.getElementById('profileDropdownBtn');
const profileDropdown = document.getElementById('profileDropdown');
if (profileBtn && profileDropdown) {
    profileBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        profileDropdown.style.display = (profileDropdown.style.display === 'block') ? 'none' : 'block';
    });
    document.addEventListener('click', function(e) {
        if (profileDropdown.style.display === 'block') {
            profileDropdown.style.display = 'none';
        }
    });
}

// Room type search logic
const roomSearchInput = document.getElementById('roomSearchInput');
const roomSearchResults = document.getElementById('roomSearchResults');
if (roomSearchInput && roomSearchResults) {
    let searchTimeout = null;
    roomSearchInput.addEventListener('input', function() {
        const query = this.value.trim();
        if (searchTimeout) clearTimeout(searchTimeout);
        if (query.length < 2) {
            roomSearchResults.style.display = 'none';
            roomSearchResults.innerHTML = '';
            return;
        }
        searchTimeout = setTimeout(function() {
            fetch('../pages/search_room_types.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (Array.isArray(data) && data.length > 0) {
                        roomSearchResults.innerHTML = data.map(room =>
                            `<div class='search-result-item' data-id='${room.room_type_id}'>
                                <strong>${room.type_name}</strong><br>
                                <span style='font-size:0.95em;color:#888;'>${room.description}</span>
                            </div>`
                        ).join('');
                        roomSearchResults.style.display = 'block';
                    } else {
                        roomSearchResults.innerHTML = `<div class='search-result-item' style='color:#888;'>No results found.</div>`;
                        roomSearchResults.style.display = 'block';
                    }
                });
        }, 250);
    });
    roomSearchResults.addEventListener('click', function(e) {
        const item = e.target.closest('.search-result-item');
        if (item && item.dataset.id) {
            window.location.href = '../pages/booking_form.php?room_type_id=' + encodeURIComponent(item.dataset.id);
        }
    });
    document.addEventListener('click', function(e) {
        if (!roomSearchResults.contains(e.target) && e.target !== roomSearchInput) {
            roomSearchResults.style.display = 'none';
        }
    });
}
</script>

</body>
</html>
