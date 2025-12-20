<?php

/**
 * Run Google Calendar OAuth Migration
 */

require_once __DIR__ . '/../config/db.php';

echo "Running Google Calendar OAuth Migration...\n\n";

$db = Database::connect();

try {
    // Read migration file
    $sql = file_get_contents(__DIR__ . '/../database/migrations/migration_google_calendar_oauth.sql');

    // Remove comments and split by semicolon
    $statements = array_filter(
        array_map(
            'trim',
            preg_split('/;[\s]*\n/', $sql)
        ),
        function ($stmt) {
            // Remove comments and empty lines
            $stmt = preg_replace('/--.*$/m', '', $stmt);
            $stmt = preg_replace('/\/\*.*?\*\//s', '', $stmt);
            $stmt = trim($stmt);
            return !empty($stmt) && !preg_match('/^(SELECT|SHOW)/', $stmt);
        }
    );

    $executed = 0;
    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        $db->exec($statement);
        $executed++;
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "   Executed $executed statements\n\n";

    // Verify
    echo "Verifying columns...\n";
    $columns = ['google_calendar_token', 'google_calendar_refresh_token', 'google_calendar_token_expires', 'calendar_auto_add', 'calendar_connected_at'];

    $success = 0;
    foreach ($columns as $column) {
        $result = $db->query("SHOW COLUMNS FROM users LIKE '$column'");
        if ($result->rowCount() > 0) {
            echo "✅ Column '$column' created\n";
            $success++;
        } else {
            echo "❌ Column '$column' failed\n";
        }
    }

    if ($success === count($columns)) {
        echo "\n🎉 All columns created successfully!\n";
    }
} catch (PDOException $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
