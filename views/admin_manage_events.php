<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

// Filtering
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$sql = "SELECT e.*, u.name as organizer FROM events e JOIN users u ON e.created_by = u.id WHERE 1=1";
$params = [];

if ($status !== 'all') {
    $sql .= " AND e.status = :status";
    $params[':status'] = $status;
}

if ($search) {
    $sql .= " AND (e.title LIKE :search OR u.name LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY e.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Event - EventSite Admin</title>
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
                    <div class="header-breadcrumb">Approval dan manajemen semua event</div>
                </div>
            </header>

            <div class="card mb-4" style="padding: 20px;">
                <form method="GET" class="d-flex align-center gap-2" style="flex-wrap: wrap;">
                    <select name="status" class="form-control" style="width: auto; min-width: 150px;">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Menunggu Review</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Ditolak</option>
                    </select>

                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari event atau panitia..." class="form-control" style="width: auto; flex: 1;">

                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="index.php?page=admin_manage_events" class="btn btn-outline">Reset</a>
                </form>
            </div>

            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <?php if (count($events) > 0): ?>
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; text-align: left;">
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Event</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Panitia</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Tanggal</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Status</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <strong><?= htmlspecialchars($event['title']) ?></strong>
                                            <div class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($event['location']) ?></div>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <?= htmlspecialchars($event['organizer']) ?>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <?= date('d M Y', strtotime($event['start_at'])) ?>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <span class="badge badge-<?= $event['status'] === 'approved' ? 'success' : ($event['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                                <?= ucfirst($event['status']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <div class="d-flex gap-2">
                                                <a href="index.php?page=event-detail&id=<?= $event['id'] ?>" class="btn btn-outline btn-sm">Review</a>

                                                <?php if ($event['status'] === 'pending'): ?>
                                                    <form method="POST" action="api/event_approval.php" style="display: inline;">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                                        <button type="submit" class="btn btn-primary btn-sm" style="background: #28a745; border-color: #28a745;">✓</button>
                                                    </form>
                                                    <form method="POST" action="api/event_approval.php" style="display: inline;">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" style="background: #dc3545; border-color: #dc3545; color: white;">✗</button>
                                                    </form>
                                                <?php elseif ($event['status'] === 'approved'): ?>
                                                    <form method="POST" action="api/event_approval.php" style="display: inline;">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                                        <button type="submit" class="btn btn-warning btn-sm">Tolak</button>
                                                    </form>
                                                <?php endif; ?>

                                                <form method="POST" action="api/event_approval.php" style="display: inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm" style="border-color: #dc3545; color: #dc3545;">🗑</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 60px;">
                            <p class="text-muted">Tidak ada event ditemukan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        function updateEvent(id, action) {
            if (!confirm('Apakah Anda yakin ingin melakukan tindakan ini?')) return;

            const formData = new FormData();
            formData.append('action', action);
            formData.append('id', id);

            fetch('../api/events.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json()) // Expect JSON
                .then(data => {
                    if (data.status) {
                        alert('Berhasil!');
                        location.reload();
                    } else {
                        alert('Gagal memproses permintaan.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan sistem.');
                });
        }
    </script>
</body>

</html>