<?php
// Navbar component untuk public pages
$current_page = isset($_SERVER['PHP_SELF']) ? basename($_SERVER['PHP_SELF']) : 'home.php';
?>
<nav class="navbar">
    <div class="navbar-container">
        <a href="index.php?page=home" class="navbar-brand">
            <div class="navbar-logo">E</div>
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
                ?>
                <span style="font-size: 14px; margin-right: 10px; color: var(--text-light);">
                    Hi, <?= htmlspecialchars(explode(' ', $_SESSION['user']['name'] ?? 'User')[0]) ?>
                </span>
                <a href="<?= $dashboard_link ?>" class="btn btn-primary btn-sm">Dashboard</a>
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

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('navbarMenu');
        const toggle = document.querySelector('.navbar-toggle');

        if (!menu.contains(event.target) && !toggle.contains(event.target)) {
            menu.classList.remove('active');
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