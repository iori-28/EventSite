<?php

/**
 * Run Event Category Migration
 * Adds category column to events table
 */

echo "======================================\n";
echo "Event Category Migration\n";
echo "======================================\n\n";

$migration_file = __DIR__ . '/../database/migrations/migration_add_event_category.sql';

if (!file_exists($migration_file)) {
    die("❌ Error: Migration file not found at: $migration_file\n");
}

// Read SQL file
$sql = file_get_contents($migration_file);

if ($sql === false) {
    die("❌ Error: Failed to read migration file\n");
}

echo "📄 Migration file loaded: migration_add_event_category.sql\n";

// Connect to database
require_once __DIR__ . '/../config/db.php';

try {
    $db = Database::connect();
    echo "✅ Database connection established\n\n";

    // Check if column already exists
    $check = $db->query("SHOW COLUMNS FROM events LIKE 'category'")->fetch();

    if ($check) {
        echo "⚠️  Column 'category' already exists in events table\n";
        echo "   Migration may have been run before\n";
        echo "   Skipping to avoid errors...\n\n";

        // Show current stats
        $stats = $db->query("
            SELECT 
                COUNT(*) as total_events,
                COUNT(CASE WHEN category IS NOT NULL THEN 1 END) as events_with_category,
                COUNT(CASE WHEN category = 'Lainnya' THEN 1 END) as default_category
            FROM events
        ")->fetch(PDO::FETCH_ASSOC);

        echo "📊 Current Statistics:\n";
        echo "   Total Events: {$stats['total_events']}\n";
        echo "   With Category: {$stats['events_with_category']}\n";
        echo "   Default (Lainnya): {$stats['default_category']}\n\n";

        exit(0);
    }

    echo "🚀 Running migration...\n";

    // Split SQL by semicolons and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function ($stmt) {
            return !empty($stmt) && strpos($stmt, '--') !== 0;
        }
    );

    foreach ($statements as $index => $statement) {
        if (empty($statement)) continue;

        try {
            $db->exec($statement);
            echo "   ✓ Statement " . ($index + 1) . " executed\n";
        } catch (PDOException $e) {
            echo "   ⚠️  Statement " . ($index + 1) . ": " . $e->getMessage() . "\n";
        }
    }

    echo "\n✅ Migration completed successfully!\n\n";

    // Verify results
    echo "🔍 Verifying migration...\n";

    // Check column exists
    $column = $db->query("SHOW COLUMNS FROM events LIKE 'category'")->fetch();
    if ($column) {
        echo "   ✓ Column 'category' exists\n";
        echo "     Type: {$column['Type']}\n";
        echo "     Default: {$column['Default']}\n";
    }

    // Check index
    $index = $db->query("SHOW INDEX FROM events WHERE Key_name = 'idx_category'")->fetch();
    if ($index) {
        echo "   ✓ Index 'idx_category' exists\n";
    }

    // Show stats
    $stats = $db->query("
        SELECT 
            COUNT(*) as total_events,
            COUNT(CASE WHEN category IS NOT NULL THEN 1 END) as events_with_category,
            category,
            COUNT(*) as count
        FROM events
        GROUP BY category WITH ROLLUP
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "\n📊 Final Statistics:\n";
    foreach ($stats as $stat) {
        if ($stat['category'] === null) {
            echo "   TOTAL: {$stat['count']} events\n";
        } else {
            echo "   {$stat['category']}: {$stat['count']} events\n";
        }
    }

    echo "\n✅ Migration successful! Ready to use.\n";
    echo "\n📝 Next steps:\n";
    echo "   1. Create/edit events to assign categories\n";
    echo "   2. Check Admin Analytics for category stats\n";
    echo "   3. Test category filtering (if implemented)\n";
} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
