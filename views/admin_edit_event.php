<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('admin');

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

// Check for success message from redirect (PRG pattern)
if (isset($_GET['updated'])) {
    $success_msg = 'Event berhasil diupdate!';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? 'Lainnya';
    $location = $_POST['location'] ?? '';
    $start_at = $_POST['start_at'] ?? '';
    $end_at = $_POST['end_at'] ?? '';
    $capacity = $_POST['capacity'] ?? 0;
    $status = $_POST['status'] ?? 'pending';
    $delete_image = isset($_POST['delete_image']) ? true : false;

    // Basic validation
    if (empty($title) || empty($start_at) || empty($capacity)) {
        $error_msg = 'Mohon lengkapi semua field yang wajib diisi.';
    } else {
        // Handle image upload
        $uploaded_image = $event['event_image']; // Keep existing image by default

        if ($delete_image) {
            // Delete old image file if exists
            if ($event['event_image']) {
                $old_file = $_SERVER['DOCUMENT_ROOT'] . '/EventSite/public/' . $event['event_image'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $uploaded_image = null;
        } elseif (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['event_image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB

            // Validate file type
            if (!in_array($file['type'], $allowed_types)) {
                $error_msg = 'Format file tidak valid. Gunakan JPG, PNG, atau GIF.';
            }
            // Validate file size
            elseif ($file['size'] > $max_size) {
                $error_msg = 'Ukuran file terlalu besar. Maksimal 2MB.';
            } else {
                // Delete old image if exists
                if ($event['event_image']) {
                    $old_file = $_SERVER['DOCUMENT_ROOT'] . '/EventSite/public/' . $event['event_image'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }

                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'event_' . time() . '_' . uniqid() . '.' . $extension;
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/EventSite/public/uploads/events/';

                // Create directory if not exists
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                    $uploaded_image = 'uploads/events/' . $filename;
                } else {
                    $error_msg = 'Gagal mengupload file.';
                }
            }
        }

        // Only update if no upload errors
        if (empty($error_msg)) {
            // Update event
            $stmt = $db->prepare("
                UPDATE events 
                SET title = ?, description = ?, category = ?, location = ?, start_at = ?, end_at = ?, capacity = ?, status = ?, event_image = ?, updated_at = NOW()
                WHERE id = ?
            ");

            if ($stmt->execute([$title, $description, $category, $location, $start_at, $end_at, $capacity, $status, $uploaded_image, $event_id])) {
                // PRG Pattern: Redirect after successful POST to prevent duplicate submission on refresh
                header('Location: index.php?page=admin_edit_event&id=' . $event_id . '&updated=1');
                exit;
            } else {
                $error_msg = 'Gagal mengupdate event. Silakan coba lagi.';
            }
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
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Judul Event *</label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Kategori Event *</label>
                            <select name="category" class="form-control" required>
                                <option value="Seminar" <?= ($event['category'] ?? '') == 'Seminar' ? 'selected' : '' ?>>📚 Seminar</option>
                                <option value="Workshop" <?= ($event['category'] ?? '') == 'Workshop' ? 'selected' : '' ?>>🛠️ Workshop</option>
                                <option value="Webinar" <?= ($event['category'] ?? '') == 'Webinar' ? 'selected' : '' ?>>💻 Webinar</option>
                                <option value="Kompetisi" <?= ($event['category'] ?? '') == 'Kompetisi' ? 'selected' : '' ?>>🏆 Kompetisi</option>
                                <option value="Pelatihan" <?= ($event['category'] ?? '') == 'Pelatihan' ? 'selected' : '' ?>>📖 Pelatihan</option>
                                <option value="Sosialisasi" <?= ($event['category'] ?? '') == 'Sosialisasi' ? 'selected' : '' ?>>📢 Sosialisasi</option>
                                <option value="Expo" <?= ($event['category'] ?? '') == 'Expo' ? 'selected' : '' ?>>🎪 Expo</option>
                                <option value="Musik" <?= ($event['category'] ?? '') == 'Musik' ? 'selected' : '' ?>>🎵 Musik</option>
                                <option value="Olahraga" <?= ($event['category'] ?? '') == 'Olahraga' ? 'selected' : '' ?>>🏃 Olahraga</option>
                                <option value="Festival" <?= ($event['category'] ?? '') == 'Festival' ? 'selected' : '' ?>>🎭 Festival</option>
                                <option value="Bakti Sosial" <?= ($event['category'] ?? '') == 'Bakti Sosial' ? 'selected' : '' ?>>🤝 Bakti Sosial</option>
                                <option value="Lainnya" <?= ($event['category'] ?? 'Lainnya') == 'Lainnya' ? 'selected' : '' ?>>📋 Lainnya</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Deskripsi</label>
                            <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Event Image Upload -->
                        <div class="form-group">
                            <label>Foto/Banner Event</label>

                            <?php if (!empty($event['event_image'])): ?>
                                <div style="margin-bottom: 15px;">
                                    <img src="<?= htmlspecialchars($event['event_image']) ?>" alt="Event Image" style="max-width: 100%; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                    <div style="margin-top: 10px;">
                                        <label style="display: inline-flex; align-items: center; cursor: pointer;">
                                            <input type="checkbox" name="delete_image" value="1" style="margin-right: 8px;">
                                            <span style="color: #dc3545; font-weight: 500;">🗑️ Hapus gambar ini</span>
                                        </label>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="padding: 20px; background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; text-align: center; margin-bottom: 15px;">
                                    <span style="font-size: 48px;">📷</span>
                                    <p style="color: #6c757d; margin: 10px 0 0 0;">Belum ada gambar</p>
                                </div>
                            <?php endif; ?>

                            <input type="file" name="event_image" accept="image/*" class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;" onchange="previewImage(event)">
                            <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB. <?= !empty($event['event_image']) ? 'Upload file baru untuk mengganti gambar.' : '' ?></small>

                            <div id="imagePreview" style="margin-top: 15px; display: none;">
                                <p style="font-weight: 500; color: var(--primary-color);">Preview:</p>
                                <img id="preview" style="max-width: 100%; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </div>
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

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview');
                    const previewContainer = document.getElementById('imagePreview');
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>

</html>