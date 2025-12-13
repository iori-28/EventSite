<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'panitia') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get Stats
// Note: In real app, we should check organization_id, but for now assuming event created_by user
$stats = [
    'total_events' => $db->query("SELECT COUNT(*) FROM events WHERE created_by = $user_id")->fetchColumn(),
    'active_events' => $db->query("SELECT COUNT(*) FROM events WHERE created_by = $user_id AND start_at > NOW()")->fetchColumn(),
    'total_participants' => $db->query("
        SELECT COUNT(*) FROM participants p 
        JOIN events e ON p.event_id = e.id 
        WHERE e.created_by = $user_id
    ")->fetchColumn()
];

// Get Recent Events created by Panitia
$recent_events = $db->query("
    SELECT * FROM events 
    WHERE created_by = $user_id 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

// Get Recent Registrations
$recent_participants = $db->query("
    SELECT p.*, u.name as user_name, e.title as event_title 
    FROM participants p 
    JOIN events e ON p.event_id = e.id 
    JOIN users u ON p.user_id = u.id 
    WHERE e.created_by = $user_id 
    ORDER BY p.registered_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Panitia - EventSite</title>
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
            $page_title = 'Dashboard Panitia';
            $breadcrumb = 'Kelola event dan peserta Anda';
            include 'components/dashboard_header.php';
            ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= $stats['total_events'] ?></h3>
                        <p>Total Event Dibuat</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(102, 234, 150, 0.1); color: #28a745;">📅</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= $stats['active_events'] ?></h3>
                        <p>Event Aktif</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(234, 187, 102, 0.1); color: #ffc107;">⏳</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= $stats['total_participants'] ?></h3>
                        <p>Total Peserta</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color);">👥</div>
                </div>
            </div>

            <div class="grid grid-2" style="gap: 30px;">
                <!-- Recent Events -->
                <div class="card">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 18px; margin: 0;">Event Terbaru</h3>
                        <a href="index.php?page=panitia_my_events" class="btn btn-outline btn-sm">Lihat Semua</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (count($recent_events) > 0): ?>
                            <?php foreach ($recent_events as $event): ?>
                                <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h4 style="font-size: 16px; margin-bottom: 4px;"><?= htmlspecialchars($event['title']) ?></h4>
                                        <p style="font-size: 12px; color: var(--text-muted);">
                                            <?= date('d M Y', strtotime($event['start_at'])) ?> •
                                            <span class="badge badge-<?= $event['status'] === 'approved' ? 'success' : ($event['status'] === 'rejected' ? 'danger' : 'warning') ?>" style="padding: 2px 6px; font-size: 10px;">
                                                <?= ucfirst($event['status']) ?>
                                            </span>
                                        </p>
                                    </div>
                                    <a href="index.php?page=event-detail&id=<?= $event['id'] ?>" class="btn btn-outline btn-sm">Lihat</a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px 20px;">
                                <p style="color: var(--text-muted);">Belum ada event dibuat.</p>
                                <a href="index.php?page=panitia_create_event" class="btn btn-primary btn-sm mt-2">Buat Sekarang</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Participants -->
                <div class="card">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 18px; margin: 0;">Pendaftar Terbaru</h3>
                        <a href="index.php?page=panitia_participants" class="btn btn-outline btn-sm">Semua</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (count($recent_participants) > 0): ?>
                            <?php foreach ($recent_participants as $p): ?>
                                <div style="padding: 15px 20px; border-bottom: 1px solid #eee;">
                                    <div class="d-flex justify-between">
                                        <h4 style="font-size: 14px; margin-bottom: 2px;"><?= htmlspecialchars($p['user_name']) ?></h4>
                                        <span class="text-muted" style="font-size: 11px;"><?= date('d/m H:i', strtotime($p['registered_at'])) ?></span>
                                    </div>
                                    <p style="font-size: 12px; color: var(--text-muted);">
                                        Mendaftar di <strong><?= htmlspecialchars($p['event_title']) ?></strong>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px 20px;">
                                <p style="color: var(--text-muted);">Belum ada pendaftar.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>