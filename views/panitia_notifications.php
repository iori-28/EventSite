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
    ORDER BY send_at DESC 
    LIMIT 50
";

$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$notifications = $stmt->fetchAll();
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
                                $payload = json_decode($notif['payload'] ?? '{}', true);
                                $message = $payload['message'] ?? 'Notifikasi baru';
                                $type = $notif['type'];

                                $icon = '🔔';
                                if ($type === 'registration') $icon = '📝';
                                elseif ($type === 'approval') $icon = '✅';
                                elseif ($type === 'reminder') $icon = '⏰';
                                ?>
                                <li style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; gap: 15px; align-items: flex-start;">
                                    <div style="width: 40px; height: 40px; background: #f5f7fa; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                                        <?= $icon ?>
                                    </div>
                                    <div style="flex: 1;">
                                        <p style="margin-bottom: 5px; color: var(--text-dark); font-weight: 500;">
                                            <?= htmlspecialchars($message) ?>
                                        </p>
                                        <span style="font-size: 12px; color: var(--text-muted);">
                                            <?= date('d M Y, H:i', strtotime($notif['send_at'])) ?>
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