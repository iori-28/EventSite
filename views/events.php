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

    <?php include 'components/footer.php'; ?>
</body>

</html>