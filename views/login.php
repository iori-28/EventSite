<?php


// Get redirect parameter
$redirect = $_GET['redirect'] ?? '';

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    // If there's a redirect URL, go there
    if ($redirect) {
        header('Location: ' . $redirect);
        exit;
    }

    // Otherwise, go to role-based dashboard
    $role = $_SESSION['user']['role'];
    if ($role === 'admin') {
        header('Location: index.php?page=admin_dashboard');
    } elseif ($role === 'panitia') {
        header('Location: index.php?page=panitia_dashboard');
    } else {
        header('Location: index.php?page=user_dashboard');
    }
    exit;
}

// Handle login
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    require_once '../controllers/AuthController.php';

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $redirect_after = $_POST['redirect'] ?? '';

    if (AuthController::login($email, $password)) {
        // If there's a redirect URL, go there after login
        if ($redirect_after) {
            header('Location: ' . $redirect_after);
            exit;
        }

        // Otherwise redirect to dashboard sesuai role
        $role = $_SESSION['user']['role'];
        if ($role === 'admin') {
            header('Location: index.php?page=admin_dashboard');
        } elseif ($role === 'panitia') {
            header('Location: index.php?page=panitia_dashboard');
        } else {
            header('Location: index.php?page=user_dashboard');
        }
        exit;
    } else {
        $error = 'Email atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EventSite</title>
    <link rel="stylesheet" href="css/auth.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-header">
            <div class="logo">ES</div>
            <h1>Selamat Datang</h1>
            <p>Masuk ke akun EventSite Anda</p>
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
            <input type="hidden" name="action" value="login">
            <?php if ($redirect): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="nama@example.com"
                    required
                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-toggle">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Masukkan password Anda"
                        required>
                    <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-submit">Masuk</button>
        </form>

        <div class="divider">
            <span>atau</span>
        </div>

        <div class="auth-footer">
            <p>Belum punya akun? <a href="index.php?page=register">Daftar sekarang</a></p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle-btn');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });
    </script>
</body>

</html>