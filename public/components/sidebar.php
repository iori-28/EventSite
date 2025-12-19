<?php
// Determine active menu based on current page
$current_page = $_GET['page'] ?? 'home';
$role = $_SESSION['user']['role'];

// Define menu items per role
$menu_items = [];

if ($role === 'user') {
    $menu_items = [
        'Dashboard' => [
            'icon' => '🏠',
            'link' => 'index.php?page=user_dashboard',
            'active' => 'user_dashboard'
        ],
        'Browse Event' => [
            'icon' => '🔍',
            'link' => 'index.php?page=user_browse_events',
            'active' => 'user_browse_events'
        ],
        'Event Saya' => [
            'icon' => '📅',
            'link' => 'index.php?page=user_my_events',
            'active' => 'user_my_events'
        ],
        'Sertifikat' => [
            'icon' => '🏆',
            'link' => 'index.php?page=user_certificates',
            'active' => 'user_certificates'
        ],
        'Notifikasi' => [
            'icon' => '🔔',
            'link' => 'index.php?page=user_notifications',
            'active' => 'user_notifications'
        ]
    ];
} elseif ($role === 'panitia') {
    $menu_items = [
        'Dashboard' => [
            'icon' => '📊',
            'link' => 'index.php?page=panitia_dashboard',
            'active' => 'panitia_dashboard'
        ],
        'Buat Event' => [
            'icon' => '➕',
            'link' => 'index.php?page=panitia_create_event',
            'active' => 'panitia_create_event'
        ],
        'Event Saya' => [
            'icon' => '📅',
            'link' => 'index.php?page=panitia_my_events',
            'active' => 'panitia_my_events'
        ],
        'Daftar Peserta' => [
            'icon' => '👥',
            'link' => 'index.php?page=panitia_participants',
            'active' => 'panitia_participants'
        ],
        'Notifikasi' => [
            'icon' => '🔔',
            'link' => 'index.php?page=panitia_notifications',
            'active' => 'panitia_notifications'
        ]
    ];
} elseif ($role === 'admin') {
    $menu_items = [
        'Dashboard' => [
            'icon' => '⚡',
            'link' => 'index.php?page=admin_dashboard',
            'active' => 'admin_dashboard'
        ],
        'Persetujuan Event' => [
            'icon' => '⏳',
            'link' => 'index.php?page=adm_apprv_event',
            'active' => 'adm_apprv_event'
        ],
        'Analytics' => [
            'icon' => '📊',
            'link' => 'index.php?page=admin_analytics',
            'active' => 'admin_analytics'
        ],
        'Kelola Event' => [
            'icon' => '📅',
            'link' => 'index.php?page=admin_manage_events',
            'active' => 'admin_manage_events'
        ],
        'Event Completion' => [
            'icon' => '✅',
            'link' => 'index.php?page=admin_event_completion',
            'active' => 'admin_event_completion'
        ],
        'Kelola Users' => [
            'icon' => '👥',
            'link' => 'index.php?page=admin_manage_users',
            'active' => 'admin_manage_users'
        ],
        'Notifikasi' => [
            'icon' => '🔔',
            'link' => 'index.php?page=admin_notifications',
            'active' => 'admin_notifications'
        ],
        'Laporan' => [
            'icon' => '📈',
            'link' => 'index.php?page=admin_reports',
            'active' => 'admin_reports'
        ]
    ];
}
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="index.php?page=home" style="display: flex; align-items: center; text-decoration: none; gap: 12px;">
            <div class="sidebar-logo">ES</div>
            <span class="sidebar-brand">EventSite</span>
        </a>
    </div>

    <div class="sidebar-menu">
        <div class="menu-label">Menu Utama</div>

        <?php foreach ($menu_items as $label => $item): ?>
            <a href="<?= $item['link'] ?>" class="menu-item <?= $current_page === $item['active'] ? 'active' : '' ?>">
                <i><?= $item['icon'] ?></i>
                <span><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="sidebar-footer" style="position: relative;">
        <div onclick="toggleSidebarUserMenu()" class="user-profile" style="cursor: pointer; transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.1)'" onmouseout="this.style.backgroundColor='transparent'">
            <?php if (!empty($_SESSION['user']['profile_picture'])): ?>
                <img src="<?= htmlspecialchars($_SESSION['user']['profile_picture']) ?>" alt="Profile" class="user-avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            <?php else: ?>
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div class="user-info">
                <h4><?= htmlspecialchars($_SESSION['user']['name']) ?></h4>
                <p><?= ucfirst($_SESSION['user']['role']) ?></p>
            </div>
            <div style="margin-left: auto; font-size: 12px; color: rgba(255,255,255,0.7);">▲</div>
        </div>

        <!-- Dropup Menu -->
        <div id="sidebar-user-menu" class="sidebar-dropup" style="display: none; position: absolute; bottom: 100%; left: 0; right: 0; background: #2c3e50; border-radius: 8px 8px 0 0; box-shadow: 0 -4px 12px rgba(0,0,0,0.15); z-index: 1000; margin-bottom: 5px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1);">
            <a href="index.php?page=<?= $role ?>_profile" style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: white; text-decoration: none; border-bottom: 1px solid rgba(255,255,255,0.1); transition: background 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.1)'" onmouseout="this.style.backgroundColor='transparent'">
                <span style="font-size: 18px;">👤</span>
                <span style="font-weight: 500;">Profile</span>
            </a>
            <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" style="display: flex; align-items: center; gap: 12px; padding: 14px 20px; color: #ff6b6b; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.backgroundColor='rgba(255,107,107,0.1)'" onmouseout="this.style.backgroundColor='transparent'">
                <span style="font-size: 18px;">🚪</span>
                <span style="font-weight: 500;">Logout</span>
            </a>
        </div>
    </div>
</aside>


<div class="mobile-overlay" onclick="toggleSidebar()"></div>

<style>
    /* CSS khusus untuk sidebar component mobile */
    @media (max-width: 768px) {
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99;
        }

        .mobile-overlay.active {
            display: block;
        }
    }
</style>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.querySelector('.mobile-overlay').classList.toggle('active');
    }

    function toggleSidebarUserMenu() {
        const menu = document.getElementById('sidebar-user-menu');
        if (menu.style.display === 'none' || menu.style.display === '') {
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }
    }

    // Close sidebar dropup when clicking outside
    document.addEventListener('click', function(event) {
        const sidebarFooter = document.querySelector('.sidebar-footer');
        const userMenu = document.getElementById('sidebar-user-menu');
        if (sidebarFooter && userMenu && !sidebarFooter.contains(event.target)) {
            userMenu.style.display = 'none';
        }
    });
</script>