<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';

// Check authentication and refresh session from database
Auth::check('admin');

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();
$user_id = $_SESSION['user']['id'];
$success_msg = '';
$error_msg = '';

// Check for success messages from redirects
if (isset($_GET['profile_updated'])) {
    $success_msg = "Profil berhasil diperbarui!";
} elseif (isset($_GET['password_updated'])) {
    $success_msg = "Password berhasil diubah!";
} elseif (isset($_GET['photo_updated'])) {
    $success_msg = "Foto profil berhasil diperbarui!";
} elseif (isset($_GET['photo_deleted'])) {
    $success_msg = "Foto profil berhasil dihapus!";
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';

        if ($name && $email) {
            try {
                $stmt = $db->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
                $stmt->execute([':name' => $name, ':email' => $email, ':id' => $user_id]);

                // Update session
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;

                header('Location: index.php?page=admin_profile&profile_updated=1');
                exit;
            } catch (PDOException $e) {
                $error_msg = "Gagal memperbarui profil: " . $e->getMessage();
            }
        }
    } elseif ($action === 'upload_photo') {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_picture'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed_types)) {
                $error_msg = 'Format file tidak valid. Gunakan JPG, PNG, atau GIF.';
            } elseif ($file['size'] > $max_size) {
                $error_msg = 'Ukuran file terlalu besar. Maksimal 2MB.';
            } else {
                // Delete old photo if exists and not from Google
                $stmt = $db->prepare("SELECT profile_picture, oauth_provider FROM users WHERE id = :id");
                $stmt->execute([':id' => $user_id]);
                $current_user = $stmt->fetch();

                if ($current_user['profile_picture'] && empty($current_user['oauth_provider'])) {
                    $old_file = $_SERVER['DOCUMENT_ROOT'] . '/EventSite/public/' . $current_user['profile_picture'];
                    if (file_exists($old_file)) {
                        unlink($old_file);
                    }
                }

                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/EventSite/public/uploads/profiles/';

                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) {
                    $profile_picture = 'uploads/profiles/' . $filename;
                    $stmt = $db->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :id");
                    $stmt->execute([':profile_picture' => $profile_picture, ':id' => $user_id]);

                    $_SESSION['user']['profile_picture'] = $profile_picture;
                    header('Location: index.php?page=admin_profile&photo_updated=1');
                    exit;
                } else {
                    $error_msg = 'Gagal mengupload foto.';
                }
            }
        }
    } elseif ($action === 'delete_photo') {
        // Delete profile picture (only if not from Google OAuth)
        $stmt = $db->prepare("SELECT profile_picture, oauth_provider FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $current_user = $stmt->fetch();

        if ($current_user['profile_picture'] && empty($current_user['oauth_provider'])) {
            $old_file = $_SERVER['DOCUMENT_ROOT'] . '/EventSite/public/' . $current_user['profile_picture'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }

            $stmt = $db->prepare("UPDATE users SET profile_picture = NULL WHERE id = :id");
            $stmt->execute([':id' => $user_id]);

            $_SESSION['user']['profile_picture'] = null;
            header('Location: index.php?page=admin_profile&photo_deleted=1');
            exit;
        } else {
            $error_msg = 'Tidak dapat menghapus foto dari OAuth provider atau foto tidak ada.';
        }
    } elseif ($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if ($new_pass !== $confirm_pass) {
            $error_msg = "Password baru tidak cocok.";
        } else {
            // Verify current password
            $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $user_id]);
            $user = $stmt->fetch();

            if (!password_verify($current_pass, $user['password'])) {
                $error_msg = "Password saat ini salah.";
            } elseif (password_verify($new_pass, $user['password'])) {
                $error_msg = "Password baru tidak boleh sama dengan password lama.";
            } else {
                $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute([':password' => $hashed_pass, ':id' => $user_id]);
                header('Location: index.php?page=admin_profile&password_updated=1');
                exit;
            }
        }
    }
}

