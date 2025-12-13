<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'panitia') {
    header('Location: index.php?page=login');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/EventController.php';

$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => $_POST['title'] ?? '',
        'description' => $_POST['description'] ?? '',
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
            $success_msg = 'Event berhasil dibuat dan menunggu persetujuan Admin.';
        } else {
            $error_msg = 'Gagal membuat event. Silakan coba lagi.';
        }
    }
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
                    <form method="POST">
                        <div class="grid grid-2" style="gap: 20px;">
                            <!-- Left Column -->
                            <div>
                                <div class="form-group mb-3">
                                    <label style="display: block; margin-bottom: 8px; font-weight: 500;">Judul Event <span style="color:red">*</span></label>
                                    <input type="text" name="title" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;" placeholder="Contoh: Seminar Teknologi Masa Depan">
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