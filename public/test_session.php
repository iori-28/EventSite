<?php

/**
 * Test Session Debug
 * Check what's in the session
 */

session_start();

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Session Debug</h2>";
echo "<pre>";

if (isset($_SESSION['user'])) {
    echo "User Session Data:\n";
    echo "=================\n";
    foreach ($_SESSION['user'] as $key => $value) {
        echo $key . ": " . ($value ?? 'NULL') . "\n";
    }

    echo "\n\nProfile Picture Details:\n";
    echo "========================\n";
    echo "Value: " . ($_SESSION['user']['profile_picture'] ?? 'NOT SET') . "\n";
    echo "Empty check: " . (empty($_SESSION['user']['profile_picture']) ? 'YES (EMPTY)' : 'NO (HAS VALUE)') . "\n";

    if (!empty($_SESSION['user']['profile_picture'])) {
        echo "Is URL: " . (strpos($_SESSION['user']['profile_picture'], 'http') === 0 ? 'YES' : 'NO') . "\n";

        echo "\n\nImage Display Test:\n";
        echo "===================\n";
        echo '<img src="' . htmlspecialchars($_SESSION['user']['profile_picture']) . '" alt="Profile" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #3498db;">';
        echo "\n\nHTML Code:\n";
        echo htmlspecialchars('<img src="' . $_SESSION['user']['profile_picture'] . '" alt="Profile">');
    }
} else {
    echo "NO USER SESSION FOUND!\n";
    echo "User is not logged in.\n";
}

echo "</pre>";

echo "<hr>";
echo "<a href='../index.php'>Back to Home</a> | <a href='dashboard.php'>Back to Dashboard</a>";
