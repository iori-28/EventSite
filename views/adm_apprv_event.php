<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$events = $db->query("SELECT e.*, u.name as creator_name FROM events e LEFT JOIN users u ON e.created_by = u.id WHERE e.status = 'pending' ORDER BY e.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Persetujuan Event - Admin</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <header class="dashboard-header">
                <div class="header-title">
                    <h1>Persetujuan Event</h1>
                    <div class="header-breadcrumb">Kelola event yang menunggu persetujuan</div>
                </div>
            </header>

            <?php if (count($events) > 0): ?>
                <div class="grid grid-2" style="gap: 20px;">
                    <?php foreach ($events as $e): ?>
                        <div class="card">
                            <div class="card-img" style="background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">
                                📅
                            </div>
                            <div class="card-body" style="padding: 20px;">
                                <h3 class="card-title" style="font-size: 18px; margin-bottom: 10px;">
                                    <?= htmlspecialchars($e['title']) ?>
                                </h3>

                                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 8px;">
                                    👤 Dibuat oleh: <strong><?= htmlspecialchars($e['creator_name']) ?></strong>
                                </p>

                                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 8px;">
                                    📍 <?= htmlspecialchars($e['location']) ?>
                                </p>

                                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 8px;">
                                    🕐 <?= date('d M Y, H:i', strtotime($e['start_at'])) ?>
                                </p>

                                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 15px;">
                                    👥 Kapasitas: <?= $e['capacity'] ?> orang
                                </p>

                                <div class="d-flex gap-2">
                                    <form method="POST" action="api/event_approval.php" style="flex: 1;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                                            ✅ Setujui
                                        </button>
                                    </form>

                                    <form method="POST" action="api/event_approval.php" style="flex: 1;">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" class="btn btn-outline" style="width: 100%; border-color: #dc3545; color: #dc3545;">
                                            ❌ Tolak
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="card text-center" style="padding: 60px;">
                    <p style="font-size: 16px; color: var(--text-muted);">
                        Tidak ada event yang menunggu persetujuan.
                    </p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>