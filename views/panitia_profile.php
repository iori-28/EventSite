<?php


// Check role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'panitia') {
    header('Location: index.php?page=login');
    exit;
}

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

                header('Location: index.php?page=panitia_profile&profile_updated=1');
                exit;
            } catch (PDOException $e) {
                $error_msg = "Gagal memperbarui profil: " . $e->getMessage();
            }
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
                header('Location: index.php?page=panitia_profile&password_updated=1');
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
    <title>Profil Saya - EventSite</title>
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
                    <h1>Profil Saya</h1>
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