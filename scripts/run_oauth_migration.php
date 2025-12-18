<?php

/**
 * Migration Runner: Add OAuth Columns to Users Table
 * 
 * This script adds Google OAuth support columns to the users table:
 * - google_id: Stores Google user ID (unique)
 * - profile_picture: Stores profile picture URL from Google
 * - oauth_provider: Stores OAuth provider name (google, facebook, etc)
 * 
 * Usage: Access via browser http://localhost/EventSite/scripts/run_oauth_migration.php
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

// Set headers for HTML output
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>OAuth Migration Runner</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { color: #155724; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: #721c24; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔐 Google OAuth Migration Runner</h1>";

try {
    $db = Database::connect();

    echo "<div class='info'>Connected to database successfully</div>";

    // Check if columns already exist
    echo "<h2>Step 1: Checking existing columns...</h2>";

    $check = $db->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'users' 
        AND COLUMN_NAME IN ('google_id', 'profile_picture', 'oauth_provider')
    ")->fetchAll(PDO::FETCH_COLUMN);

    if (count($check) >= 3) {
        echo "<div class='success'>✅ OAuth columns already exist! No migration needed.</div>";

        // Show current columns
        echo "<h3>Current OAuth Columns:</h3><pre>";
        $columns = $db->query("
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'users' 
            AND COLUMN_NAME IN ('google_id', 'profile_picture', 'oauth_provider')
        ")->fetchAll();
        print_r($columns);
        echo "</pre>";
    } else {
        echo "<div class='info'>Columns not found. Running migration...</div>";

        // Read migration file
        echo "<h2>Step 2: Running migration...</h2>";
        $migrationFile = $_SERVER['DOCUMENT_ROOT'] . '/EventSite/database/migrations/migration_add_oauth_columns.sql';

        if (!file_exists($migrationFile)) {
            throw new Exception("Migration file not found: $migrationFile");
        }

        $sql = file_get_contents($migrationFile);

        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            // Skip comments and verification queries
            if (
                empty($statement) ||
                strpos($statement, '--') === 0 ||
                stripos($statement, 'SELECT') === 0
            ) {
                continue;
            }

            echo "<div class='info'>Executing: " . substr($statement, 0, 100) . "...</div>";
            $db->exec($statement);
        }

        echo "<div class='success'>✅ Migration completed successfully!</div>";

        // Verify migration
        echo "<h2>Step 3: Verifying migration...</h2>";

        $verify = $db->query("
            SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'users' 
            AND COLUMN_NAME IN ('google_id', 'profile_picture', 'oauth_provider')
        ")->fetchAll();

        if (count($verify) >= 3) {
            echo "<div class='success'>✅ All OAuth columns created successfully!</div>";
            echo "<h3>New Columns:</h3><pre>";
            print_r($verify);
            echo "</pre>";
        } else {
            echo "<div class='error'>⚠️ Verification failed. Some columns may be missing.</div>";
        }
    }

    echo "<h2>✅ Migration Process Complete!</h2>";
    echo "<p><a href='../public/index.php'>← Back to EventSite</a></p>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Error:</strong><br>";
    echo htmlspecialchars($e->getMessage());
    echo "</div>";

    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
