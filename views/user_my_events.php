<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('user');

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get my events
$query = "
    SELECT e.*, p.status as registration_status, p.registered_at, p.qr_token, p.id as participant_id
    FROM participants p
    JOIN events e ON p.event_id = e.id 
    WHERE p.user_id = :user_id
    ORDER BY e.start_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute([':user_id' => $user_id]);
$all_events = $stmt->fetchAll();

$upcoming = [];
$past = [];

foreach ($all_events as $event) {
    if (strtotime($event['end_at']) > time()) {
        $upcoming[] = $event;
    } else {
        $past[] = $event;
    }
}
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
                    <h1>Event Saya</h1>
                    <div class="header-breadcrumb">Daftar event yang Anda ikuti</div>
                </div>
            </header>

            <!-- Upcoming Events -->
            <section class="mb-5">
                <h2 style="font-size: 20px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">Akan Datang</h2>

                <?php if (count($upcoming) > 0): ?>
                    <div class="grid grid-2">
                        <?php foreach ($upcoming as $event): ?>
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-between mb-3">
                                        <span class="badge badge-info"><?= ucfirst($event['registration_status']) ?></span>
                                        <span class="text-muted" style="font-size: 12px;">Ref: #<?= $event['id'] ?></span>
                                    </div>

                                    <h3 class="card-title"><?= htmlspecialchars($event['title']) ?></h3>

                                    <div class="event-meta">
                                        <div class="event-meta-item">
                                            📅 <?= date('d M Y', strtotime($event['start_at'])) ?>
                                        </div>
                                        <div class="event-meta-item">
                                            🕒 <?= date('H:i', strtotime($event['start_at'])) ?>
                                        </div>
                                    </div>

                                    <p class="text-muted mb-3" style="font-size: 14px;">📍 <?= htmlspecialchars($event['location']) ?></p>

                                    <div class="d-flex gap-2">
                                        <a href="index.php?page=event-detail&id=<?= $event['id'] ?>&from=user_my_events" class="btn btn-primary btn-sm" style="flex: 1;">Detail</a>
                                        <button onclick="showQRCode('<?= $event['qr_token'] ?>', '<?= htmlspecialchars($event['title']) ?>')" class="btn btn-secondary btn-sm" style="flex: 1;">
                                            📱 QR Code
                                        </button>
                                        <button onclick="cancelRegistration(<?= $event['id'] ?>)" class="btn btn-outline btn-sm" style="flex: 1; border-color: var(--danger-color); color: var(--danger-color);">Batalkan</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card p-4 text-center">
                        <p class="text-muted">Tidak ada event event yang akan datang.</p>
                        <a href="index.php?page=user_browse_events" class="btn btn-primary btn-sm mt-2">Cari Event</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Past Events -->
            <section>
                <h2 style="font-size: 20px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color);">Riwayat Event</h2>

                <?php if (count($past) > 0): ?>
                    <div class="card">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; text-align: left;">
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Event</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Tanggal</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Lokasi</th>
                                    <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($past as $event): ?>
                                    <tr>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <strong><?= htmlspecialchars($event['title']) ?></strong>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <?= date('d M Y', strtotime($event['start_at'])) ?>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <?= htmlspecialchars($event['location']) ?>
                                        </td>
                                        <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                            <span class="badge badge-secondary" style="background: #eee; color: #666;">Selesai</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Belum ada riwayat event.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- QR Code Modal -->
    <div id="qr-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:8px; max-width:400px; width:90%; text-align:center;">
            <h3 style="margin-bottom:20px; color:#1a1a1a;">QR Code Kehadiran</h3>
            <p style="margin-bottom:15px; font-size:14px; color:#666;" id="qr-event-title"></p>
            <div id="qr-code" style="margin:20px auto; display:flex; justify-content:center;"></div>
            <p style="margin-top:15px; font-size:13px; color:#999;">Tunjukkan QR code ini kepada panitia untuk konfirmasi kehadiran</p>
            <button onclick="closeQRModal()" class="btn btn-primary" style="margin-top:20px;">Tutup</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        function showQRCode(token, eventTitle) {
            const modal = document.getElementById('qr-modal');
            const qrContainer = document.getElementById('qr-code');
            const titleEl = document.getElementById('qr-event-title');

            // Clear previous QR
            qrContainer.innerHTML = '';

            // Set title
            titleEl.textContent = eventTitle;

            // Generate QR Code
            new QRCode(qrContainer, {
                text: token,
                width: 256,
                height: 256,
                colorDark: "#1a1a1a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            // Show modal
            modal.style.display = 'flex';
        }

        function closeQRModal() {
            document.getElementById('qr-modal').style.display = 'none';
        }

        function cancelRegistration(eventId) {
            if (!confirm('Apakah Anda yakin ingin membatalkan pendaftaran event ini?')) return;

            const formData = new FormData();
            formData.append('action', 'cancel');
            formData.append('event_id', eventId);

            fetch('../api/participants.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    const status = data.trim();
                    if (status === 'CANCEL_SUCCESS') {
                        alert('Pendaftaran berhasil dibatalkan.');
                        location.reload();
                    } else {
                        alert('Gagal membatalkan pendaftaran: ' + status);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi kesalahan koneksi.');
                });
        }
    </script>
</body>

</html>