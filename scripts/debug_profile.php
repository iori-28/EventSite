<?php

/**
 * Debug Profile Pictures
 * Check database values for Google OAuth users
 */

require_once __DIR__ . '/../config/db.php';

echo "=== DEBUG PROFILE PICTURES ===\n\n";

try {
    $db = Database::connect();

    // Check Google OAuth users
    echo "Google OAuth Users:\n";
    echo str_repeat('-', 80) . "\n";

    $stmt = $db->query("
        SELECT id, name, email, google_id, profile_picture, oauth_provider 
        FROM users 
        WHERE oauth_provider = 'google' 
        ORDER BY id DESC 
        LIMIT 5
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        echo "No Google OAuth users found.\n";
    } else {
        foreach ($users as $user) {
            echo "ID: " . $user['id'] . "\n";
            echo "Name: " . $user['name'] . "\n";
            echo "Email: " . $user['email'] . "\n";
            echo "Google ID: " . ($user['google_id'] ?? 'NULL') . "\n";
            echo "Profile Picture: " . ($user['profile_picture'] ?? 'NULL') . "\n";
            echo "OAuth Provider: " . ($user['oauth_provider'] ?? 'NULL') . "\n";

            if (!empty($user['profile_picture'])) {
                // Check if it's a URL or local path
                if (strpos($user['profile_picture'], 'http') === 0) {
                    echo "Type: External URL (Google)\n";
                } else {
                    echo "Type: Local file\n";
                    $fullPath = __DIR__ . '/../public/' . $user['profile_picture'];
                    echo "Full path: " . $fullPath . "\n";
                    echo "File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
                }
            }

            echo str_repeat('-', 80) . "\n";
        }
    }

    // Check all users with profile pictures
    echo "\n\nAll Users with Profile Pictures:\n";
    echo str_repeat('-', 80) . "\n";

    $stmt = $db->query("
        SELECT id, name, email, profile_picture, oauth_provider 
        FROM users 
        WHERE profile_picture IS NOT NULL 
        ORDER BY id DESC 
        LIMIT 10
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        echo "ID: " . $user['id'] . " | ";
        echo "Name: " . $user['name'] . " | ";
        echo "OAuth: " . ($user['oauth_provider'] ?? 'local') . " | ";
        echo "Picture: " . substr($user['profile_picture'], 0, 60) . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";
