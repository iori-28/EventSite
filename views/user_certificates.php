<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('user');

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/CertificateController.php';
$user_id = $_SESSION['user']['id'];

// Get all certificates for this user
$certificates = CertificateController::getByUser($user_id);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Saya - EventSite</title>
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
            $page_title = 'Sertifikat Saya';
            $breadcrumb = 'Lihat dan download sertifikat event yang telah Anda ikuti';
            include 'components/dashboard_header.php';
            ?>

            <!-- Certificates Grid -->
            <?php if (!empty($certificates)): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px;">
                    <?php foreach ($certificates as $cert): ?>
                        <div class="card" style="overflow: hidden;">
                            <!-- Certificate Preview -->
                            <div style="background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%); padding: 40px 20px; text-align: center; color: white; position: relative;">
                                <div style="font-size: 48px; margin-bottom: 10px;">🏆</div>
                                <h3 style="color: white; margin-bottom: 10px; font-size: 18px;">Certificate of Participation</h3>
                                <p style="font-size: 12px; opacity: 0.9;">Issued on <?= date('d F Y', strtotime($cert['issued_at'])) ?></p>
                            </div>

                            <!-- Certificate Details -->
                            <div class="card-body">
                                <h4 style="margin-bottom: 15px; color: var(--text-dark);">
                                    <?= htmlspecialchars($cert['event_title']) ?>
                                </h4>
                                <div style="font-size: 14px; color: var(--text-muted); margin-bottom: 20px;">
                                    <p style="margin-bottom: 8px;">
                                        <strong>📅 Tanggal Event:</strong><br>
                                        <?= date('d M Y', strtotime($cert['start_at'])) ?>
                                    </p>
                                    <p style="margin-bottom: 8px;">
                                        <strong>📝 Terdaftar:</strong><br>
                                        <?= date('d M Y H:i', strtotime($cert['registered_at'])) ?>
                                    </p>
                                    <p>
                                        <strong>✅ Sertifikat Diterbitkan:</strong><br>
                                        <?= date('d M Y H:i', strtotime($cert['issued_at'])) ?>
                                    </p>
                                </div>

                                <!-- Action Buttons -->
                                <div style="display: flex; gap: 10px;">
                                    <a href="<?= $cert['file_path'] ?>" target="_blank" class="btn btn-primary" style="flex: 1; text-align: center;">
                                        👁️ Lihat
                                    </a>
                                    <a href="api/certificates.php?action=download&certificate_id=<?= $cert['id'] ?>" class="btn btn-success" style="flex: 1; text-align: center;">
                                        📥 Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="card" style="padding: 60px 20px;">
                    <div class="text-center">
                        <div style="font-size: 80px; margin-bottom: 20px; opacity: 0.3;">🏆</div>
                        <h3 style="margin-bottom: 15px; color: var(--text-dark);">Belum Ada Sertifikat</h3>
                        <p style="color: var(--text-muted); margin-bottom: 30px;">
                            Anda belum memiliki sertifikat. Ikuti event dan hadiri untuk mendapatkan sertifikat!
                        </p>
                        <a href="index.php?page=user_browse_events" class="btn btn-primary">
                            🔍 Cari Event
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Info Box -->
            <div class="alert alert-info" style="margin-top: 30px;">
                <strong>ℹ️ Informasi:</strong><br>
                • Sertifikat akan otomatis diterbitkan setelah admin mengkonfirmasi kehadiran Anda<br>
                • Anda dapat men-download sertifikat dalam format HTML yang bisa di-print atau di-convert ke PDF<br>
                • Sertifikat bersifat resmi dan dapat digunakan untuk keperluan akademik maupun non-akademik
            </div>
        </main>
    </div>
</body>

</html>