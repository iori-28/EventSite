<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get filter parameters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$category = $_GET['category'] ?? '';
$organizer_filter = $_GET['organizer'] ?? '';

// Build query
$query = "
    SELECT e.*, u.name as creator_name,
    (SELECT COUNT(*) FROM participants WHERE event_id = e.id) as participant_count,
    (SELECT COUNT(*) FROM participants WHERE event_id = e.id AND user_id = :user_id) as is_registered
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    WHERE e.status = 'approved' AND e.end_at > NOW()
";

$params = [':user_id' => $user_id];

if ($search) {
    $query .= " AND (e.title LIKE :search_title OR e.description LIKE :search_desc)";
    $params[':search_title'] = "%$search%";
    $params[':search_desc'] = "%$search%";
}

if ($location) {
    $query .= " AND e.location LIKE :location";
    $params[':location'] = "%$location%";
}

if ($category) {
    $query .= " AND e.category = :category";
    $params[':category'] = $category;
}

if ($organizer_filter) {
    $query .= " AND e.created_by = :organizer";
    $params[':organizer'] = $organizer_filter;
}

$query .= " ORDER BY e.start_at ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get unique locations for filter
$locations = $db->query("
    SELECT DISTINCT location 
    FROM events 
    WHERE status = 'approved' AND location IS NOT NULL AND end_at > NOW()
    ORDER BY location
")->fetchAll(PDO::FETCH_COLUMN);

// Get categories for filter
$categories = $db->query("
    SELECT DISTINCT category 
    FROM events 
    WHERE status = 'approved' AND category IS NOT NULL AND end_at > NOW()
    ORDER BY category
")->fetchAll(PDO::FETCH_COLUMN);

// Get organizers (panitia) for filter
$organizers = $db->query("
    SELECT DISTINCT u.id, u.name 
    FROM users u
    INNER JOIN events e ON e.created_by = u.id
    WHERE u.role = 'panitia' AND e.status = 'approved' AND e.end_at > NOW()
    ORDER BY u.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jelajahi Event - EventSite</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- LOGOUT BUTTON -->

            <header class="dashboard-header">
                <div class="header-title">
                    <button class="sidebar-toggle" onclick="toggleSidebar()" style="display:none; background:none; border:none; font-size:24px; cursor:pointer; margin-right:10px;">☰</button>
                    <h1>Jelajahi Event</h1>
                    <div class="header-breadcrumb">Temukan event menarik untuk diikuti</div>
                </div>
            </header>

            <!-- Filter Section -->
            <div class="card mb-4" style="padding: 20px;">
                <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                    <input type="hidden" name="page" value="user_browse_events">
                    <div style="display: flex; flex-direction: column;">
                        <label style="margin-bottom: 8px; font-weight: 600; color: var(--text-dark); font-size: 14px;">Cari Event</label>
                        <input
                            type="text"
                            name="search"
                            placeholder="Cari berdasarkan judul atau deskripsi..."
                            value="<?= htmlspecialchars($search) ?>"
                            style="padding: 10px 14px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <label style="margin-bottom: 8px; font-weight: 600; color: var(--text-dark); font-size: 14px;">Lokasi</label>
                        <select name="location" style="padding: 10px 14px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                            <option value="">Semua Lokasi</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?= htmlspecialchars($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <label style="margin-bottom: 8px; font-weight: 600; color: var(--text-dark); font-size: 14px;">Kategori</label>
                        <select name="category" style="padding: 10px 14px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: flex; flex-direction: column;">
                        <label style="margin-bottom: 8px; font-weight: 600; color: var(--text-dark); font-size: 14px;">Panitia</label>
                        <select name="organizer" style="padding: 10px 14px; border: 2px solid var(--border-color); border-radius: 8px; font-size: 14px;">
                            <option value="">Semua Panitia</option>
                            <?php foreach ($organizers as $org): ?>
                                <option value="<?= $org['id'] ?>" <?= $organizer_filter == $org['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($org['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="height: 42px;">Filter</button>
                </form>
            </div>

            <!-- Events Grid -->
            <?php if (count($events) > 0): ?>
                <div class="grid grid-3">
                    <?php foreach ($events as $event): ?>
                        <?php
                        $is_full = $event['participant_count'] >= $event['capacity'];
                        $is_registered = $event['is_registered'] > 0;
                        ?>
                        <a href="index.php?page=event-detail&id=<?= $event['id'] ?>&from=user_browse_events" style="text-decoration: none; color: inherit; display: block; transition: transform 0.2s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                            <div class="card" style="height: 100%; cursor: pointer;">
                                <div class="card-img" style="background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">
                                    📅
                                </div>
                                <div class="card-body">
                                    <h3 class="card-title" style="font-size: 18px;"><?= htmlspecialchars($event['title']) ?></h3>
                                    <p class="card-text" style="font-size: 14px; margin-bottom: 10px;">
                                        <?= date('d M Y, H:i', strtotime($event['start_at'])) ?>
                                    </p>
                                    <p class="card-text" style="font-size: 14px;">
                                        📍 <?= htmlspecialchars($event['location']) ?>
                                    </p>

                                    <div class="d-flex justify-between align-center mt-3">
                                        <span class="text-muted" style="font-size: 12px;">
                                            <?= $event['participant_count'] ?>/<?= $event['capacity'] ?> Peserta
                                        </span>

                                        <?php if ($is_registered): ?>
                                            <span class="btn btn-outline btn-sm" style="opacity: 0.8; cursor: pointer;">
                                                ✅ Terdaftar
                                            </span>
                                        <?php elseif ($is_full): ?>
                                            <span class="btn btn-outline btn-sm" style="opacity: 0.8; border-color: var(--warning-color); color: var(--warning-color); cursor: pointer;">
                                                ❌ Penuh
                                            </span>
                                        <?php else: ?>
                                            <span class="btn btn-primary btn-sm" style="cursor: pointer;">
                                                📖 Lihat Detail
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center" style="padding: 60px;">
                    <p class="text-muted">Tidak ada event ditemukan.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function registerEvent(eventId) {
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('event_id', eventId);

            fetch('../api/participants.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    const status = data.trim();
                    if (status === 'REGISTER_SUCCESS') {
                        alert('Berhasil mendaftar event!');
                        location.reload();
                    } else if (status === 'EVENT_FULL') {
                        alert('Maaf, kuota event sudah penuh.');
                    } else if (status === 'ALREADY_REGISTERED') {
                        alert('Anda sudah terdaftar di event ini.');
                    } else if (status === 'EVENT_NOT_APPROVED') {
                        alert('Event ini belum disetujui.');
                    } else if (status === 'NO_SESSION') {
                        alert('Sesi habis. Silakan login kembali.');
                        window.location.href = '../login.php';
                    } else {
                        alert('Gagal mendaftar: ' + status);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat mendaftar.');
                });
        }
    </script>
</body>

</html>