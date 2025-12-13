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
$notifications = $db->query("
    SELECT * FROM notifications 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC
")->fetchAll();

// Mark as read (optional, simple logic)
// $db->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id");

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
                                <li style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; gap: 15px; background: <?= $notif['is_read'] ? 'white' : '#f8f9fa' ?>;">
                                    <div style="font-size: 20px;">
                                        <?= $notif['type'] === 'info' ? 'ℹ️' : '🔔' ?>
                                    </div>
                                    <div>
                                        <h4 style="margin: 0 0 5px; font-size: 16px;"><?= htmlspecialchars($notif['subject']) ?></h4>
                                        <div style="color: #555; font-size: 14px; margin-bottom: 5px;"><?= $notif['message'] ?></div> <!-- Allow HTML in message -->
                                        <small class="text-muted"><?= date('d M Y H:i', strtotime($notif['created_at'])) ?></small>
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