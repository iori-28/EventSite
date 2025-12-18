<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('admin');

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
                    <input type="hidden" name="page" value="admin_manage_events">
                    <select name="status" class="form-control" style="width: auto; min-width: 150px;">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Menunggu Review</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Disetujui</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Selesai</option>
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
                        <!-- Bulk Actions Toolbar -->
                        <form method="POST" action="api/events_bulk.php" id="bulk-form" style="padding: 15px; border-bottom: 1px solid var(--border-color); background: #f8f9fa;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label style="font-weight: 500;">With Selected:</label>
                                <select name="bulk_action" required style="padding: 8px; border-radius: 4px; border: 1px solid var(--border-color);">
                                    <option value="">-- Choose Action --</option>
                                    <option value="approve">✓ Approve</option>
                                    <option value="reject">✗ Reject</option>
                                    <option value="delete">🗑 Delete</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirmBulkAction()">Apply</button>
                                <span id="selected-count" style="margin-left: 10px; color: #666; font-size: 13px;">0 selected</span>
                            </div>
                        </form>

                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; text-align: left;">
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color); width: 30px;">
                                        <input type="checkbox" id="select-all" onclick="toggleSelectAll(this)">
                                    </th>
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
                                        <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: center;">
                                            <input type="checkbox" name="event_ids[]" value="<?= $event['id'] ?>" class="event-checkbox" form="bulk-form" onchange="updateSelectedCount()">
                                        </td>
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
                                            <span class="badge badge-<?= $event['status'] === 'completed' ? 'success' : ($event['status'] === 'approved' ? 'success' : ($event['status'] === 'waiting_completion' ? 'info' : ($event['status'] === 'rejected' ? 'danger' : 'warning'))) ?>">
                                                <?= $event['status'] === 'completed' ? '✓ Selesai' : ($event['status'] === 'waiting_completion' ? '⏳ Menunggu' : ucfirst($event['status'])) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <div class="d-flex gap-2">
                                                <a href="index.php?page=event-detail&id=<?= $event['id'] ?>&from=admin_manage_events" class="btn btn-outline btn-sm">Review</a>
                                                <a href="index.php?page=admin_edit_event&id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">✏️ Edit</a>

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
                                                    <button onclick="completeEvent(<?= $event['id'] ?>)" class="btn btn-success btn-sm" style="background: #28a745;">✅ Complete Event</button>
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
        // Bulk Actions Functions
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.event-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateSelectedCount();
        }

        function updateSelectedCount() {
            const checked = document.querySelectorAll('.event-checkbox:checked').length;
            document.getElementById('selected-count').textContent = checked + ' selected';

            // Update select-all checkbox state
            const total = document.querySelectorAll('.event-checkbox').length;
            const selectAll = document.getElementById('select-all');
            if (selectAll) {
                selectAll.checked = (checked === total && total > 0);
                selectAll.indeterminate = (checked > 0 && checked < total);
            }
        }

        function confirmBulkAction() {
            const checked = document.querySelectorAll('.event-checkbox:checked');
            if (checked.length === 0) {
                alert('Please select at least one event');
                return false;
            }

            const action = document.querySelector('select[name="bulk_action"]').value;
            if (!action) {
                alert('Please choose an action');
                return false;
            }

            const actionText = {
                'approve': 'approve',
                'reject': 'reject',
                'delete': 'DELETE'
            };

            return confirm(`Are you sure you want to ${actionText[action]} ${checked.length} event(s)?`);
        }

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

        function completeEvent(eventId) {
            if (!confirm('⚠️ Apakah Anda yakin ingin menyelesaikan event ini?\n\nEvent akan langsung diset sebagai completed dan sertifikat akan digenerate (jika ada peserta yang hadir).')) {
                return;
            }

            const formData = new FormData();
            formData.append('action', 'admin_complete');
            formData.append('event_id', eventId);
            formData.append('force', 0);

            fetch('api/admin_event_completion.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ ' + data.message);
                        location.reload();
                    } else if (data.require_confirm) {
                        // Event has no participants, ask for confirmation
                        if (confirm('⚠️ ' + data.message)) {
                            // User confirmed, send force=1
                            const forceData = new FormData();
                            forceData.append('action', 'admin_complete');
                            forceData.append('event_id', eventId);
                            forceData.append('force', 1);

                            fetch('api/admin_event_completion.php', {
                                    method: 'POST',
                                    body: forceData
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        alert('✅ ' + data.message);
                                        location.reload();
                                    } else {
                                        alert('❌ Error: ' + data.message);
                                    }
                                })
                                .catch(err => {
                                    console.error(err);
                                    alert('❌ Terjadi kesalahan sistem.');
                                });
                        }
                    } else {
                        alert('❌ Error: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('❌ Terjadi kesalahan sistem.');
                });
        }
    </script>
</body>

</html>