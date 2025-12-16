<?php

/**
 * Run Event Completion Workflow Migration
 * This script adds the waiting_completion status and tracking fields
 */

$_SERVER['DOCUMENT_ROOT'] = 'c:/laragon/www';
require_once dirname(__DIR__) . '/config/db.php';

echo "=================================================\n";
echo "Event Completion Workflow Migration\n";
echo "=================================================\n\n";

try {
    $db = Database::connect();

    // Read migration file
    $migration = file_get_contents(dirname(__DIR__) . '/database/migrations/migration_event_completion_workflow.sql');

    // Remove comments
    $lines = explode("\n", $migration);
    $cleaned = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '--')) continue;
        $cleaned .= $line . ' ';
    }

    // Split by semicolons and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $cleaned)),
        fn($s) => !empty($s)
    );

    $db->beginTransaction();

    $executed = 0;
    $skipped = 0;

    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;

        echo "Executing: " . substr($statement, 0, 80) . "...\n";

        try {
            $db->exec($statement);
            $executed++;
            echo "  ✓ Success\n";
        } catch (PDOException $e) {
            // If column already exists or other minor error, skip
            if (
                strpos($e->getMessage(), 'Duplicate column name') !== false ||
                strpos($e->getMessage(), 'Duplicate key name') !== false
            ) {
                echo "  ⊘ Skipped (already exists)\n";
                $skipped++;
            } else {
                throw $e;
            }
        }
    }

    $db->commit();

    echo "\n✓ SUCCESS!\n";
    echo "Executed: $executed statements\n";
    echo "Skipped: $skipped statements (already exists)\n\n";

    // Verify changes
    echo "Verifying changes...\n";
    $result = $db->query("DESCRIBE events")->fetchAll(PDO::FETCH_ASSOC);

    echo "\nEvents table structure:\n";
    echo str_pad("Field", 25) . str_pad("Type", 30) . "Null\n";
    echo str_repeat("-", 60) . "\n";

    foreach ($result as $row) {
        echo str_pad($row['Field'], 25) .
            str_pad($row['Type'], 30) .
            str_pad($row['Null'], 5) . "\n";
    }

    echo "\n✓ Migration completed successfully!\n";
    echo "=================================================\n";
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    echo "\n✗ ERROR!\n";
    echo "Migration failed: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
