<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('panitia');

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get event ID
$event_id = $_GET['id'] ?? null;

if (!$event_id) {
    header('Location: index.php?page=panitia_my_events&error=missing_id');
    exit;
}

// Get event details
$stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND created_by = ?");
$stmt->execute([$event_id, $user_id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header('Location: index.php?page=panitia_my_events&error=not_found');
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

    // Basic validation
    if (empty($title) || empty($start_at) || empty($capacity)) {
        $error_msg = 'Mohon lengkapi semua field yang wajib diisi.';
    } else {
        // Update event
        $stmt = $db->prepare("
            UPDATE events 
            SET title = ?, description = ?, location = ?, start_at = ?, end_at = ?, capacity = ?, updated_at = NOW()
            WHERE id = ? AND created_by = ?
        ");

        if ($stmt->execute([$title, $description, $location, $start_at, $end_at, $capacity, $event_id, $user_id])) {
            $success_msg = 'Event berhasil diupdate!';
            // Refresh event data
            $stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error_msg = 'Gagal mengupdate event. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - EventSite</title>
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
            $page_title = 'Edit Event';
            $breadcrumb = 'Edit informasi event Anda';
            include 'components/dashboard_header.php';
            ?>

            <!-- Alert Messages -->
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?= $success_msg ?></div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-danger"><?= $error_msg ?></div>
            <?php endif; ?>

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
                            <input type="number" name="capacity" class="form-control" value="<?= $event['capacity'] ?>" min="1" required>
                            <small class="text-muted">Jumlah maksimal peserta yang dapat mendaftar</small>
                        </div>

                        <!-- Event Status Info -->
                        <div class="alert alert-info">
                            <strong>Status Event:</strong>
                            <span class="badge badge-<?= $event['status'] === 'approved' ? 'success' : ($event['status'] === 'rejected' ? 'danger' : 'warning') ?>">
                                <?= ucfirst($event['status']) ?>
                            </span>
                            <br>
                            <?php if ($event['status'] === 'pending'): ?>
                                <small>Event Anda sedang menunggu persetujuan dari Admin.</small>
                            <?php elseif ($event['status'] === 'approved'): ?>
                                <small>Event Anda telah disetujui dan dapat dilihat oleh pengguna.</small>
                            <?php elseif ($event['status'] === 'rejected'): ?>
                                <small>Event Anda ditolak. Silakan perbaiki dan hubungi admin.</small>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">💾 Simpan Perubahan</button>
                            <a href="index.php?page=panitia_my_events" class="btn btn-outline">← Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>