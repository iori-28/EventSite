<?php

// Get redirect parameter
$redirect = $_GET['redirect'] ?? '';

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    if ($role === 'panitia') {
        header('Location: index.php?page=panitia_dashboard');
    } elseif ($role === 'admin') {
        header('Location: index.php?page=admin_dashboard');
    } else {
        header('Location: index.php?page=user_dashboard');
    }
    exit;
}

// Handle register
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/AuthController.php';

    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        $result = AuthController::register($name, $email, $password);

        if ($result === 'REGISTER_SUCCESS') {
            // Redirect to login with redirect parameter preserved
            $login_url = 'index.php?page=login&success=registered';
            if ($redirect) {
                $login_url .= '&redirect=' . urlencode($redirect);
            }
            header('Location: ' . $login_url);
            exit;
        } else {
            $error = 'Email sudah terdaftar atau terjadi kesalahan!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EventSite</title>
    <link rel="stylesheet" href="css/auth.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="logo">ES</div>
            <h1>Buat Akun Baru</h1>
            <p>Daftar untuk mengakses EventSite</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="register">
            <?php if ($redirect): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="name">Nama Lengkap</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="Masukkan nama lengkap"
                    required
                    value="<?= isset($_POST['name']) && !$success ? htmlspecialchars($_POST['name']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="nama@example.com"
                    required
                    value="<?= isset($_POST['email']) && !$success ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-toggle">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimal 6 karakter"
                        required
                        minlength="6">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
                        👁️
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <div class="password-toggle">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Ulangi password"
                        required
                        minlength="6">
                    <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Daftar</button>
        </form>

        <div class="divider">
            <span>atau</span>
        </div>

        <a href="api/google-login.php" class="btn-google">
            <svg width="18" height="18" viewBox="0 0 18 18" style="margin-right: 10px;">
                <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" />
                <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z" />
                <path fill="#FBBC05" d="M3.964 10.707c-.18-.54-.282-1.117-.282-1.707s.102-1.167.282-1.707V4.961H.957C.347 6.175 0 7.55 0 9s.348 2.825.957 4.039l3.007-2.332z" />
                <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.961L3.964 7.293C4.672 5.163 6.656 3.58 9 3.58z" />
            </svg>
            Daftar dengan Google
        </a>

        <div class="auth-footer">
            <p>Sudah punya akun? <a href="index.php?page=login<?= $redirect ? '&redirect=' . urlencode($redirect) : '' ?>">Masuk sekarang</a></p>
        </div>
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

        // Password match validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');

        confirmPassword.addEventListener('input', function() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Password tidak cocok!');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });

        password.addEventListener('input', function() {
            if (confirmPassword.value && password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Password tidak cocok!');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });

        // Auto redirect to login after successful registration
        <?php if ($success): ?>
            setTimeout(() => {
                window.location.href = 'index.php?page=login';
            }, 3000);
        <?php endif; ?>
    </script>
</body>

</html>