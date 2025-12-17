<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'panitia') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get notifications
$query = "
    SELECT * FROM notifications 
    WHERE user_id = :user_id 
    ORDER BY COALESCE(send_at, created_at) DESC 
    LIMIT 50
";

$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$notifications = $stmt->fetchAll();

// Mark as read (update status to 'sent' when viewing)
$db->prepare("UPDATE notifications SET status = 'sent' WHERE user_id = ? AND status = 'pending'")->execute([$user_id]);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi - EventSite</title>
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
            $breadcrumb = 'Pemberitahuan aktivitas terbaru';
            include 'components/dashboard_header.php';
            ?>

            <div class="card">
                <div class="card-body">
                    <?php if (count($notifications) > 0): ?>
                        <ul style="list-style: none; padding: 0;">
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
                                    case 'event_approved':
                                        $icon = '✅';
                                        $subject = 'Event Disetujui';
                                        $message = $eventTitle ? "Event '{$eventTitle}' telah disetujui admin dan sekarang dapat dilihat user" : 'Event Anda telah disetujui admin';
                                        break;
                                    case 'event_rejected':
                                        $icon = '❌';
                                        $subject = 'Event Ditolak';
                                        $message = $eventTitle ? "Event '{$eventTitle}' ditolak admin" : 'Event Anda ditolak admin';
                                        break;
                                    case 'new_participant':
                                        $icon = '👤';
                                        $subject = 'Peserta Baru';
                                        $userName = $payload['user_name'] ?? 'Seseorang';
                                        $message = $eventTitle ? "{$userName} mendaftar event '{$eventTitle}'" : "Peserta baru mendaftar event Anda";
                                        break;
                                    case 'event_reminder':
                                        $icon = '⏰';
                                        $subject = 'Pengingat Event';
                                        $message = $eventTitle ? "Event '{$eventTitle}' segera dimulai!" : 'Event Anda segera dimulai';
                                        break;
                                    default:
                                        $icon = '🔔';
                                        $subject = ucfirst(str_replace('_', ' ', $type));
                                        $message = $payload['message'] ?? 'Notifikasi baru';
                                }
                                ?>
                                <li style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; gap: 15px; align-items: flex-start;">
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
                        <div class="text-center" style="padding: 40px;">
                            <p class="text-muted">Tidak ada notifikasi.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>