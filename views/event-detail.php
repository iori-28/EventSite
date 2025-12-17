<?php


require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

// Get event ID
$event_id = $_GET['id'] ?? null;
$from_source = $_GET['from'] ?? '';

if (!$event_id) {
    header('Location: index.php?page=events');
    exit;
}

// If user came from email reminder and not logged in, redirect to login
if ($from_source === 'email' && !isset($_SESSION['user'])) {
    $return_url = urlencode("index.php?page=event-detail&id={$event_id}&from=email");
    header("Location: index.php?page=login&redirect={$return_url}");
    exit;
}

// Get event details
$stmt = $db->prepare("
    SELECT e.*, u.name as creator_name, u.email as creator_email,
    (SELECT COUNT(*) FROM participants WHERE event_id = e.id) as participant_count
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    WHERE e.id = :id AND e.status = 'approved'
");
$stmt->execute([':id' => $event_id]);
$event = $stmt->fetch();

if (!$event) {
    header('Location: index.php?page=events');
    exit;
}

// Check if user already registered
$is_registered = false;
if (isset($_SESSION['user'])) {
    $stmt = $db->prepare("SELECT id FROM participants WHERE user_id = :user_id AND event_id = :event_id");
    $stmt->execute([
        ':user_id' => $_SESSION['user']['id'],
        ':event_id' => $event_id
    ]);
    $is_registered = $stmt->fetch() !== false;
}

$is_full = $event['participant_count'] >= $event['capacity'];
$is_past = strtotime($event['end_at']) < time();
$is_completed = in_array($event['status'], ['completed', 'waiting_completion']);
$can_register = !$is_full && !$is_past && !$is_registered && isset($_SESSION['user']) && $_SESSION['user']['role'] === 'user';

// Determine back button based on 'from' parameter or default to public events list
$from = $_GET['from'] ?? 'events';
$back_url = 'index.php?page=events';
$back_text = '← Kembali ke Daftar Event';

// Map 'from' parameter to appropriate back URL and text
if ($from === 'admin_manage_events') {
    $back_url = 'index.php?page=admin_manage_events';
    $back_text = '← Kembali ke Kelola Event';
} elseif ($from === 'panitia_my_events') {
    $back_url = 'index.php?page=panitia_my_events';
    $back_text = '← Kembali ke Event Saya';
} elseif ($from === 'user_my_events') {
    $back_url = 'index.php?page=user_my_events';
    $back_text = '← Kembali ke Event Saya';
} elseif ($from === 'user_dashboard') {
    $back_url = 'index.php?page=user_dashboard';
    $back_text = '← Kembali ke Dashboard';
} elseif ($from === 'email') {
    // From email, redirect to user's my events page if logged in
    if (isset($_SESSION['user'])) {
        $role = $_SESSION['user']['role'];
        if ($role === 'user') {
            $back_url = 'index.php?page=user_my_events';
            $back_text = '← Kembali ke Event Saya';
        } elseif ($role === 'panitia') {
            $back_url = 'index.php?page=panitia_my_events';
            $back_text = '← Kembali ke Event Saya';
        } else {
            $back_url = 'index.php?page=admin_dashboard';
            $back_text = '← Kembali ke Dashboard';
        }
    }
} elseif ($from === 'dashboard') {
    // Redirect to appropriate dashboard based on role
    if (isset($_SESSION['user'])) {
        $role = $_SESSION['user']['role'];
        if ($role === 'admin') {
            $back_url = 'index.php?page=admin_dashboard';
            $back_text = '← Kembali ke Dashboard';
        } elseif ($role === 'panitia') {
            $back_url = 'index.php?page=panitia_dashboard';
            $back_text = '← Kembali ke Dashboard';
        } else {
            $back_url = 'index.php?page=user_dashboard';
            $back_text = '← Kembali ke Dashboard';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['title']) ?> - EventSite</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        .event-header {
            background: var(--primary-gradient);
            color: white;
            padding: 60px 0;
        }

        .event-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: -50px;
        }

        .event-main {
            background: white;
            padding: 40px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-lg);
        }

        .event-sidebar {
            position: sticky;
            top: 80px;
            height: fit-content;
        }

        .sidebar-card {
            background: white;
            padding: 30px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            background: rgba(201, 56, 74, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-content h4 {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 5px;
            font-weight: 500;
        }

        .info-content p {
            font-size: 16px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%);
            transition: width 0.3s ease;
        }

        @media (max-width: 768px) {
            .event-content {
                grid-template-columns: 1fr;
            }

            .event-sidebar {
                position: static;
            }
        }
    </style>
</head>

<body>
    <?php include 'components/navbar.php'; ?>

    <!-- Event Header -->
    <div class="event-header">
        <div class="container">
            <div style="margin-bottom: 15px;">
                <a href="<?= $back_url ?>" style="color: white; text-decoration: none; opacity: 0.9; display: inline-flex; align-items: center; gap: 6px; transition: opacity 0.2s;">
                    <?= $back_text ?>
                </a>
            </div>
            <h1 style="font-size: 42px; margin-bottom: 15px;"><?= htmlspecialchars($event['title']) ?></h1>
            <p style="font-size: 18px; opacity: 0.95;">
                Oleh <?= htmlspecialchars($event['creator_name']) ?>
            </p>
        </div>
    </div>

    <!-- Event Content -->
    <section class="section">
        <div class="container">
            <div class="event-content">
                <!-- Main Content -->
                <div class="event-main">
                    <h2 style="margin-bottom: 20px;">Tentang Event</h2>
                    <p style="line-height: 1.8; color: var(--text-light); white-space: pre-line;">
                        <?= htmlspecialchars($event['description']) ?>
                    </p>

                    <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid var(--border-color);">
                        <h3 style="margin-bottom: 20px;">Detail Event</h3>
                        <div class="grid grid-2" style="gap: 20px;">
                            <div>
                                <h4 style="color: var(--text-muted); margin-bottom: 8px; font-size: 14px;">Mulai</h4>
                                <p style="font-size: 16px; font-weight: 600;">
                                    <?= date('l, d F Y', strtotime($event['start_at'])) ?><br>
                                    <span style="color: var(--primary-color);"><?= date('H:i', strtotime($event['start_at'])) ?> WIB</span>
                                </p>
                            </div>
                            <div>
                                <h4 style="color: var(--text-muted); margin-bottom: 8px; font-size: 14px;">Selesai</h4>
                                <p style="font-size: 16px; font-weight: 600;">
                                    <?= date('l, d F Y', strtotime($event['end_at'])) ?><br>
                                    <span style="color: var(--primary-color);"><?= date('H:i', strtotime($event['end_at'])) ?> WIB</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if ($is_completed): ?>
                        <div class="alert alert-success" style="margin-top: 30px; padding: 20px; background: #d4edda; color: #155724; border-radius: 8px;">
                            ✅ Event ini telah selesai
                        </div>
                    <?php elseif ($is_past): ?>
                        <div class="alert alert-warning" style="margin-top: 30px; padding: 20px; background: #fff3cd; color: #856404; border-radius: 8px;">
                            ⏰ Waktu event sudah berakhir (menunggu konfirmasi penyelesaian)
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="event-sidebar">
                    <!-- Registration Card -->
                    <div class="sidebar-card">
                        <div class="info-item">
                            <div class="info-icon">📍</div>
                            <div class="info-content">
                                <h4>Lokasi</h4>
                                <p><?= htmlspecialchars($event['location']) ?></p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">🕒</div>
                            <div class="info-content">
                                <h4>Waktu</h4>
                                <p><?= date('d M Y, H:i', strtotime($event['start_at'])) ?></p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">👥</div>
                            <div class="info-content">
                                <h4>Kapasitas</h4>
                                <p><?= $event['participant_count'] ?> / <?= $event['capacity'] ?> peserta</p>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= ($event['participant_count'] / $event['capacity']) * 100 ?>%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon">👤</div>
                            <div class="info-content">
                                <h4>Penyelenggara</h4>
                                <p><?= htmlspecialchars($event['creator_name']) ?></p>
                            </div>
                        </div>

                        <?php if ($is_registered): ?>
                            <div class="alert alert-success" style="padding: 15px; background: #d4edda; color: #155724; border-radius: 8px; text-align: center; margin-top: 20px;">
                                ✅ Anda sudah terdaftar
                            </div>
                        <?php elseif ($can_register): ?>
                            <form id="registerForm" onsubmit="registerEvent(event)" style="margin-top: 20px;">
                                <input type="hidden" name="action" value="register">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 16px;">
                                    Daftar Sekarang
                                </button>
                            </form>
                        <?php elseif (!isset($_SESSION['user'])): ?>
                            <a href="index.php?page=login&redirect=event-detail&id=<?= $event['id'] ?>" class="btn btn-primary" style="width: 100%; padding: 16px; font-size: 16px; text-align: center; display: block; text-decoration: none; margin-top: 20px;">
                                Login untuk Daftar
                            </a>
                        <?php elseif ($is_full): ?>
                            <div class="alert alert-warning" style="padding: 15px; background: #fff3cd; color: #856404; border-radius: 8px; text-align: center; margin-top: 20px;">
                                ⚠️ Event sudah penuh
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Share Card -->
                    <div class="sidebar-card">
                        <h3 style="margin-bottom: 15px; font-size: 18px;">Bagikan Event</h3>
                        <div class="d-flex gap-2">
                            <button onclick="shareEvent('facebook')" class="btn btn-outline btn-sm" style="flex: 1;">
                                Facebook
                            </button>
                            <button onclick="shareEvent('twitter')" class="btn btn-outline btn-sm" style="flex: 1;">
                                Twitter
                            </button>
                            <button onclick="copyLink()" class="btn btn-outline btn-sm" style="flex: 1;">
                                Copy Link
                            </button>
                        </div>
                    </div>

                    <!-- Add to Calendar Card -->
                    <div class="sidebar-card">
                        <h3 style="margin-bottom: 15px; font-size: 18px;">Tambahkan ke Kalender</h3>
                        <?php
                        require_once 'components/calendar_button.php';
                        renderCalendarButton($event);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>

    <script>
        function registerEvent(e) {
            e.preventDefault();
            if (!confirm('Apakah Anda yakin ingin mendaftar event ini?')) return;

            const form = document.getElementById('registerForm');
            const formData = new FormData(form);
            const btn = form.querySelector('button');
            const originalText = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = 'Memproses...';

            fetch('api/participants.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    const status = data.trim();
                    console.log(status);

                    if (status === 'REGISTER_SUCCESS') {
                        alert('Berhasil mendaftar event! Halaman akan dimuat ulang.');
                        location.reload();
                    } else if (status === 'ALREADY_REGISTERED') {
                        alert('Anda sudah terdaftar di event ini.');
                    } else if (status === 'EVENT_FULL') {
                        alert('Maaf, kuota event sudah penuh.');
                    } else if (status === 'EVENT_NOT_APPROVED') {
                        alert('Event ini belum disetujui.');
                    } else if (status === 'NO_SESSION') {
                        alert('Sesi habis. Silakan login kembali.');
                        window.location.href = 'login.php';
                    } else {
                        alert('Gagal mendaftar. Terjadi kesalahan sistem: ' + status);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Terjadi error koneksi.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                });
        }

        function shareEvent(platform) {
            const url = window.location.href;
            const title = <?= json_encode($event['title']) ?>;

            let shareUrl = '';
            if (platform === 'facebook') {
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
            } else if (platform === 'twitter') {
                shareUrl = `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
            }

            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        }

        function copyLink() {
            navigator.clipboard.writeText(window.location.href).then(() => {
                alert('Link berhasil disalin!');
            });
        }
    </script>
</body>

</html>