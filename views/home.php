<?php


// Redirect logic removed to allow logged-in users to view home
// if (isset($_SESSION['user'])) { ... }

// Get featured events (latest 3 approved events)
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

$featuredEvents = $db->query("
    SELECT e.*, u.name as creator_name 
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    WHERE e.status = 'approved' 
    AND e.start_at > NOW()
    ORDER BY e.created_at DESC 
    LIMIT 3
")->fetchAll();

// Get statistics
$stats = [
    'total_events' => $db->query("SELECT COUNT(*) FROM events WHERE status = 'approved'")->fetchColumn(),
    'total_participants' => $db->query("SELECT COUNT(*) FROM participants")->fetchColumn(),
    'active_events' => $db->query("SELECT COUNT(*) FROM events WHERE status = 'approved' AND start_at > NOW()")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventSite - Platform Event Mahasiswa</title>
    <link rel="stylesheet" href="css/main.css">
</head>

<body>
    <?php include 'components/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Platform Event Mahasiswa Terbaik</h1>
            <p>Temukan dan daftar event menarik di kampus Anda. Kelola event dengan mudah dan profesional.</p>
            <div class="hero-actions">
                <a href="index.php?page=events" class="btn btn-lg">Jelajahi Event</a>
                <a href="index.php?page=register" class="btn btn-outline btn-lg">Daftar Sekarang</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="section" style="background: white;">
        <div class="container">
            <div class="grid grid-3">
                <div class="text-center">
                    <div style="font-size: 48px; font-weight: 800; color: var(--primary-color); margin-bottom: 10px;">
                        <?= $stats['total_events'] ?>+
                    </div>
                    <div style="font-size: 18px; color: var(--text-light);">Total Event</div>
                </div>
                <div class="text-center">
                    <div style="font-size: 48px; font-weight: 800; color: var(--primary-color); margin-bottom: 10px;">
                        <?= $stats['total_participants'] ?>+
                    </div>
                    <div style="font-size: 18px; color: var(--text-light);">Peserta Terdaftar</div>
                </div>
                <div class="text-center">
                    <div style="font-size: 48px; font-weight: 800; color: var(--primary-color); margin-bottom: 10px;">
                        <?= $stats['active_events'] ?>
                    </div>
                    <div style="font-size: 18px; color: var(--text-light);">Event Aktif</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Events -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Event Terbaru</h2>
                <p class="section-subtitle">Jangan lewatkan event-event menarik yang akan datang</p>
            </div>

            <?php if (count($featuredEvents) > 0): ?>
                <div class="grid grid-3">
                    <?php foreach ($featuredEvents as $event): ?>
                        <div class="card">
                            <div class="card-img" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; font-weight: bold;">
                                📅
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?= htmlspecialchars($event['title']) ?></h3>
                                <p class="card-text"><?= htmlspecialchars(substr($event['description'], 0, 100)) ?>...</p>

                                <div class="card-meta">
                                    <div class="card-meta-item">
                                        📍 <?= htmlspecialchars($event['location']) ?>
                                    </div>
                                    <div class="card-meta-item">
                                        🕒 <?= date('d M Y', strtotime($event['start_at'])) ?>
                                    </div>
                                </div>

                                <div class="d-flex justify-between align-center">
                                    <span class="badge badge-success">Available</span>
                                    <a href="index.php?page=event-detail&id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-5">
                    <a href="index.php?page=events" class="btn btn-outline btn-lg">Lihat Semua Event</a>
                </div>
            <?php else: ?>
                <div class="text-center" style="padding: 60px 20px; background: white; border-radius: 12px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">📅</div>
                    <h3 style="margin-bottom: 10px;">Belum Ada Event</h3>
                    <p style="color: var(--text-light);">Event akan segera hadir. Pantau terus halaman ini!</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section" style="background: white;">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Kenapa EventSite?</h2>
                <p class="section-subtitle">Platform terlengkap untuk manajemen event mahasiswa</p>
            </div>

            <div class="grid grid-3">
                <div class="text-center" style="padding: 30px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">🎯</div>
                    <h3 style="margin-bottom: 15px;">Mudah Digunakan</h3>
                    <p style="color: var(--text-light);">Interface yang intuitif dan user-friendly untuk semua kalangan</p>
                </div>
                <div class="text-center" style="padding: 30px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">⚡</div>
                    <h3 style="margin-bottom: 15px;">Cepat & Real-time</h3>
                    <p style="color: var(--text-light);">Notifikasi instant dan update real-time untuk setiap event</p>
                </div>
                <div class="text-center" style="padding: 30px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">🔒</div>
                    <h3 style="margin-bottom: 15px;">Aman & Terpercaya</h3>
                    <p style="color: var(--text-light);">Data Anda aman dengan sistem keamanan terbaik</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section" style="background: var(--primary-gradient); color: white; text-align: center;">
        <div class="container">
            <h2 style="font-size: 36px; margin-bottom: 20px;">Siap Memulai?</h2>
            <p style="font-size: 18px; margin-bottom: 30px; opacity: 0.95;">Bergabunglah dengan ribuan mahasiswa lainnya</p>
            <div class="d-flex justify-between align-center gap-2" style="justify-content: center;">
                <a href="index.php?page=register" class="btn btn-lg" style="background: white; color: var(--primary-color);">Daftar Gratis</a>
                <a href="index.php?page=events" class="btn btn-outline btn-lg" style="border-color: white; color: white;">Lihat Event</a>
            </div>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>
</body>

</html>