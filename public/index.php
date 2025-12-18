<?php
// Main Router
session_start();

// Session security
if (empty($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

$page = $_GET['page'] ?? 'home';

// Handle Logout
if ($page === 'logout') {
    // 1. Clear Session
    $_SESSION = array();

    // 2. Kill Cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // 3. Destroy
    session_destroy();

    // 4. Output HTML with Meta Refresh (More robust than immediate header() for some browsers)
?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta http-equiv="refresh" content="0;url=index.php?page=home">
        <script>
            localStorage.clear();
            sessionStorage.clear();
        </script>
    </head>

    <body style="background:#f8f9fa;"></body>

    </html>
<?php
    exit;
}

// Whitelist allowed pages for security
$allowed_pages = [
    'home',
    'login',
    'register',
    'events',
    'event-detail',
    'user_dashboard',
    'user_browse_events',
    'user_my_events',
    'user_notifications',
    'user_profile',
    'user_certificates',
    'panitia_dashboard',
    'panitia_create_event',
    'panitia_my_events',
    'panitia_participants',
    'panitia_notifications',
    'panitia_profile',
    'panitia_edit_event',
    'admin_dashboard',
    'admin_profile',
    'admin_analytics',
    'admin_manage_events',
    'admin_event_completion',
    'admin_manage_users',
    'admin_reports',
    'admin_notifications',
    'admin_confirm_attendance',
    'admin_edit_event',
    'adm_apprv_event',
];

// Sanitize and check
$page = basename($page); // Prevent directory traversal
if (!in_array($page, $allowed_pages)) {
    // 404 behavior or fallback to home
    $page = 'home';
}

$file = __DIR__ . '/../views/' . $page . '.php';

if (file_exists($file)) {
    require_once $file;
} else {
    echo "404 Page Not Found";
}
?>