<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('admin');

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

// Get event ID
$event_id = $_GET['event_id'] ?? null;

// Get event details
$event = null;
if ($event_id) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all events for dropdown
$all_events = $db->query("
    SELECT id, title, start_at, status 
    FROM events 
    WHERE status = 'approved' 
    ORDER BY start_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get participants if event is selected
$participants = [];
if ($event_id) {
    $stmt = $db->prepare("
        SELECT 
            p.*,
            u.name as user_name,
            u.email as user_email,
            c.id as certificate_id,
            c.file_path as certificate_path
        FROM participants p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN certificates c ON c.participant_id = p.id
        WHERE p.event_id = ?
        ORDER BY p.registered_at DESC
    ");
    $stmt->execute([$event_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Kehadiran - EventSite</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <?php
            $page_title = 'Konfirmasi Kehadiran Peserta';
            $breadcrumb = 'Kelola peserta event dan generate sertifikat';
            include 'components/dashboard_header.php';
            ?>

            <!-- Breadcrumb Navigation -->
            <?php if ($event_id): ?>
                <div style="margin-bottom: 20px;">
                    <a href="index.php?page=admin_edit_event&id=<?= $event_id ?>" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                        ← Kembali ke Edit Event
                    </a>
                </div>
            <?php else: ?>
                <div style="margin-bottom: 20px;">
                    <a href="index.php?page=admin_manage_events" class="btn btn-outline" style="display: inline-flex; align-items: center; gap: 8px;">
                        ← Kembali ke Kelola Event
                    </a>
                </div>
            <?php endif; ?>

            <!-- Event Selector -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header">
                    <h3>Pilih Event</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="index.php">
                        <input type="hidden" name="page" value="admin_confirm_attendance">
                        <div style="display: flex; gap: 15px; align-items: end;">
                            <div class="form-group" style="flex: 1;">
                                <label>Event</label>
                                <select name="event_id" class="form-control" required>
                                    <option value="">-- Pilih Event --</option>
                                    <?php if (!empty($all_events)): ?>
                                        <?php foreach ($all_events as $evt): ?>
                                            <option value="<?= $evt['id'] ?>" <?= $event_id == $evt['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($evt['title']) ?>
                                                (<?= date('d M Y', strtotime($evt['start_at'])) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>Tidak ada event yang disetujui</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Tampilkan Peserta</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($event_id && $event): ?>
                <!-- Event Info -->
                <div class="alert alert-info" style="margin-bottom: 30px;">
                    <strong>Event:</strong> <?= htmlspecialchars($event['title']) ?><br>
                    <strong>Tanggal:</strong> <?= date('d M Y H:i', strtotime($event['start_at'])) ?> - <?= date('d M Y H:i', strtotime($event['end_at'])) ?><br>
                    <strong>Total Peserta:</strong> <?= count($participants) ?> orang
                </div>

                <!-- Participants Table -->
                <div class="card">
                    <div class="card-header">
                        <h3>Daftar Peserta</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (!empty($participants)): ?>
                            <div style="overflow-x: auto;">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Peserta</th>
                                            <th>Email</th>
                                            <th>Tanggal Daftar</th>
                                            <th>Status</th>
                                            <th>Sertifikat</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($participants as $index => $p): ?>
                                            <tr id="row-<?= $p['id'] ?>">
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($p['user_name']) ?></td>
                                                <td><?= htmlspecialchars($p['user_email']) ?></td>
                                                <td><?= date('d M Y H:i', strtotime($p['registered_at'])) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $p['status'] === 'checked_in' ? 'success' : ($p['status'] === 'cancelled' ? 'danger' : 'warning') ?>">
                                                        <?= $p['status'] === 'checked_in' ? 'Hadir' : ($p['status'] === 'cancelled' ? 'Batal' : 'Terdaftar') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($p['certificate_id']): ?>
                                                        <a href="<?= $p['certificate_path'] ?>" target="_blank" class="btn btn-sm btn-success">
                                                            📄 Lihat
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Belum ada</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($p['status'] !== 'checked_in'): ?>
                                                        <button
                                                            onclick="confirmAttendance(<?= $p['id'] ?>)"
                                                            class="btn btn-sm btn-primary"
                                                            id="btn-<?= $p['id'] ?>">
                                                            ✅ Konfirmasi Hadir
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">✓ Sudah Dikonfirmasi</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px;">
                                <p style="color: var(--text-muted);">Belum ada peserta yang mendaftar event ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center" style="padding: 60px 20px;">
                    <p style="color: var(--text-muted); font-size: 18px;">Silahkan pilih event terlebih dahulu</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function confirmAttendance(participantId) {
            if (!confirm('Konfirmasi kehadiran peserta ini? Sertifikat akan otomatis di-generate.')) {
                return;
            }

            const btn = document.getElementById('btn-' + participantId);
            btn.disabled = true;
            btn.textContent = 'Memproses...';

            fetch('api/certificates.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=confirm_attendance&participant_id=' + participantId
                })
                .then(response => response.text())
                .then(data => {
                    if (data === 'SUCCESS') {
                        alert('✅ Kehadiran dikonfirmasi dan sertifikat berhasil di-generate!');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + data);
                        btn.disabled = false;
                        btn.textContent = '✅ Konfirmasi Hadir';
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                    btn.disabled = false;
                    btn.textContent = '✅ Konfirmasi Hadir';
                });
        }
    </script>
</body>

</html>