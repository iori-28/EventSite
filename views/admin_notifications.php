<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get notifications
$stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY COALESCE(send_at, created_at) DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Mark as read (update status to 'sent' when viewing)
$db->prepare("UPDATE notifications SET status = 'sent' WHERE user_id = ? AND status = 'pending'")->execute([$user_id]);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi Admin - EventSite</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <?php
            $page_title = 'Notifikasi';
            $breadcrumb = 'Pemberitahuan sistem';
            include 'components/dashboard_header.php';
            ?>

            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <?php if (count($notifications) > 0): ?>
                        <ul class="notification-list" style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($notifications as $notif): ?>
                                <?php
                                // Parse payload JSON
                                $payload = json_decode($notif['payload'] ?? '{}', true);
                                $eventTitle = $payload['event_title'] ?? $payload['title'] ?? '';
                                $type = $notif['type'];

                                // Generate informative message based on type
                                $icon = '🔔';
                                $subject = '';
                                $message = '';

                                switch ($type) {
                                    case 'new_event':
                                        $icon = '🎉';
                                        $subject = 'Event Baru Dibuat';
                                        $panitiaNama = $payload['panitia_name'] ?? 'Panitia';
                                        $message = $eventTitle ? "{$panitiaNama} membuat event baru '{$eventTitle}' yang menunggu persetujuan" : 'Event baru menunggu persetujuan';
                                        break;
                                    case 'event_need_approval':
                                        $icon = '⏳';
                                        $subject = 'Perlu Persetujuan';
                                        $message = $eventTitle ? "Event '{$eventTitle}' memerlukan persetujuan Anda" : 'Event baru memerlukan persetujuan';
                                        break;
                                    case 'system':
                                        $icon = 'ℹ️';
                                        $subject = 'Notifikasi Sistem';
                                        $message = $payload['message'] ?? 'Notifikasi sistem';
                                        break;
                                    default:
                                        $icon = '🔔';
                                        $subject = $notif['subject'] ?? ucfirst(str_replace('_', ' ', $type));
                                        $message = $notif['message'] ?? $payload['message'] ?? 'Notifikasi baru';
                                }
                                ?>
                                <li style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; gap: 15px; align-items: flex-start; background: <?= ($notif['status'] === 'pending') ? '#f8f9fa' : 'white' ?>;">
                                    <div style="width: 40px; height: 40px; background: #f5f7fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                        <?= $icon ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <p style="margin-bottom: 3px; color: var(--text-dark); font-weight: 600; font-size: 15px;">
                                            <?= htmlspecialchars($subject) ?>
                                        </p>
                                        <p style="margin-bottom: 5px; color: var(--text-dark); font-weight: 400;">
                                            <?= htmlspecialchars($message) ?>
                                        </p>
                                        <span style="font-size: 12px; color: var(--text-muted);">
                                            <?php if (!empty($notif['send_at'])): ?>
                                                <?= date('d M Y, H:i', strtotime($notif['send_at'])) ?>
                                            <?php else: ?>
                                                <?= date('d M Y, H:i', strtotime($notif['created_at'])) ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-center" style="padding: 60px;">
                            <p class="text-muted">Tidak ada notifikasi.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>