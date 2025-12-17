<?php
// Ensure session and role checks are done in the parent page before including this
if (!isset($_SESSION['user'])) {
    return;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';

// Fetch user data
$user_id = $_SESSION['user']['id'];
$unread_count = NotificationController::getUnreadCount($user_id);
$latest_notifs = NotificationController::getLatest($user_id, 5);
?>
<header class="dashboard-header">
    <div class="header-title">
        <button class="sidebar-toggle" onclick="toggleSidebar()" style="display:none; background:none; border:none; font-size:24px; cursor:pointer; margin-right:10px;">☰</button>
        <h1><?= $page_title ?? 'Dashboard' ?></h1>
        <?php if (isset($breadcrumb)): ?>
            <div class="header-breadcrumb"><?= $breadcrumb ?></div>
        <?php endif; ?>
    </div>

    <div class="header-actions" style="display: flex; align-items: center; gap: 20px;">
        <!-- Notification Dropdown -->
        <div class="notification-dropdown" style="position: relative;">
            <button onclick="toggleNotifications()" class="btn-icon" style="background: none; border: none; font-size: 20px; cursor: pointer; position: relative;">
                🔔
                <?php if ($unread_count > 0): ?>
                    <span class="badge-count" style="position: absolute; top: -5px; right: -5px; background: red; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; display: <?= $unread_count > 0 ? 'block' : 'none' ?>;"><?= $unread_count ?></span>
                <?php endif; ?>
            </button>

            <div id="notif-menu" class="dropdown-menu" style="display: none; position: absolute; right: 0; top: 100%; width: 300px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000;">
                <div style="padding: 10px 15px; border-bottom: 1px solid #eee; font-weight: bold; display: flex; justify-content: space-between;">
                    <span>Notifikasi</span>
                    <?php
                    $notif_page = $_SESSION['user']['role'] . '_notifications';
                    if ($_SESSION['user']['role'] === 'admin') {
                        $notif_page = 'admin_notifications';
                    }
                    ?>
                    <a href="index.php?page=<?= $notif_page ?>" style="font-size: 12px; color: var(--primary-color); text-decoration: none;">Lihat Semua</a>
                </div>
                <div class="notif-items" style="max-height: 300px; overflow-y: auto;">
                    <?php if (count($latest_notifs) > 0): ?>
                        <?php foreach ($latest_notifs as $n):
                            // Parse payload JSON if exists
                            $payload = json_decode($n['payload'] ?? '{}', true);
                            $eventTitle = $payload['event_title'] ?? $payload['title'] ?? '';

                            // Generate user-friendly message based on type
                            $subject = '';
                            $message = '';

                            switch ($n['type']) {
                                case 'event_approved':
                                    $subject = '✅ Event Disetujui';
                                    $message = $eventTitle ? "Event '{$eventTitle}' telah disetujui admin" : 'Event Anda telah disetujui admin';
                                    break;
                                case 'event_rejected':
                                    $subject = '❌ Event Ditolak';
                                    $message = $eventTitle ? "Event '{$eventTitle}' ditolak admin" : 'Event Anda ditolak admin';
                                    break;
                                case 'registration_success':
                                    $subject = '🎉 Pendaftaran Berhasil';
                                    $message = $eventTitle ? "Berhasil daftar '{$eventTitle}'" : 'Berhasil daftar event';
                                    break;
                                case 'event_reminder':
                                    $subject = '⏰ Pengingat Event';
                                    $message = $eventTitle ? "Event '{$eventTitle}' segera dimulai!" : 'Event Anda segera dimulai';
                                    break;
                                case 'certificate_issued':
                                    $subject = '🎓 Sertifikat Tersedia';
                                    $message = $eventTitle ? "Sertifikat '{$eventTitle}' telah tersedia" : 'Sertifikat event telah tersedia';
                                    break;
                                default:
                                    $subject = ucfirst(str_replace('_', ' ', $n['type']));
                                    $message = $eventTitle ?: 'Notifikasi baru';
                            }

                            // Format date safely (handle null)
                            $dateFormatted = 'Baru saja';
                            if (!empty($n['send_at'])) {
                                $timestamp = strtotime($n['send_at']);
                                if ($timestamp !== false) {
                                    $dateFormatted = date('d M H:i', $timestamp);
                                }
                            }
                        ?>
                            <div style="padding: 10px 15px; border-bottom: 1px solid #eee; background: <?= $n['status'] === 'pending' ? '#f8f9fa' : 'white' ?>;">
                                <div style="font-size: 13px; font-weight: 500; margin-bottom: 2px;"><?= htmlspecialchars($subject) ?></div>
                                <div style="font-size: 11px; color: #666;"><?= htmlspecialchars($message) ?></div>
                                <div style="font-size: 10px; color: #999; margin-top: 2px;"><?= $dateFormatted ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 20px; text-align: center; color: #999; font-size: 12px;">Tidak ada notifikasi</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- User Dropdown -->
        <div class="user-dropdown" style="position: relative;">
            <div onclick="toggleUserMenu()" class="user-info-header" style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 5px; border-radius: 8px; transition: background 0.2s;">
                <div class="avatar" style="width: 32px; height: 32px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    <?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?>
                </div>
                <span style="font-size: 14px; display: none; @media (min-width: 768px) { display: block; }"><?= htmlspecialchars($_SESSION['user']['name']) ?> ▼</span>
            </div>

            <div id="user-menu" class="dropdown-menu" style="display: none; position: absolute; right: 0; top: 100%; width: 200px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; overflow: hidden;">
                <a href="index.php?page=<?= $_SESSION['user']['role'] ?>_profile" style="display: block; padding: 12px 20px; color: var(--text-dark); text-decoration: none; border-bottom: 1px solid #eee;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    👤 Profile
                </a>
                <a href="index.php?page=logout" onclick="return confirm('Apakah Anda yakin ingin keluar?')" style="display: block; padding: 12px 20px; color: #dc3545; text-decoration: none; font-weight: 500;" onmouseover="this.style.background='#ffe6e6'" onmouseout="this.style.background='white'">
                    🚪 Logout
                </a>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleNotifications() {
        const menu = document.getElementById('notif-menu');
        const userMenu = document.getElementById('user-menu');
        // Close other
        if (userMenu) userMenu.style.display = 'none';

        if (menu.style.display === 'none') {
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }
    }

    function toggleUserMenu() {
        const menu = document.getElementById('user-menu');
        const notifMenu = document.getElementById('notif-menu');
        // Close other
        if (notifMenu) notifMenu.style.display = 'none';

        if (menu.style.display === 'none') {
            menu.style.display = 'block';
        } else {
            menu.style.display = 'none';
        }
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        const notifDropdown = document.querySelector('.notification-dropdown');
        const notifMenu = document.getElementById('notif-menu');
        if (notifDropdown && !notifDropdown.contains(event.target)) {
            notifMenu.style.display = 'none';
        }

        const userDropdown = document.querySelector('.user-dropdown');
        const userMenu = document.getElementById('user-menu');
        if (userDropdown && !userDropdown.contains(event.target)) {
            userMenu.style.display = 'none';
        }
    });
</script>