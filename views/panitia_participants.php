<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'panitia') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];
$event_id = $_GET['event_id'] ?? null;

// Validate event ownership if event_id is provided
$current_event = null;
if ($event_id) {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = :id AND created_by = :user_id");
    $stmt->execute([':id' => $event_id, ':user_id' => $user_id]);
    $current_event = $stmt->fetch();

    if (!$current_event) {
        die("Event tidak ditemukan atau Anda tidak memiliki akses.");
    }
}

// Get participants
$query = "
    SELECT p.*, u.name as user_name, u.email as user_email, e.title as event_title, e.id as event_id
    FROM participants p 
    JOIN users u ON p.user_id = u.id 
    JOIN events e ON p.event_id = e.id 
    WHERE e.created_by = :user_id
";

$params = [':user_id' => $user_id];

if ($event_id) {
    $query .= " AND e.id = :event_id";
    $params[':event_id'] = $event_id;
}

$query .= " ORDER BY p.registered_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$participants = $stmt->fetchAll();

// Get list of events for filter dropdown
$my_events = $db->prepare("SELECT id, title FROM events WHERE created_by = ?");
$my_events->execute([$user_id]);
$all_my_events = $my_events->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Peserta - EventSite</title>
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
                    <h1>Daftar Peserta</h1>
                    <div class="header-breadcrumb">
                        <?= $current_event ? 'Event: ' . htmlspecialchars($current_event['title']) : 'Semua Peserta' ?>
                    </div>
                </div>
            </header>

            <!-- Filter -->
            <div class="card mb-4" style="padding: 20px;">
                <form method="GET" class="d-flex align-center gap-2">
                    <input type="hidden" name="page" value="panitia_participants">
                    <label style="font-weight: 500;">Filter Event:</label>
                    <select name="event_id" onchange="this.form.submit()" style="padding: 8px; border-radius: 4px; border: 1px solid var(--border-color); min-width: 200px;">
                        <option value="">Semua Event</option>
                        <?php foreach ($all_my_events as $evt): ?>
                            <option value="<?= $evt['id'] ?>" <?= $event_id == $evt['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($evt['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>

            <!-- Attendance Confirmation Actions -->
            <?php if ($event_id && count($participants) > 0): ?>
                <div class="card mb-4" style="padding: 15px; background: #f8f9fa;">
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <button onclick="markSelectedAsAttended()" class="btn btn-success btn-sm">
                            ✓ Tandai Hadir (Selected)
                        </button>
                        <button onclick="markAllAsAttended()" class="btn btn-primary btn-sm">
                            ✓✓ Tandai Semua Hadir
                        </button>
                        <button onclick="openQRScanner()" class="btn btn-outline btn-sm">
                            📷 Scan QR Code
                        </button>
                        <span id="selected-count" style="color: #666; font-size: 13px; margin-left: auto;">0 selected</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Participants Table -->
            <div class="card">
                <?php if (count($participants) > 0): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa; text-align: left;">
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color); width: 30px;">
                                    <input type="checkbox" id="select-all" onclick="toggleSelectAll(this)">
                                </th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Nama Peserta</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Email</th>
                                <?php if (!$event_id): ?>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Event</th>
                                <?php endif; ?>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Tanggal Daftar</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Status Kehadiran</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $p): ?>
                                <tr id="row-<?= $p['id'] ?>">
                                    <td style="padding: 15px; border-bottom: 1px solid #eee; text-align: center;">
                                        <input type="checkbox" class="participant-checkbox" value="<?= $p['id'] ?>" onchange="updateSelectedCount()">
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <strong><?= htmlspecialchars($p['user_name']) ?></strong>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <?= htmlspecialchars($p['user_email']) ?>
                                    </td>
                                    <?php if (!$event_id): ?>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <?= htmlspecialchars($p['event_title']) ?>
                                        </td>
                                    <?php endif; ?>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <?= date('d M Y H:i', strtotime($p['registered_at'])) ?>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <span class="badge badge-<?= $p['status'] === 'checked_in' ? 'success' : ($p['status'] === 'cancelled' ? 'danger' : 'warning') ?>">
                                            <?= $p['status'] === 'checked_in' ? '✓ Hadir' : ($p['status'] === 'cancelled' ? 'Batal' : 'Terdaftar') ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <?php if ($p['status'] !== 'checked_in'): ?>
                                            <button onclick="markAttendance(<?= $p['id'] ?>)" class="btn btn-sm btn-success" id="btn-<?= $p['id'] ?>">
                                                ✓ Hadir
                                            </button>
                                        <?php else: ?>
                                            <button onclick="unmarkAttendance(<?= $p['id'] ?>)" class="btn btn-sm btn-outline" style="font-size: 11px;">
                                                ✗ Batal Hadir
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center" style="padding: 60px;">
                        <p class="text-muted">Belum ada peserta untuk event ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Select All Toggle
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.participant-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateSelectedCount();
        }

        // Update Selected Count
        function updateSelectedCount() {
            const checked = document.querySelectorAll('.participant-checkbox:checked').length;
            const countEl = document.getElementById('selected-count');
            if (countEl) {
                countEl.textContent = checked + ' selected';
            }

            // Update select-all checkbox state
            const total = document.querySelectorAll('.participant-checkbox').length;
            const selectAll = document.getElementById('select-all');
            if (selectAll) {
                selectAll.checked = (checked === total && total > 0);
                selectAll.indeterminate = (checked > 0 && checked < total);
            }
        }

        // Mark Single Participant as Attended
        function markAttendance(participantId) {
            updateAttendanceStatus(participantId, 'checked_in');
        }

        // Unmark Attendance
        function unmarkAttendance(participantId) {
            if (!confirm('Batalkan kehadiran peserta ini?')) return;
            updateAttendanceStatus(participantId, 'registered');
        }

        // Mark Selected Participants as Attended
        function markSelectedAsAttended() {
            const checked = Array.from(document.querySelectorAll('.participant-checkbox:checked'))
                .map(cb => cb.value);

            if (checked.length === 0) {
                alert('Pilih minimal 1 peserta');
                return;
            }

            if (!confirm(`Tandai ${checked.length} peserta sebagai hadir?`)) return;

            bulkUpdateAttendance(checked, 'checked_in');
        }

        // Mark All Participants as Attended
        function markAllAsAttended() {
            if (!confirm('Tandai SEMUA peserta sebagai hadir?')) return;

            const allIds = Array.from(document.querySelectorAll('.participant-checkbox'))
                .map(cb => cb.value);

            bulkUpdateAttendance(allIds, 'checked_in');
        }

        // Update Attendance Status (API Call)
        function updateAttendanceStatus(participantId, status) {
            const btn = document.getElementById('btn-' + participantId);
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Processing...';
            }

            fetch('api/participants_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=update_status&participant_id=${participantId}&status=${status}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to update status'));
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = '✓ Hadir';
                        }
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = '✓ Hadir';
                    }
                });
        }

        // Bulk Update Attendance
        function bulkUpdateAttendance(participantIds, status) {
            fetch('api/participants_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'bulk_update',
                        participant_ids: participantIds,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`${data.updated_count} peserta berhasil ditandai hadir`);
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to update attendance'));
                    }
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
        }

        // QR Code Scanner (Placeholder)
        function openQRScanner() {
            alert('Fitur QR Scanner akan segera hadir!\n\nUntuk saat ini, gunakan:\n- Tandai Hadir (individual)\n- Tandai Hadir (selected)\n- Tandai Semua Hadir');

            // TODO: Implement QR Scanner
            // You can integrate libraries like:
            // - html5-qrcode
            // - jsQR
            // - QuaggaJS
        }
    </script>
</body>

</html>