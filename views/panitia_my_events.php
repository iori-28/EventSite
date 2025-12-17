<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'panitia') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get flash messages
$success_msg = $_SESSION['flash_success'] ?? '';
$error_msg = $_SESSION['flash_error'] ?? '';
if ($success_msg) unset($_SESSION['flash_success']);
if ($error_msg) unset($_SESSION['flash_error']);

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

            <?php if ($success_msg): ?>
                <div class="alert alert-success mb-4" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    ✅ <?= htmlspecialchars($success_msg) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-error mb-4" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    ❌ <?= htmlspecialchars($error_msg) ?>
                </div>
            <?php endif; ?>

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
                                        <span class="badge badge-<?= $event['status'] === 'completed' ? 'success' : ($event['status'] === 'waiting_completion' ? 'info' : ($event['status'] === 'approved' ? 'success' : ($event['status'] === 'rejected' ? 'danger' : 'warning'))) ?>">
                                            <?= $event['status'] === 'completed' ? '✓ Selesai' : ($event['status'] === 'waiting_completion' ? '⏳ Menunggu Approval' : ucfirst($event['status'])) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <div class="d-flex gap-2">
                                            <a href="index.php?page=event-detail&id=<?= $event['id'] ?>&from=panitia_my_events" class="btn btn-outline btn-sm">View</a>

                                            <?php if ($event['status'] !== 'completed'): ?>
                                                <a href="index.php?page=panitia_edit_event&id=<?= $event['id'] ?>" class="btn btn-success btn-sm">✏️ Edit</a>
                                            <?php endif; ?>

                                            <a href="index.php?page=panitia_participants&event_id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">Peserta</a>

                                            <?php if ($event['status'] === 'approved'): ?>
                                                <button onclick="completeEvent(<?= $event['id'] ?>)" class="btn btn-info btn-sm">✅ Selesaikan</button>
                                            <?php elseif ($event['status'] === 'waiting_completion'): ?>
                                                <span class="badge badge-info" style="font-size: 11px;">⏳ Waiting Admin</span>
                                            <?php endif; ?>
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

    <script>
        function completeEvent(eventId) {
            if (!confirm('Apakah Anda yakin event ini sudah selesai?\n\nSetelah diklik:\n- Event akan dikirim ke admin untuk approval\n- Pastikan sudah konfirmasi kehadiran peserta\n- Sertifikat akan digenerate setelah admin approve')) {
                return;
            }

            fetch('api/events.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=complete&event_id=' + eventId
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'SUCCESS_WAITING_ADMIN_APPROVAL') {
                        alert('✓ Event completion request sent!\n\nMenunggu approval dari admin.\nSetelah diapprove, sertifikat akan otomatis digenerate.');
                        location.reload();
                    } else if (data === 'NO_ATTENDED_PARTICIPANTS') {
                        alert('❌ Tidak ada peserta yang hadir!\n\nPastikan Anda sudah konfirmasi kehadiran peserta terlebih dahulu.');
                    } else {
                        alert('Gagal menyelesaikan event: ' + data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat menyelesaikan event');
                });
        }
    </script>
</body>

</html>