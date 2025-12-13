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

// Build query
$query = "
    SELECT e.*, u.name as creator_name,
    (SELECT COUNT(*) FROM participants WHERE event_id = e.id) as participant_count,
    (SELECT COUNT(*) FROM participants WHERE event_id = e.id AND user_id = :user_id) as is_registered
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    WHERE e.status = 'approved' AND e.start_at > NOW()
";

$params = [':user_id' => $user_id];

if ($search) {
    $query .= " AND (e.title LIKE :search OR e.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($location) {
    $query .= " AND e.location LIKE :location";
    $params[':location'] = "%$location%";
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
                <form method="GET" class="d-flex gap-2" style="flex-wrap: wrap;">
                    <input
                        type="text"
                        name="search"
                        placeholder="Cari event..."
                        value="<?= htmlspecialchars($search) ?>"
                        style="flex: 2; min-width: 200px; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                    <select name="location" style="flex: 1; min-width: 150px; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px;">
                        <option value="">Semua Lokasi</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Filter</button>
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
                        <div class="card">
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
                                        <button class="btn btn-outline btn-sm" disabled style="opacity: 0.6; cursor: not-allowed;">
                                            ✅ Terdaftar
                                        </button>
                                    <?php elseif ($is_full): ?>
                                        <button class="btn btn-outline btn-sm" disabled style="opacity: 0.6; cursor: not-allowed; border-color: var(--warning-color); color: var(--warning-color);">
                                            ❌ Penuh
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" action="/EventSite/public/api/participants.php" style="display: inline;">
                                            <input type="hidden" name="action" value="register">
                                            <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                Daftar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
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