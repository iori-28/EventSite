<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

// Get event ID
$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    header('Location: index.php?page=admin_manage_events&error=missing_id');
    exit;
}

// Get event details
$stmt = $db->prepare("
    SELECT e.*, u.name as creator_name 
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: index.php?page=admin_manage_events&error=not_found');
    exit;
}

$success_msg = '';
$error_msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $location = $_POST['location'] ?? '';
    $start_at = $_POST['start_at'] ?? '';
    $end_at = $_POST['end_at'] ?? '';
    $capacity = $_POST['capacity'] ?? 0;
    $status = $_POST['status'] ?? 'pending';

    // Basic validation
    if (empty($title) || empty($start_at) || empty($capacity)) {
        $error_msg = 'Mohon lengkapi semua field yang wajib diisi.';
    } else {
        // Update event
        $stmt = $db->prepare("
            UPDATE events 
            SET title = ?, description = ?, location = ?, start_at = ?, end_at = ?, capacity = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");

        if ($stmt->execute([$title, $description, $location, $start_at, $end_at, $capacity, $status, $event_id])) {
            $success_msg = 'Event berhasil diupdate!';
            // Refresh event data
            $stmt = $db->prepare("
                SELECT e.*, u.name as creator_name 
                FROM events e 
                LEFT JOIN users u ON e.created_by = u.id 
                WHERE e.id = ?
            ");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error_msg = 'Gagal mengupdate event. Silakan coba lagi.';
        }
    }
}

// Get participant count
$stmt = $db->prepare("SELECT COUNT(*) FROM participants WHERE event_id = ?");
$stmt->execute([$event_id]);
$participant_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Admin - EventSite</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include 'components/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <?php
            $page_title = 'Edit Event (Admin)';
            $breadcrumb = 'Edit dan kelola event';
            include 'components/dashboard_header.php';
            ?>

            <!-- Alert Messages -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?= $success_msg ?></div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><?= $error_msg ?></div>
            <?php endif; ?>

            <!-- Event Info -->
            <div class="alert alert-info" style="margin-bottom: 30px;">
                <strong>👤 Dibuat oleh:</strong> <?= htmlspecialchars($event['creator_name'] ?? 'Unknown') ?><br>
                <strong>📅 Dibuat:</strong> <?= date('d M Y H:i', strtotime($event['created_at'])) ?><br>
                <strong>👥 Total Peserta:</strong> <?= $participant_count ?> orang
            </div>

            <!-- Event Form -->
            <div class="card">
                <div class="card-header">
                    <h3>Informasi Event</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Judul Event *</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Lokasi</label>
                            <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($event['location'] ?? '') ?>" placeholder="Contoh: Gedung A Lt. 3 atau Online (Zoom)">
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label>Tanggal & Waktu Mulai *</label>
                                <input type="datetime-local" name="start_at" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['start_at'])) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Tanggal & Waktu Selesai</label>
                                <input type="datetime-local" name="end_at" class="form-control" value="<?= $event['end_at'] ? date('Y-m-d\TH:i', strtotime($event['end_at'])) : '' ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Kapasitas Peserta *</label>
                            <input type="number" name="capacity" class="form-control" value="<?= $event['capacity'] ?>" min="<?= $participant_count ?>" required>
                            <small class="text-muted">
                                Minimal kapasitas: <?= $participant_count ?> (jumlah peserta saat ini)
                            </small>
                        </div>

                        <!-- Status Selection (Admin Only) -->
                        <div class="form-group">
                            <label>Status Event *</label>
                            <select name="status" class="form-control" required>
                                <option value="draft" <?= $event['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="pending" <?= $event['status'] === 'pending' ? 'selected' : '' ?>>Pending (Menunggu Approval)</option>
                                <option value="approved" <?= $event['status'] === 'approved' ? 'selected' : '' ?>>Approved (Disetujui)</option>
                                <option value="rejected" <?= $event['status'] === 'rejected' ? 'selected' : '' ?>>Rejected (Ditolak)</option>
                                <option value="cancelled" <?= $event['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled (Dibatalkan)</option>
                            </select>
                            <small class="text-muted">Hanya event dengan status "Approved" yang dapat dilihat oleh user.</small>
                        </div>

                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                            <a href="index.php?page=admin_manage_events" class="btn btn-outline">← Kembali</a>
                            <a href="index.php?page=admin_confirm_attendance&event_id=<?= $event_id ?>" class="btn btn-success" style="margin-left: auto;">
                                ✅ Kelola Kehadiran
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>