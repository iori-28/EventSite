<?php
// Navbar component untuk public pages
$current_page = isset($_SERVER['PHP_SELF']) ? basename($_SERVER['PHP_SELF']) : 'home.php';
?>
<nav class="navbar">
    <div class="navbar-container">
        <a href="index.php?page=home" class="navbar-brand">
            <div class="navbar-logo">ES</div>
            <span>EventSite</span>
        </a>

        <ul class="navbar-menu" id="navbarMenu">
            <li><a href="index.php?page=home" class="<?= $current_page == 'home.php' || $current_page == 'home' ? 'active' : '' ?>">Home</a></li>
            <li><a href="index.php?page=events" class="<?= $current_page == 'events.php' || $current_page == 'events' ? 'active' : '' ?>">Events</a></li>
            <li><a href="index.php?page=home#about">Tentang</a></li>
            <li><a href="index.php?page=home#contact">Kontak</a></li>
        </ul>

        <div class="navbar-actions">
            <?php if (isset($_SESSION['user'])): ?>
                <?php
                $dashboard_link = 'index.php?page=user_dashboard';
                if ($_SESSION['user']['role'] === 'admin') $dashboard_link = 'index.php?page=admin_dashboard';
                if ($_SESSION['user']['role'] === 'panitia') $dashboard_link = 'index.php?page=panitia_dashboard';
                $user_initial = strtoupper(substr($_SESSION['user']['name'] ?? 'U', 0, 1));
                $user_firstname = htmlspecialchars(explode(' ', $_SESSION['user']['name'] ?? 'User')[0]);
                ?>
                <div class="profile-dropdown-wrapper" style="position: relative;" onmouseenter="showProfileDropdown()" onmouseleave="hideProfileDropdown()">
                    <button onclick="toggleProfileDropdown()" class="profile-btn" style="display: flex; align-items: center; gap: 8px; background: white; border: 2px solid var(--border-color); padding: 6px 12px; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                        <?php if (!empty($_SESSION['user']['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($_SESSION['user']['profile_picture']) ?>" alt="Profile" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 32px; height: 32px; background: var(--primary-gradient); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">
                                <?= $user_initial ?>
                            </div>
                        <?php endif; ?>
                        <span style="font-size: 14px; color: var(--text-dark); font-weight: 500;"><?= $user_firstname ?></span>
                        <span style="font-size: 10px; color: var(--text-muted);">▼</span>
                    </button>

                    <div id="profileDropdown" class="profile-dropdown" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 2px; background: white; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,0.15); min-width: 200px; z-index: 1000; overflow: hidden; border: 1px solid var(--border-color); opacity: 0; transform: translateY(-10px); transition: opacity 0.2s, transform 0.2s; padding-top: 6px;">
                        <div style="padding: 16px; padding-top: 10px; border-bottom: 1px solid var(--border-color); background: #f8f9fa;">
                            <div style="font-weight: 600; color: var(--text-dark); margin-bottom: 4px;"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
                            <div style="font-size: 12px; color: var(--text-muted); text-transform: capitalize;"><?= htmlspecialchars($_SESSION['user']['role']) ?></div>
                        </div>
                        <?php
                        $profile_link = 'index.php?page=user_profile';
                        if ($_SESSION['user']['role'] === 'admin') $profile_link = 'index.php?page=admin_profile';
                        if ($_SESSION['user']['role'] === 'panitia') $profile_link = 'index.php?page=panitia_profile';
                        ?>
                        <a href="<?= $profile_link ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; text-decoration: none; color: var(--text-dark); transition: background 0.2s; border-bottom: 1px solid #f0f0f0;">
                            <span style="font-size: 18px;">👤</span>
                            <span style="font-weight: 500;">Profile</span>
                        </a>
                        <a href="<?= $dashboard_link ?>" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; text-decoration: none; color: var(--text-dark); transition: background 0.2s; border-bottom: 1px solid #f0f0f0;">
                            <span style="font-size: 18px;">📊</span>
                            <span style="font-weight: 500;">Dashboard</span>
                        </a>
                        <a href="logout.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; text-decoration: none; color: #dc3545; transition: background 0.2s;">
                            <span style="font-size: 18px;">🚪</span>
                            <span style="font-weight: 500;">Logout</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="index.php?page=login" class="btn btn-outline btn-sm">Masuk</a>
                <a href="index.php?page=register" class="btn btn-primary btn-sm">Daftar</a>
            <?php endif; ?>
        </div>

        <button class="navbar-toggle" onclick="toggleMobileMenu()">
            ☰
        </button>
    </div>
</nav>

<script>
    function toggleMobileMenu() {
        const menu = document.getElementById('navbarMenu');
        menu.classList.toggle('active');
    }

    function showProfileDropdown() {
        const dropdown = document.getElementById('profileDropdown');
        if (dropdown) {
            dropdown.style.display = 'block';
            setTimeout(() => {
                dropdown.style.opacity = '1';
                dropdown.style.transform = 'translateY(0)';
            }, 10);
        }
    }

    function hideProfileDropdown() {
        const dropdown = document.getElementById('profileDropdown');
        if (dropdown) {
            dropdown.style.opacity = '0';
            dropdown.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                dropdown.style.display = 'none';
            }, 200);
        }
    }

    function toggleProfileDropdown() {
        const dropdown = document.getElementById('profileDropdown');
        if (dropdown.style.display === 'none' || dropdown.style.opacity === '0') {
            showProfileDropdown();
        } else {
            hideProfileDropdown();
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('navbarMenu');
        const toggle = document.querySelector('.navbar-toggle');
        const profileDropdown = document.getElementById('profileDropdown');
        const profileWrapper = document.querySelector('.profile-dropdown-wrapper');

        // Close mobile menu
        if (menu && toggle && !menu.contains(event.target) && !toggle.contains(event.target)) {
            menu.classList.remove('active');
        }

        // Close profile dropdown
        if (profileDropdown && profileWrapper && !profileWrapper.contains(event.target)) {
            hideProfileDropdown();
        }
    });

    // Smooth scroll to anchor on same page
    document.addEventListener('DOMContentLoaded', function() {
        // Check if there's a hash in URL
        if (window.location.hash) {
            setTimeout(function() {
                const element = document.querySelector(window.location.hash);
                if (element) {
                    element.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }, 100);
        }

        // Handle anchor links
        document.querySelectorAll('a[href*="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                const hashIndex = href.indexOf('#');

                if (hashIndex !== -1) {
                    const hash = href.substring(hashIndex);
                    const currentPage = href.substring(0, hashIndex);
                    const thisPage = window.location.href.split('#')[0];

                    // If linking to same page, smooth scroll
                    if (!currentPage || thisPage.includes(currentPage)) {
                        e.preventDefault();
                        const element = document.querySelector(hash);
                        if (element) {
                            element.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                            history.pushState(null, null, hash);
                        }
                    }
                }
            });
        });
    });
</script>
