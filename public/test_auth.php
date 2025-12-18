<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Auth Middleware</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .success {
            color: #28a745;
            font-weight: bold;
        }

        .error {
            color: #dc3545;
            font-weight: bold;
        }

        .info {
            color: #17a2b8;
        }

        img {
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        td:first-child {
            font-weight: bold;
            width: 200px;
            color: #666;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-right: 10px;
        }

        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>🔧 Test Auth Middleware</h1>
        <p class="info">This page tests the new Auth middleware and session refresh functionality.</p>
    </div>

    <?php
    session_start();

    if (!isset($_SESSION['user'])) {
        echo '<div class="card"><p class="error">❌ Not logged in. Please <a href="index.php?page=login">login</a> first.</p></div>';
        echo '<div class="card"><a href="index.php" class="btn">Go to Home</a></div>';
        exit;
    }

    echo '<div class="card">';
    echo '<h2>📋 Session Data (Before Auth::check)</h2>';
    echo '<table>';
    foreach ($_SESSION['user'] as $key => $value) {
        echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
    }
    echo '</table>';
    echo '</div>';

    // Now call Auth::check to refresh session
    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/AuthMiddleware.php';
    $fresh_user = Auth::check();

    echo '<div class="card">';
    echo '<h2>✨ Session Data (After Auth::check - Fresh from DB)</h2>';
    echo '<table>';
    foreach ($_SESSION['user'] as $key => $value) {
        echo '<tr><td>' . htmlspecialchars($key) . '</td><td>' . htmlspecialchars($value ?? 'NULL') . '</td></tr>';
    }
    echo '</table>';
    echo '</div>';

    echo '<div class="card">';
    echo '<h2>🖼️ Profile Picture Test</h2>';

    if (!empty($_SESSION['user']['profile_picture'])) {
        echo '<p class="success">✅ Profile picture found in session!</p>';
        echo '<p><strong>URL:</strong> ' . htmlspecialchars($_SESSION['user']['profile_picture']) . '</p>';

        if (strpos($_SESSION['user']['profile_picture'], 'http') === 0) {
            echo '<p class="info">Type: External URL (Google OAuth)</p>';
        } else {
            echo '<p class="info">Type: Local uploaded file</p>';
        }

        echo '<div style="margin-top: 20px;">';
        echo '<p><strong>Display Test:</strong></p>';
        echo '<img src="' . htmlspecialchars($_SESSION['user']['profile_picture']) . '" alt="Profile" style="width: 120px; height: 120px;">';
        echo '</div>';
    } else {
        echo '<p class="error">❌ No profile picture in session</p>';

        // Check database directly
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
        $db = Database::connect();
        $stmt = $db->prepare("SELECT profile_picture FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user']['id']]);
        $db_pic = $stmt->fetchColumn();

        if ($db_pic) {
            echo '<p class="error">⚠️ BUT profile picture EXISTS in database: ' . htmlspecialchars($db_pic) . '</p>';
            echo '<p class="error">This means Auth::check() is not working properly!</p>';
        } else {
            echo '<p class="info">ℹ️ No profile picture in database either - user hasn\'t uploaded one yet.</p>';
        }
    }
    echo '</div>';

    echo '<div class="card">';
    echo '<h2>🎯 OAuth Provider Info</h2>';
    if (!empty($_SESSION['user']['oauth_provider'])) {
        echo '<p class="success">✅ Connected via: ' . htmlspecialchars($_SESSION['user']['oauth_provider']) . '</p>';
        if (!empty($_SESSION['user']['google_id'])) {
            echo '<p>Google ID: ' . htmlspecialchars($_SESSION['user']['google_id']) . '</p>';
        }
    } else {
        echo '<p class="info">ℹ️ Local account (not OAuth)</p>';
    }
    echo '</div>';

    echo '<div class="card">';
    echo '<a href="index.php" class="btn">Go to Home</a>';
    echo '<a href="index.php?page=' . htmlspecialchars($_SESSION['user']['role']) . '_dashboard" class="btn">Go to Dashboard</a>';
    echo '<a href="index.php?page=' . htmlspecialchars($_SESSION['user']['role']) . '_profile" class="btn">Go to Profile</a>';
    echo '</div>';
    ?>
</body>

</html>