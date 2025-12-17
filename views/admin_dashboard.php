<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

// Get Stats
$stats = [
    'pending_events' => $db->query("SELECT COUNT(*) FROM events WHERE status = 'pending'")->fetchColumn(),
    'approved_events' => $db->query("SELECT COUNT(*) FROM events WHERE status = 'approved'")->fetchColumn(),
    'total_users' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'total_panitia' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'panitia'")->fetchColumn()
];

// Get Pending Events
$pending_list = $db->query("
    SELECT e.*, u.name as organizer 
    FROM events e 
    JOIN users u ON e.created_by = u.id 
    WHERE e.status = 'pending' 
    ORDER BY e.created_at ASC 
    LIMIT 5
")->fetchAll();

// Get Recent Users
$recent_users = $db->query("
    SELECT * FROM users 
    WHERE role IN ('user', 'panitia') 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - EventSite</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <!-- Main Content -->
        <main class="main-content">
            <?php
            $page_title = 'Dashboard Admin';
            $breadcrumb = 'Ringkasan sistem dan persetujuan';
            include 'components/dashboard_header.php';
            ?>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= $stats['pending_events'] ?></h3>
                        <p>Menunggu Approval</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(234, 187, 102, 0.1); color: #ffc107;">⏳</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= $stats['approved_events'] ?></h3>
                        <p>Event Aktif</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(102, 234, 150, 0.1); color: #28a745;">📅</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= $stats['total_users'] ?></h3>
                        <p>Total User</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color);">👥</div>
                </div>
                <div class="stat-card">
                    <div class="stat-info">
                        <h3><?= $stats['total_panitia'] ?></h3>
                        <p>Total Panitia</p>
                    </div>
                    <div class="stat-icon" style="background: rgba(142, 68, 173, 0.1); color: #8e44ad;">👔</div>
                </div>
            </div>

            <div class="grid grid-2" style="gap: 30px;">
                <!-- Pending Events -->
                <div class="card">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 18px; margin: 0;">Butuh Persetujuan</h3>
                        <a href="index.php?page=adm_apprv_event" class="btn btn-outline btn-sm">Kelola Semua</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (count($pending_list) > 0): ?>
                            <?php foreach ($pending_list as $event): ?>
                                <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: start;">
                                    <div>
                                        <h4 style="font-size: 16px; margin-bottom: 4px;"><?= htmlspecialchars($event['title']) ?></h4>
                                        <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 4px;">
                                            Oleh: <strong><?= htmlspecialchars($event['organizer']) ?></strong>
                                        </p>
                                        <p style="font-size: 12px; color: var(--text-muted);">
                                            <?= date('d M Y', strtotime($event['start_at'])) ?>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="index.php?page=event-detail&id=<?= $event['id'] ?>&from=dashboard" class="btn btn-outline btn-sm">Lihat</a>
                                        <form method="POST" action="api/event_approval.php" style="display:inline;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                            <button type="submit" class="btn btn-primary btn-sm" style="background: #28a745; border-color: #28a745;">✓</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px 20px;">
                                <p style="color: var(--text-muted);">Tidak ada event menunggu.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="card">
                    <div class="card-header" style="padding: 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="font-size: 18px; margin: 0;">User Baru</h3>
                        <a href="index.php?page=admin_manage_users" class="btn btn-outline btn-sm">Kelola User</a>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (count($recent_users) > 0): ?>
                            <?php foreach ($recent_users as $u): ?>
                                <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px;">
                                    <div style="width: 36px; height: 36px; background: #f0f2f5; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; color: var(--text-muted);">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <h4 style="font-size: 14px; margin-bottom: 2px;"><?= htmlspecialchars($u['name']) ?></h4>
                                        <span class="badge" style="background: <?= $u['role'] === 'panitia' ? '#8e44ad' : '#3498db' ?>; color: white; padding: 2px 6px; font-size: 10px; border-radius: 4px;">
                                            <?= ucfirst($u['role']) ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px 20px;">
                                <p style="color: var(--text-muted);">Belum ada user.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>