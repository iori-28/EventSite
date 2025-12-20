<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('user');

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/GoogleCalendarController.php';

$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get Stats
$stmt_registered = $db->prepare("SELECT COUNT(*) FROM participants WHERE user_id = ?");
$stmt_registered->execute([$user_id]);

$stmt_upcoming = $db->prepare("
    SELECT COUNT(*) FROM participants p 
    JOIN events e ON p.event_id = e.id 
    WHERE p.user_id = ? AND e.start_at > NOW()
");
$stmt_upcoming->execute([$user_id]);

$stmt_attended = $db->prepare("SELECT COUNT(*) FROM participants WHERE user_id = ? AND status = 'checked_in'");
$stmt_attended->execute([$user_id]);

$stats = [
    'registered' => $stmt_registered->fetchColumn(),
    'upcoming' => $stmt_upcoming->fetchColumn(),
    'attended' => $stmt_attended->fetchColumn()
];

// Get Upcoming Events (Limit 3)
$stmt_events = $db->prepare("
    SELECT e.*, p.status as payment_status 
    FROM events e 
    JOIN participants p ON e.id = p.event_id 
    WHERE p.user_id = ? AND e.start_at > NOW() 
    ORDER BY e.start_at ASC 
    LIMIT 3
");
$stmt_events->execute([$user_id]);
$upcoming_events = $stmt_events->fetchAll();

// Get Recent Notifications (Limit 5)
$stmt_notif = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY COALESCE(send_at, created_at) DESC 
    LIMIT 5
");
$stmt_notif->execute([$user_id]);
$notifications = $stmt_notif->fetchAll();

// Get Google Calendar connection status
$calendar_info = GoogleCalendarController::getConnectionInfo($user_id);

// Get user's email reminder preference
$stmt_reminder = $db->prepare("SELECT email_reminders_enabled FROM users WHERE id = ?");
$stmt_reminder->execute([$user_id]);
$reminder_enabled = $stmt_reminder->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard User - EventSite</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <?php
            $page_title = 'Dashboard';
            $breadcrumb = 'Selamat datang kembali, ' . htmlspecialchars($_SESSION['user']['name']) . '! 👋';
            include 'components/dashboard_header.php';
            ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= $stats['registered'] ?></h3>
                        <p>Total Event Diikuti</p>
                    </div>
                    <div class="stat-icon">📅</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= $stats['upcoming'] ?></h3>
                        <p>Event Akan Datang</p>
                    </div>
                    <div class="stat-icon">⏳</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= $stats['attended'] ?></h3>
                        <p>Event Dihadiri</p>
                    </div>
                    <div class="stat-icon">✅</div>
                </div>
            </div>

            <!-- Google Calendar Connection Widget -->
            <div class="card" style="margin-bottom: 30px; border-left: 4px solid <?= $calendar_info['connected'] ? '#28a745' : '#ffc107' ?>;">
                <div class="card-body" style="padding: 20px;">
                    <?php if ($calendar_info['connected']): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 8px 0; color: #28a745; font-size: 16px;">✅ Google Calendar Terhubung</h4>
                                <p style="margin: 0; font-size: 13px; color: var(--text-muted);">
                                    Terhubung sejak <?= date('d M Y', strtotime($calendar_info['connected_at'])) ?>
                                </p>
                                <div style="margin-top: 12px;">
                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                        <input type="checkbox"
                                            id="toggleAutoAdd"
                                            <?= $calendar_info['auto_add'] ? 'checked' : '' ?>
                                            onchange="toggleAutoAdd(this.checked)"
                                            style="width: 18px; height: 18px; cursor: pointer;">
                                        <span style="font-size: 14px;">Auto-add event ke kalender saat mendaftar</span>
                                    </label>
                                </div>
                            </div>
                            <button onclick="disconnectCalendar()" class="btn btn-outline btn-sm" style="border-color: #dc3545; color: #dc3545;">
                                Putuskan Koneksi
                            </button>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 8px 0; color: #ffc107; font-size: 16px;">📅 Hubungkan Google Calendar</h4>
                                <p style="margin: 0; font-size: 13px; color: var(--text-muted);">
                                    Sinkronkan event secara otomatis ke Google Calendar Anda. Event baru akan langsung masuk ke kalender tanpa perlu klik manual!
                                </p>
                            </div>
                            <a href="api/google-calendar-connect.php" class="btn btn-primary btn-sm">
                                Hubungkan Sekarang
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email Reminders Widget -->
            <div class="card" style="margin-bottom: 30px; border-left: 4px solid <?= $reminder_enabled ? '#28a745' : '#dc3545' ?>;">
                <div class="card-body" style="padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 8px 0; color: <?= $reminder_enabled ? '#28a745' : '#dc3545' ?>; font-size: 16px;">
                                <?= $reminder_enabled ? '🔔 Email Reminders Aktif' : '🔕 Email Reminders Nonaktif' ?>
                            </h4>
                            <p style="margin: 0 0 12px 0; font-size: 13px; color: var(--text-muted);">
                                <?= $reminder_enabled ? 'Anda akan menerima email reminder H-1 dan H-0 untuk event yang Anda daftar' : 'Anda tidak akan menerima email reminder otomatis' ?>
                            </p>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox"
                                    id="toggleEmailReminders"
                                    <?= $reminder_enabled ? 'checked' : '' ?>
                                    onchange="toggleEmailReminders(this.checked)"
                                    style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 14px; font-weight: 500;">Aktifkan email reminders untuk semua event</span>
                            </label>
                        </div>
                        <div style="font-size: 48px; opacity: 0.3;">
                            <?= $reminder_enabled ? '📧' : '📭' ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-2" style="gap: 30px;">
                <!-- Upcoming Events Widget -->
                <div class="card">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 18px; margin: 0;">Jadwal Event Terdekat</h3>
                        <a href="index.php?page=user_my_events" class="btn btn-outline btn-sm">Lihat Semua</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (count($upcoming_events) > 0): ?>
                            <?php foreach ($upcoming_events as $event): ?>
                                <a href="index.php?page=event-detail&id=<?= $event['id'] ?>&from=user_dashboard" style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; gap: 15px; align-items: center; text-decoration: none; color: inherit; transition: background 0.2s;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='white'">
                                    <div style="width: 50px; height: 50px; background: #eee; border-radius: 8px; display: flex; flex-direction: column; align-items: center; justify-content: center; flex-shrink: 0;">
                                        <span style="font-size: 10px; color: var(--text-muted);"><?= date('M', strtotime($event['start_at'])) ?></span>
                                        <span style="font-size: 18px; font-weight: bold;"><?= date('d', strtotime($event['start_at'])) ?></span>
                                    </div>
                                    <div style="flex: 1;">
                                        <h4 style="font-size: 16px; margin-bottom: 4px;"><?= htmlspecialchars($event['title']) ?></h4>
                                        <p style="font-size: 12px; color: var(--text-muted);">
                                            <?= date('H:i', strtotime($event['start_at'])) ?> • <?= htmlspecialchars($event['location'] ?? 'Online') ?>
                                        </p>
                                    </div>
                                    <span class="badge badge-info">Registered</span>
                                </a>
                            <?php endforeach; ?>

                            <script>
                                // Toggle auto-add preference
                                function toggleAutoAdd(enabled) {
                                    fetch('api/google-calendar-toggle-auto-add.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/x-www-form-urlencoded',
                                            },
                                            body: 'enabled=' + (enabled ? '1' : '0')
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                alert(enabled ? '✅ Auto-add diaktifkan!' : '⚠️ Auto-add dinonaktifkan');
                                            } else {
                                                alert('❌ Gagal mengubah pengaturan');
                                                // Revert checkbox
                                                document.getElementById('toggleAutoAdd').checked = !enabled;
                                            }
                                        })
                                        .catch(error => {
                                            alert('❌ Terjadi kesalahan');
                                            document.getElementById('toggleAutoAdd').checked = !enabled;
                                        });
                                }

                                // Disconnect calendar
                                function disconnectCalendar() {
                                    if (!confirm('Yakin ingin memutuskan koneksi Google Calendar?')) return;

                                    fetch('api/google-calendar-disconnect.php', {
                                            method: 'POST'
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                alert('✅ Koneksi berhasil diputus');
                                                location.reload();
                                            } else {
                                                alert('❌ Gagal memutuskan koneksi');
                                            }
                                        })
                                        .catch(error => {
                                            alert('❌ Terjadi kesalahan');
                                        });
                                }

                                // Show success message if calendar just connected
                                const urlParams = new URLSearchParams(window.location.search);
                                if (urlParams.get('calendar_connected') === '1') {
                                    alert('✅ Google Calendar berhasil terhubung!');
                                    // Clean URL
                                    window.history.replaceState({}, document.title, window.location.pathname + '?page=user_dashboard');
                                }

                                /**
                                 * Toggle email reminders preference
                                 */
                                function toggleEmailReminders(enabled) {
                                    fetch('api/toggle_email_reminders.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/x-www-form-urlencoded',
                                            },
                                            body: `enabled=${enabled ? 1 : 0}`
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                alert(enabled ? '✅ Email reminders diaktifkan' : '🔕 Email reminders dinonaktifkan');
                                                location.reload();
                                            } else {
                                                alert('❌ Gagal mengubah pengaturan: ' + (data.error || 'Unknown error'));
                                                // Revert checkbox
                                                document.getElementById('toggleEmailReminders').checked = !enabled;
                                            }
                                        })
                                        .catch(error => {
                                            alert('❌ Terjadi kesalahan');
                                            // Revert checkbox
                                            document.getElementById('toggleEmailReminders').checked = !enabled;
                                        });
                                }
                            </script>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px 20px;">
                                <p style="color: var(--text-muted);">Belum ada event yang akan datang.</p>
                                <a href="index.php?page=user_browse_events" class="btn btn-primary btn-sm mt-2">Cari Event</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications Widget -->
                <div class="card">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 18px; margin: 0;">Notifikasi Terbaru</h3>
                        <a href="index.php?page=user_notifications" class="btn btn-outline btn-sm">Semua</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (count($notifications) > 0): ?>
                            <?php foreach ($notifications as $notif): ?>
                                <div style="padding: 15px 20px; border-bottom: 1px solid #eee;">
                                    <p style="font-size: 14px; margin-bottom: 5px; color: var(--text-dark);">
                                        <?= htmlspecialchars(json_decode($notif['payload'] ?? '{}', true)['message'] ?? 'Notifikasi baru') ?>
                                    </p>
                                    <span style="font-size: 11px; color: var(--text-muted);">
                                        <?php if (!empty($notif['send_at'])): ?>
                                            <?= date('d M H:i', strtotime($notif['send_at'])) ?>
                                        <?php else: ?>
                                            <?= date('d M H:i', strtotime($notif['created_at'])) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px 20px;">
                                <p style="color: var(--text-muted);">Tidak ada notifikasi baru.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>