// Get current user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Admin - EventSite</title>
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
                    <h1>Profil Admin</h1>
                    <div class="header-breadcrumb">Kelola informasi akun Anda</div>
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

            <!-- Profile Picture Section -->
            <div class="card" style="margin-bottom: 30px;">
                <div class="card-header" style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                    <h3 style="margin: 0; font-size: 18px;">📸 Foto Profil</h3>
                </div>
                <div class="card-body" style="padding: 24px; display: flex; align-items: center; gap: 30px;">
                    <div>
                        <?php if (!empty($user['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_picture']) ?>" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                        <?php else: ?>
                            <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 48px; font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 8px 0; font-size: 20px;"><?= htmlspecialchars($user['name']) ?></h4>
                        <p style="color: #6c757d; margin: 0 0 15px 0;"><?= htmlspecialchars($user['email']) ?></p>
                        <?php if (!empty($user['oauth_provider'])): ?>
                            <span style="display: inline-block; padding: 4px 12px; background: #e3f2fd; color: #1976d2; border-radius: 12px; font-size: 12px; font-weight: 500; margin-bottom: 15px;">
                                🔗 Connected via <?= ucfirst($user['oauth_provider']) ?>
                            </span>
                        <?php endif; ?>
                        <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                            <input type="hidden" name="action" value="upload_photo">
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <input type="file" name="profile_picture" accept="image/*" id="photoInput" style="display: none;" onchange="this.form.submit()">
                                <label for="photoInput" class="btn btn-primary" style="margin: 0; cursor: pointer;">📷 Ganti Foto</label>
                                <?php if (!empty($user['profile_picture']) && empty($user['oauth_provider'])): ?>
                                    <button type="button" onclick="if(confirm('Yakin ingin menghapus foto profil?')) { this.form.action.value='delete_photo'; this.form.submit(); }" class="btn btn-outline" style="margin: 0; border-color: #dc3545; color: #dc3545;">🗑️ Hapus Foto</button>
                                <?php endif; ?>
                                <small style="color: #6c757d; width: 100%;">JPG, PNG, GIF. Max 2MB</small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="grid grid-2" style="align-items: start;">
                <!-- Profile Info -->
                <div class="card">
                    <div class="card-header" style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="margin: 0; font-size: 18px;">Informasi Pribadi</h3>
                    </div>
                    <div class="card-body" style="padding: 24px;">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="form-group mb-3">
                                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Nama Lengkap</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                            </div>

                            <div class="form-group mb-4">
                                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Email</label>
                                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="form-control" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 6px;">
                            </div>

                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header" style="padding: 24px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="margin: 0; font-size: 18px;">Ganti Password</h3>
                    </div>
                    <div class="card-body" style="padding: 24px;">
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">

                            <div class="form-group mb-3">
                                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Password Saat Ini</label>
                                <div class="password-toggle" style="position: relative;">
                                    <input type="password" id="current_password" name="current_password" required class="form-control" style="width: 100%; padding: 10px; padding-right: 45px; border: 1px solid var(--border-color); border-radius: 6px;">
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('current_password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 18px;">
                                        👁️
                                    </button>
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Password Baru</label>
                                <div class="password-toggle" style="position: relative;">
                                    <input type="password" id="new_password" name="new_password" required class="form-control" style="width: 100%; padding: 10px; padding-right: 45px; border: 1px solid var(--border-color); border-radius: 6px;">
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('new_password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 18px;">
                                        👁️
                                    </button>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Konfirmasi Password Baru</label>
                                <div class="password-toggle" style="position: relative;">
                                    <input type="password" id="confirm_password" name="confirm_password" required class="form-control" style="width: 100%; padding: 10px; padding-right: 45px; border: 1px solid var(--border-color); border-radius: 6px;">
                                    <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 18px;">
                                        👁️
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-outline">Update Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleBtn = passwordInput.nextElementSibling;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
    </script>
</body>

</html>