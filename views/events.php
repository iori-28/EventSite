<?php


require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

// Get filter parameters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build query
$query = "
    SELECT e.*, u.name as creator_name,
    (SELECT COUNT(*) FROM participants WHERE event_id = e.id) as participant_count
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    WHERE e.status = 'approved'
";

$params = [];

if ($search) {
    $query .= " AND (e.title LIKE :search OR e.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($location) {
    $query .= " AND e.location LIKE :location";
    $params[':location'] = "%$location%";
}

if ($date_filter === 'upcoming') {
    $query .= " AND e.start_at > NOW()";
} elseif ($date_filter === 'today') {
    $query .= " AND DATE(e.start_at) = CURDATE()";
} elseif ($date_filter === 'this_week') {
    $query .= " AND YEARWEEK(e.start_at) = YEARWEEK(NOW())";
}

$query .= " ORDER BY e.start_at ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get unique locations for filter
$locations = $db->query("
    SELECT DISTINCT location 
    FROM events 
    WHERE status = 'approved' AND location IS NOT NULL
    ORDER BY location
")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Event - EventSite</title>
    <link rel="stylesheet" href="css/main.css">
    <style>
        .filter-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-sm);
        }

        .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            padding: 12px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-sm);
            font-size: 14px;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .event-card {
            display: flex;
            gap: 20px;
            background: white;
            padding: 20px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            margin-bottom: 20px;
        }

        .event-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .event-icon {
            width: 120px;
            height: 120px;
            background: var(--primary-gradient);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            flex-shrink: 0;
        }

        .event-info {
            flex: 1;
        }

        .event-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .event-description {
            color: var(--text-light);
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .event-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .event-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .capacity-info {
            font-size: 14px;
            color: var(--text-muted);
        }

        @media (max-width: 768px) {
            .filter-form {
                grid-template-columns: 1fr;
            }

            .event-card {
                flex-direction: column;
            }

            .event-icon {
                width: 100%;
                height: 150px;
            }
        }
    </style>
</head>

<body>
    <?php include 'components/navbar.php'; ?>

    <section class="section">
        <div class="container">
            <div class="section-header">
                <h1 class="section-title">Daftar Event</h1>
                <p class="section-subtitle">Temukan event yang sesuai dengan minat Anda</p>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="search">Cari Event</label>
                        <input
                            type="text"
                            id="search"
                            name="search"
                            placeholder="Cari berdasarkan judul atau deskripsi..."
                            value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="form-group">
                        <label for="location">Lokasi</label>
                        <select id="location" name="location">
                            <option value="">Semua Lokasi</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date">Waktu</label>
                        <select id="date" name="date">
                            <option value="">Semua Waktu</option>
                            <option value="today" <?= $date_filter === 'today' ? 'selected' : '' ?>>Hari Ini</option>
                            <option value="this_week" <?= $date_filter === 'this_week' ? 'selected' : '' ?>>Minggu Ini</option>
                            <option value="upcoming" <?= $date_filter === 'upcoming' ? 'selected' : '' ?>>Akan Datang</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>

            <!-- Events List -->
            <?php if (count($events) > 0): ?>
                <div style="margin-bottom: 20px; color: var(--text-muted);">
                    Menampilkan <?= count($events) ?> event
                </div>

                <?php foreach ($events as $event): ?>
                    <?php
                    $is_full = $event['participant_count'] >= $event['capacity'];
                    $is_past = strtotime($event['start_at']) < time();
                    ?>
                    <div class="event-card">
                        <div class="event-icon">
                            📅
                        </div>

                        <div class="event-info">
                            <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                            <p class="event-description">
                                <?= htmlspecialchars(substr($event['description'], 0, 200)) ?>
                                <?= strlen($event['description']) > 200 ? '...' : '' ?>
                            </p>

                            <div class="event-meta">
                                <div class="event-meta-item">
                                    📍 <?= htmlspecialchars($event['location']) ?>
                                </div>
                                <div class="event-meta-item">
                                    🕒 <?= date('d M Y, H:i', strtotime($event['start_at'])) ?>
                                </div>
                                <div class="event-meta-item">
                                    👥 <?= $event['participant_count'] ?> / <?= $event['capacity'] ?> peserta
                                </div>
                                <div class="event-meta-item">
                                    👤 <?= htmlspecialchars($event['creator_name']) ?>
                                </div>
                            </div>

                            <div class="event-actions">
                                <?php if ($is_past): ?>
                                    <span class="badge badge-danger">Event Selesai</span>
                                <?php elseif ($is_full): ?>
                                    <span class="badge badge-warning">Penuh</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Tersedia</span>
                                <?php endif; ?>

                                <a href="index.php?page=event-detail&id=<?= $event['id'] ?>" class="btn btn-primary btn-sm">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center" style="padding: 60px 20px; background: white; border-radius: 12px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">🔍</div>
                    <h3 style="margin-bottom: 10px;">Tidak Ada Event Ditemukan</h3>
                    <p style="color: var(--text-light); margin-bottom: 20px;">
                        Coba ubah filter pencarian Anda
                    </p>
                    <a href="index.php?page=events" class="btn btn-primary">Reset Filter</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 80px 20px; text-align: center;">
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
            <h2 style="font-size: 42px; margin-bottom: 50px; font-weight: 700; text-align: center; color: var(--text-dark);">Hubungi Kami</h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;">
                <div>
                    <h3 style="margin-bottom: 30px; color: var(--text-dark);">Informasi Kontak</h3>
                    <div style="margin-bottom: 30px;">
                        <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 25px;">
                            <div style="font-size: 24px;">📧</div>
                            <div>
                                <h4 style="margin-bottom: 5px; color: var(--text-dark);">Email</h4>
                                <p style="color: var(--text-muted); font-size: 14px;">support@eventsite.com</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 15px; margin-bottom: 25px;">
                            <div style="font-size: 24px;">📞</div>
                            <div>
                                <h4 style="margin-bottom: 5px; color: var(--text-dark);">Telepon</h4>
                                <p style="color: var(--text-muted); font-size: 14px;">+62 (021) 1234-5678</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: flex-start; gap: 15px;">
                            <div style="font-size: 24px;">📍</div>
                            <div>
                                <h4 style="margin-bottom: 5px; color: var(--text-dark);">Kantor</h4>
                                <p style="color: var(--text-muted); font-size: 14px;">Jl. Teknologi No. 123, Jakarta, Indonesia</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 style="margin-bottom: 30px; color: var(--text-dark);">Kirim Pesan</h3>
                    <form style="display: flex; flex-direction: column; gap: 15px;">
                        <input type="text" placeholder="Nama Anda" style="padding: 12px; border: 2px solid var(--border-color); border-radius: var(--radius-sm); font-size: 14px;" required>
                        <input type="email" placeholder="Email Anda" style="padding: 12px; border: 2px solid var(--border-color); border-radius: var(--radius-sm); font-size: 14px;" required>
                        <textarea placeholder="Pesan Anda" rows="4" style="padding: 12px; border: 2px solid var(--border-color); border-radius: var(--radius-sm); font-size: 14px; font-family: inherit;" required></textarea>
                        <button type="submit" class="btn btn-primary" style="padding: 12px; border: none; cursor: pointer; font-weight: 600;">Kirim Pesan</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>
</body>

</html>