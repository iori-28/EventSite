<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('panitia');

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/EventController.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    $uploaded_image = null;
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
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

    // Only proceed if no upload errors
    if (empty($error_msg)) {
        $data = [
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'event_image' => $uploaded_image,
            'location' => $_POST['location'] ?? '',
            'start_at' => $_POST['start_at'] ?? '',
            'end_at' => $_POST['end_at'] ?? '',
            'capacity' => $_POST['capacity'] ?? 0,
            'status' => 'pending', // Default status for new event
            'created_by' => $_SESSION['user']['id']
        ];

        // Basic validation
        if (empty($data['title']) || empty($data['start_at']) || empty($data['capacity'])) {
            $error_msg = 'Mohon lengkapi semua field yang wajib diisi.';
        } else {
            if (EventController::create($data)) {
                // PRG Pattern: Redirect after successful POST to prevent duplicate submission
                $_SESSION['flash_success'] = 'Event berhasil dibuat dan menunggu persetujuan Admin.';
                header('Location: index.php?page=panitia_my_events');
                exit;
            } else {
                $error_msg = 'Gagal membuat event. Silakan coba lagi.';
            }
        }
    }
}

// Display flash message if exists (from redirect)
if (isset($_SESSION['flash_success'])) {
    $success_msg = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $error_msg = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Event Baru - EventSite</title>
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
                    <h1>Buat Event Baru</h1>
                    <div class="header-breadcrumb">Isi detail event Anda</div>
                </div>
            </header>

            <?php if ($success_msg): ?>
                <div class="alert alert-success mb-4" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px;">
                    <?= $success_msg ?>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="alert alert-error mb-4" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px;">
                    <?= $error_msg ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body" style="padding: 30px;">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="grid grid-2" style="gap: 20px;">
                            <!-- Left Column -->
                            <div>
                                <div class="form-group mb-3">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Judul Event <span style="color:red">*</span></label>
                                    <input type="text" name="title" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;" placeholder="Contoh: Seminar Teknologi Masa Depan">
                                </div>

                                <div class="form-group mb-3">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Foto/Banner Event</label>
                                    <input type="file" name="event_image" accept="image/*" class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;" onchange="previewImage(event)">
                                    <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">Format: JPG, PNG, GIF. Max: 2MB</small>
                                    <div id="image-preview" style="margin-top: 10px; display: none;">
                                        <img id="preview-img" src="" alt="Preview" style="max-width: 100%; max-height: 200px; border-radius: 8px; border: 2px solid var(--border-color);">
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Lokasi</label>
                                    <input type="text" name="location" class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;" placeholder="Gedung Serbaguna Lt. 2">
                                </div>

                                <div class="grid grid-2" style="gap: 15px;">
                                    <div class="form-group mb-3">
                                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Waktu Mulai <span style="color:red">*</span></label>
                                        <input type="datetime-local" name="start_at" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label style="display: block; margin-bottom: 8px; font-weight: 500;">Waktu Selesai</label>
                                        <input type="datetime-local" name="end_at" class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div>
                                <div class="form-group mb-3">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Kapasitas Peserta <span style="color:red">*</span></label>
                                    <input type="number" name="capacity" required min="1" class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;" placeholder="100">
                                </div>

                                <div class="form-group mb-3">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Deskripsi Event</label>
                                    <textarea name="description" rows="8" class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;" placeholder="Jelaskan detail event Anda..."></textarea>
                                </div>
                            </div>
                        </div>


                        <script>
                            function previewImage(event) {
                                const file = event.target.files[0];
                                const preview = document.getElementById('image-preview');
                                const previewImg = document.getElementById('preview-img');

                                if (file) {
                                    // Check file size (2MB)
                                    if (file.size > 2 * 1024 * 1024) {
                                        alert('Ukuran file terlalu besar! Maksimal 2MB');
                                        event.target.value = '';
                                        preview.style.display = 'none';
                                        return;
                                    }

                                    // Check file type
                                    if (!file.type.startsWith('image/')) {
                                        alert('File harus berupa gambar!');
                                        event.target.value = '';
                                        preview.style.display = 'none';
                                        return;
                                    }

                                    const reader = new FileReader();
                                    reader.onload = function(e) {
                                        previewImg.src = e.target.result;
                                        preview.style.display = 'block';
                                    };
                                    reader.readAsDataURL(file);
                                } else {
                                    preview.style.display = 'none';
                                }
                            }
                        </script>
                        <div style="margin-top: 20px; text-align: right;">
                            <a href="index.php?page=panitia_dashboard" class="btn btn-outline" style="margin-right: 10px;">Batal</a>
                            <button type="submit" class="btn btn-primary">Buat Event</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>