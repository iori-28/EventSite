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

            <!-- Participants Table -->
            <div class="card">
                <?php if (count($participants) > 0): ?>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa; text-align: left;">
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Nama Peserta</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Email</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Event</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Tanggal Daftar</th>
                                <th style="padding: 15px; border-bottom: 1px solid var(--border-color);">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $p): ?>
                                <tr>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <strong><?= htmlspecialchars($p['user_name']) ?></strong>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <?= htmlspecialchars($p['user_email']) ?>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <?= htmlspecialchars($p['event_title']) ?>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <?= date('d M Y H:i', strtotime($p['registered_at'])) ?>
                                    </td>
                                    <td style="padding: 15px; border-bottom: 1px solid #eee;">
                                        <span class="badge badge-success"><?= ucfirst($p['status']) ?></span>
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
</body>

</html>