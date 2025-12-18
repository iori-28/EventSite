<?php
// Session already started in index.php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('admin');

// Prevent caching for fresh data
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

// Get event ID
$event_id = $_GET['event_id'] ?? null;

// Get event details
$event = null;
if ($event_id) {
    $stmt = $db->prepare("
        SELECT e.*, u.name as panitia_name, 
               completed_user.name as completed_by_name,
               e.completed_at
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        LEFT JOIN users completed_user ON e.completed_by = completed_user.id
        WHERE e.id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get events waiting for approval (with distinct to prevent duplicates)
$waiting_events = $db->query("
    SELECT DISTINCT e.id, e.title, e.description, e.location, e.start_at, e.end_at, 
           e.capacity, e.status, e.completed_by, e.completed_at, e.created_by, e.created_at,
           u.name as panitia_name,
           (SELECT COUNT(*) FROM participants WHERE event_id = e.id AND status = 'checked_in') as attended_count
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.status = 'waiting_completion'
    ORDER BY e.completed_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get completed events (already approved)
$completed_events = $db->query("
    SELECT e.*, u.name as panitia_name, e.approved_at,
           (SELECT COUNT(*) FROM participants WHERE event_id = e.id AND status = 'checked_in') as attended_count
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.status = 'completed'
    ORDER BY e.approved_at DESC
    LIMIT 10
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
        ORDER BY p.status DESC, p.registered_at DESC
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
    <title>Event Completion Approval - EventSite</title>
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
            $page_title = 'Event Completion Approval';
            $breadcrumb = 'Review dan approve penyelesaian event oleh panitia';
            include 'components/dashboard_header.php';
            ?>

            <!-- Waiting for Approval Section -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header" style="background: #fff3cd; border-bottom: 2px solid #ffc107;">
                    <h3 style="margin: 0; color: #856404;">⏳ Menunggu Approval (<?= count($waiting_events) ?>)</h3>
                </div>
                <div class="card-body" style="padding: 0;">
                    <?php if (!empty($waiting_events)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Panitia</th>
                                    <th>Tanggal Event</th>
                                    <th>Completed By</th>
                                    <th>Peserta Hadir</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($waiting_events as $evt): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($evt['title']) ?></strong><br>
                                            <small style="color: #666;"><?= htmlspecialchars($evt['location']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($evt['panitia_name']) ?></td>
                                        <td><?= date('d M Y', strtotime($evt['start_at'])) ?></td>
                                        <td>
                                            <small style="color: #666;">
                                                <?= date('d M Y H:i', strtotime($evt['completed_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary"><?= $evt['attended_count'] ?> orang</span>
                                        </td>
                                        <td>
                                            <a href="?page=admin_event_completion&event_id=<?= $evt['id'] ?>" class="btn btn-sm btn-primary">
                                                👁️ Review
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="text-center" style="padding: 40px;">
                            <p style="color: var(--text-muted);">✓ Tidak ada event yang menunggu approval</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($event_id && $event): ?>
                <!-- Event Review Detail -->
                <div class="alert" style="background: #d1ecf1; border-left: 4px solid #0c5460; margin-bottom: 30px;">
                    <h4 style="margin: 0 0 10px 0; color: #0c5460;">📋 Event Detail</h4>
                    <strong>Event:</strong> <?= htmlspecialchars($event['title']) ?><br>
                    <strong>Panitia:</strong> <?= htmlspecialchars($event['panitia_name']) ?><br>
                    <strong>Tanggal:</strong> <?= date('d M Y H:i', strtotime($event['start_at'])) ?> - <?= date('d M Y H:i', strtotime($event['end_at'])) ?><br>
                    <strong>Completed By:</strong> <?= htmlspecialchars($event['completed_by_name']) ?> pada <?= date('d M Y H:i', strtotime($event['completed_at'])) ?><br>
                    <strong>Status:</strong> <span class="badge badge-warning">Menunggu Approval</span>
                </div>

                <!-- Participants Table -->
                <div class="card" style="margin-bottom: 30px;">
                    <div class="card-header">
                        <h3>Daftar Peserta & Kehadiran</h3>
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
                                            <th>Status Kehadiran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($participants as $index => $p): ?>
                                            <tr style="<?= $p['status'] === 'checked_in' ? 'background: #d4edda;' : '' ?>">
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($p['user_name']) ?></td>
                                                <td><?= htmlspecialchars($p['user_email']) ?></td>
                                                <td><?= date('d M Y H:i', strtotime($p['registered_at'])) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $p['status'] === 'checked_in' ? 'success' : ($p['status'] === 'cancelled' ? 'danger' : 'warning') ?>">
                                                        <?= $p['status'] === 'checked_in' ? '✓ Hadir' : ($p['status'] === 'cancelled' ? 'Batal' : 'Terdaftar') ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Summary -->
                            <div style="padding: 20px; background: #f8f9fa; border-top: 1px solid #dee2e6;">
                                <strong>Summary:</strong>
                                <?php
                                $attended = array_filter($participants, fn($p) => $p['status'] === 'checked_in');
                                $attended_count = count($attended);
                                $total = count($participants);
                                ?>
                                <span class="badge badge-success"><?= $attended_count ?> Hadir</span>
                                <span class="badge badge-secondary"><?= $total - $attended_count ?> Tidak Hadir</span>
                                <span style="margin-left: 10px; color: #666;">Total: <?= $total ?> peserta</span>
                            </div>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px;">
                                <p style="color: var(--text-muted);">Belum ada peserta yang mendaftar event ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Approval Actions -->
                <?php if ($event['status'] === 'waiting_completion'): ?>
                    <div class="card">
                        <div class="card-header" style="background: #d4edda; border-bottom: 2px solid #28a745;">
                            <h3 style="margin: 0; color: #155724;">✓ Approve Event Completion</h3>
                        </div>
                        <div class="card-body">
                            <p><strong>Setelah approve:</strong></p>
                            <ul>
                                <li>Sertifikat akan otomatis digenerate untuk <strong><?= $attended_count ?> peserta</strong> yang hadir</li>
                                <li>Notifikasi email akan dikirim ke semua peserta yang hadir</li>
                                <li>Status event akan berubah menjadi <span class="badge badge-success">Completed</span></li>
                                <li>Aksi ini tidak dapat dibatalkan</li>
                            </ul>

                            <div style="margin-top: 20px; display: flex; gap: 10px;">
                                <button onclick="approveCompletion(<?= $event_id ?>)" class="btn btn-success" style="padding: 12px 30px;">
                                    ✓ Approve & Generate Certificates
                                </button>
                                <button onclick="rejectCompletion(<?= $event_id ?>)" class="btn btn-danger">
                                    ✗ Reject Completion
                                </button>
                                <a href="?page=admin_event_completion" class="btn btn-outline">
                                    ← Kembali
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Completed Events History -->
                <div class="card">
                    <div class="card-header" style="background: #d4edda; border-bottom: 2px solid #28a745;">
                        <h3 style="margin: 0; color: #155724;">✓ Recently Completed Events</h3>
                    </div>
                    <div class="card-body" style="padding: 0;">
                        <?php if (!empty($completed_events)): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Panitia</th>
                                        <th>Tanggal Event</th>
                                        <th>Approved At</th>
                                        <th>Peserta Hadir</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed_events as $evt): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($evt['title']) ?></strong><br>
                                                <small style="color: #666;"><?= htmlspecialchars($evt['location']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($evt['panitia_name']) ?></td>
                                            <td><?= date('d M Y', strtotime($evt['start_at'])) ?></td>
                                            <td>
                                                <small style="color: #666;">
                                                    <?= date('d M Y H:i', strtotime($evt['approved_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge badge-success"><?= $evt['attended_count'] ?> orang</span>
                                            </td>
                                            <td>
                                                <a href="?page=admin_event_completion&event_id=<?= $evt['id'] ?>" class="btn btn-sm btn-outline">
                                                    👁️ View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="text-center" style="padding: 40px;">
                                <p style="color: var(--text-muted);">Belum ada event yang selesai</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function approveCompletion(eventId) {
            if (!confirm('⚠️ KONFIRMASI APPROVAL\n\nApakah Anda yakin ingin approve event completion ini?\n\nSetelah approve:\n- Sertifikat akan otomatis digenerate\n- Notifikasi dikirim ke peserta\n- Aksi tidak dapat dibatalkan\n\nLanjutkan?')) {
                return;
            }

            const btn = event.target;
            btn.disabled = true;
            btn.textContent = 'Processing...';

            fetch('api/admin_event_completion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=approve_completion&event_id=${eventId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`✓ SUCCESS!\n\nEvent approved dan ${data.success_count} sertifikat berhasil digenerate!`);
                        window.location.href = '?page=admin_event_completion';
                    } else {
                        alert('❌ Error: ' + (data.message || 'Failed to approve completion'));
                        btn.disabled = false;
                        btn.textContent = '✓ Approve & Generate Certificates';
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                    btn.disabled = false;
                    btn.textContent = '✓ Approve & Generate Certificates';
                });
        }

        function rejectCompletion(eventId) {
            const reason = prompt('Alasan rejection (opsional):');
            if (reason === null) return; // User cancelled

            fetch('api/admin_event_completion.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=reject_completion&event_id=${eventId}&reason=${encodeURIComponent(reason)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Event completion rejected. Status dikembalikan ke approved.');
                        window.location.href = '?page=admin_event_completion';
                    } else {
                        alert('Error: ' + (data.message || 'Failed to reject completion'));
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
        }
    </script>
</body>

</html>