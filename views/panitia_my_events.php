<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'panitia') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get events created by panitia
$query = "SELECT * FROM events WHERE created_by = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Saya - EventSite</title>
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
                    <button class="sidebar-toggle" onclick="toggleSidebar()" style="display:none; background:none; border:none; font-size:24px; cursor:pointer; margin-right:10px;">☰</button>
                    <h1>Kelola Event</h1>
                    <div class="header-breadcrumb">Daftar event yang Anda kelola</div>
                </div>
                <div class="header-actions">
                    <a href="index.php?page=panitia_create_event" class="btn btn-primary btn-sm">➕ Tambah Event</a>
                </div>
            </header>

            <?php if (count($events) > 0): ?>
                <div class="card">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa; text-align: left;">
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Judul Event</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Jadwal</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Lokasi</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Kapasitas</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Status</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <strong><?= htmlspecialchars($event['title']) ?></strong>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <?= date('d M Y H:i', strtotime($event['start_at'])) ?>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <?= htmlspecialchars($event['location']) ?>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <?= $event['capacity'] ?>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <span class="badge badge-<?= $event['status'] === 'approved' ? 'success' : ($event['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($event['status']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <div class="d-flex gap-2">
                                            <a href="index.php?page=event-detail&id=<?= $event['id'] ?>" class="btn btn-outline btn-sm">View</a>
                                            <a href="index.php?page=panitia_participants&event_id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">Peserta</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center" style="padding: 60px;">
                    <p class="text-muted">Anda belum membuat event apapun.</p>
                    <a href="index.php?page=panitia_create_event" class="btn btn-primary mt-2">Buat Event Pertama</a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>