<?php


// Redirect logic removed to allow logged-in users to view home
// if (isset($_SESSION['user'])) { ... }

// Get featured events (latest 3 approved events)
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

$featuredEvents = $db->query("
    SELECT e.*, u.name as creator_name, e.event_image
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    WHERE e.status = 'approved' 
    AND e.end_at > NOW()
    ORDER BY e.created_at DESC 
    LIMIT 3
")->fetchAll();

// Get statistics
$stats = [
    'total_events' => $db->query("SELECT COUNT(*) FROM events WHERE status IN ('approved', 'completed', 'waiting_completion')")->fetchColumn(),
    'total_participants' => $db->query("SELECT COUNT(*) FROM participants")->fetchColumn(),
    'active_events' => $db->query("SELECT COUNT(*) FROM events WHERE status = 'approved' AND end_at > NOW()")->fetchColumn(),
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
                        <a href="index.php?page=event-detail&id=<?= $event['id'] ?>&from=home" style="text-decoration: none; color: inherit; display: block;">
                            <div class="card" style="cursor: pointer; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow=''">
                                <?php if (!empty($event['event_image'])): ?>
                                    <div class="card-img" style="background: url('<?= htmlspecialchars($event['event_image']) ?>') center/cover; height: 200px;"></div>
                                <?php else: ?>
                                    <div class="card-img" style="background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; font-weight: bold; height: 200px;">
                                        📅
                                    </div>
                                <?php endif; ?>
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
                                        <span class="btn btn-primary btn-sm">Lihat Detail</span>
                                    </div>
                                </div>
                            </div>
                        </a>
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
    <section class="section" style="background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%); color: white; text-align: center;">
        <div class="container">
            <h2 style="font-size: 36px; margin-bottom: 20px;">Siap Memulai?</h2>
            <p style="font-size: 18px; margin-bottom: 30px; opacity: 0.95;">Bergabunglah dengan ribuan mahasiswa lainnya</p>
            <div class="d-flex justify-between align-center gap-2" style="justify-content: center;">
                <a href="index.php?page=register" class="btn btn-lg" style="background: white; color: var(--primary-color);">Daftar Gratis</a>
                <a href="index.php?page=events" class="btn btn-outline btn-lg" style="border-color: white; color: white;">Lihat Event</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" style="background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%); color: white; padding: 80px 20px; text-align: center;">
        <div style="max-width: 900px; margin: 0 auto;">
            <h2 style="font-size: 42px; margin-bottom: 30px; font-weight: 700;">Tentang EventSite</h2>
            <p style="font-size: 18px; line-height: 1.8; margin-bottom: 20px;">
                EventSite adalah platform manajemen acara terpadu yang dirancang untuk memudahkan pengguna menemukan, membuat, dan mengelola event dengan efisien. Kami berkomitmen untuk menciptakan ekosistem event yang inklusif dan transparan.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; margin-top: 50px;">
                <div>
                    <div style="font-size: 40px; margin-bottom: 10px;">📅</div>
                    <h4 style="margin-bottom: 10px;">Manajemen Mudah</h4>
                    <p style="font-size: 14px; opacity: 0.9;">Kelola event Anda dengan antarmuka yang intuitif dan user-friendly.</p>
                </div>
                <div>
                    <div style="font-size: 40px; margin-bottom: 10px;">🔍</div>
                    <h4 style="margin-bottom: 10px;">Penemuan Event</h4>
                    <p style="font-size: 14px; opacity: 0.9;">Temukan event yang sesuai dengan minat dan jadwal Anda dengan mudah.</p>
                </div>
                <div>
                    <div style="font-size: 40px; margin-bottom: 10px;">📊</div>
                    <h4 style="margin-bottom: 10px;">Analitik Real-time</h4>
                    <p style="font-size: 14px; opacity: 0.9;">Pantau peserta dan performa event dengan dashboard analytics lengkap.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" style="background: #f8f9fa; padding: 80px 20px;">
        <div style="max-width: 900px; margin: 0 auto;">
            <h2 style="font-size: 42px; margin-bottom: 20px; font-weight: 700; text-align: center; color: var(--text-dark);">Hubungi Kami</h2>
            <p style="text-align: center; color: var(--text-muted); margin-bottom: 50px; font-size: 16px;">Punya pertanyaan? Kami siap membantu Anda</p>

            <!-- Contact Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-bottom: 50px;">
                <!-- Email Card -->
                <div class="contact-card" style="background: white; padding: 40px 30px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer;"
                    onclick="copyToClipboard('support@eventsite.com', this)"
                    onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                    <div style="font-size: 48px; margin-bottom: 20px;">📧</div>
                    <h3 style="font-size: 20px; margin-bottom: 10px; color: var(--text-dark);">Email Support</h3>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">Kirim email ke kami</p>
                    <p style="color: var(--primary-color); font-weight: 600; font-size: 14px; word-break: break-all;">support@eventsite.com</p>
                    <div class="copy-hint" style="margin-top: 15px; font-size: 12px; color: var(--text-muted); opacity: 0.7;">Klik untuk copy</div>
                </div>

                <!-- Phone Card -->
                <div class="contact-card" style="background: white; padding: 40px 30px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer;"
                    onclick="copyToClipboard('+62 (021) 1234-5678', this)"
                    onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                    <div style="font-size: 48px; margin-bottom: 20px;">📞</div>
                    <h3 style="font-size: 20px; margin-bottom: 10px; color: var(--text-dark);">Telepon</h3>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">Hubungi kami langsung</p>
                    <p style="color: var(--primary-color); font-weight: 600; font-size: 14px;">+62 00000000000</p>
                    <div class="copy-hint" style="margin-top: 15px; font-size: 12px; color: var(--text-muted); opacity: 0.7;">Klik untuk copy</div>
                </div>

                <!-- GitHub Card -->
                <div class="contact-card" style="background: white; padding: 40px 30px; border-radius: 12px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer;"
                    onclick="window.open('https://github.com/iori-28/EventSite', '_blank')"
                    onmouseover="this.style.transform='translateY(-8px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.12)'"
                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'">
                    <div style="font-size: 48px; margin-bottom: 20px;">💻</div>
                    <h3 style="font-size: 20px; margin-bottom: 10px; color: var(--text-dark);">GitHub</h3>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 15px;">Lihat source code</p>
                    <p style="color: var(--primary-color); font-weight: 600; font-size: 14px;">github.com/iori-28/EventSite</p>
                    <div class="copy-hint" style="margin-top: 15px; font-size: 12px; color: var(--text-muted); opacity: 0.7;">Klik untuk buka</div>
                </div>
            </div>

            <!-- Additional Info -->
            <div style="text-align: center; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 10px;">⏰ Waktu Respon: Maksimal 24 jam</p>
                <p style="color: var(--text-muted); font-size: 14px;">📍 Jl. ABCD No. 123, Pluto</p>
            </div>
        </div>
    </section>

    <script>
        // Copy to clipboard function
        function copyToClipboard(text, element) {
            // Create temporary input
            const tempInput = document.createElement('input');
            tempInput.value = text;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);

            // Show feedback
            const originalHint = element.querySelector('.copy-hint').innerHTML;
            element.querySelector('.copy-hint').innerHTML = '✅ Tersalin!';
            element.querySelector('.copy-hint').style.color = '#28a745';
            element.querySelector('.copy-hint').style.opacity = '1';

            // Reset after 2 seconds
            setTimeout(() => {
                element.querySelector('.copy-hint').innerHTML = originalHint;
                element.querySelector('.copy-hint').style.color = 'var(--text-muted)';
                element.querySelector('.copy-hint').style.opacity = '0.7';
            }, 2000);
        }
    </script>

    <?php include 'components/footer.php'; ?>
</body>

</html>