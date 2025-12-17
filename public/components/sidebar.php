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
        ],
        'Profile' => [
            'icon' => '👤',
            'link' => 'index.php?page=user_profile',
            'active' => 'user_profile'
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
        'Pengguna' => [
            'icon' => '👥',
            'link' => 'index.php?page=panitia_participants',
            'active' => 'panitia_participants'
        ],
        'Notifikasi' => [
            'icon' => '🔔',
            'link' => 'index.php?page=panitia_notifications',
            'active' => 'panitia_notifications'
        ],
        'Profile' => [
            'icon' => '👤',
            'link' => 'index.php?page=panitia_profile',
            'active' => 'panitia_profile'
        ]
    ];
} elseif ($role === 'admin') {
    $menu_items = [
        'Dashboard' => [
            'icon' => '⚡',
            'link' => 'index.php?page=admin_dashboard',
            'active' => 'admin_dashboard'
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

    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?>
            </div>
            <div class="user-info">
                <h4><?= htmlspecialchars($_SESSION['user']['name']) ?></h4>
                <p><?= ucfirst($_SESSION['user']['role']) ?></p>
            </div>
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
</script>