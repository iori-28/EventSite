<?php

/**
 * Check if Google Calendar migration has been run
 */

require_once __DIR__ . '/../config/db.php';

$db = Database::connect();

echo "Checking Google Calendar columns in users table...\n\n";

$columns = ['google_calendar_token', 'google_calendar_refresh_token', 'google_calendar_token_expires', 'calendar_auto_add', 'calendar_connected_at'];

foreach ($columns as $column) {
    $result = $db->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($result->rowCount() > 0) {
        echo "✅ Column '$column' EXISTS\n";
    } else {
        echo "❌ Column '$column' NOT FOUND\n";
    }
}

echo "\n";
echo "If columns NOT FOUND, run migration:\n";
echo "mysql -u root -p eventsite < database/migrations/migration_google_calendar_oauth.sql\n";
