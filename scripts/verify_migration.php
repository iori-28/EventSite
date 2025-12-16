<?php

/**
 * Verify Migration Status
 */

$_SERVER['DOCUMENT_ROOT'] = 'c:/laragon/www';
require_once dirname(__DIR__) . '/config/db.php';

try {
    $db = Database::connect();

    echo "=================================================\n";
    echo "Migration Verification\n";
    echo "=================================================\n\n";

    // Check events table structure
    $result = $db->query("DESCRIBE events")->fetchAll(PDO::FETCH_ASSOC);

    echo "Events Table Structure:\n";
    echo str_repeat("-", 80) . "\n";
    echo str_pad("Field", 20) . str_pad("Type", 40) . str_pad("Null", 8) . "Key\n";
    echo str_repeat("-", 80) . "\n";

    $hasCompletedBy = false;
    $hasCompletedAt = false;
    $hasApprovedBy = false;
    $hasApprovedAt = false;
    $hasWaitingStatus = false;

    foreach ($result as $row) {
        echo str_pad($row['Field'], 20) .
            str_pad($row['Type'], 40) .
            str_pad($row['Null'], 8) .
            $row['Key'] . "\n";

        if ($row['Field'] === 'completed_by') $hasCompletedBy = true;
        if ($row['Field'] === 'completed_at') $hasCompletedAt = true;
        if ($row['Field'] === 'approved_by') $hasApprovedBy = true;
        if ($row['Field'] === 'approved_at') $hasApprovedAt = true;
        if ($row['Field'] === 'status' && strpos($row['Type'], 'waiting_completion') !== false) {
            $hasWaitingStatus = true;
        }
    }

    echo "\n" . str_repeat("-", 80) . "\n\n";

    echo "Migration Checklist:\n";
    echo "  " . ($hasCompletedBy ? "✓" : "✗") . " completed_by column\n";
    echo "  " . ($hasCompletedAt ? "✓" : "✗") . " completed_at column\n";
    echo "  " . ($hasApprovedBy ? "✓" : "✗") . " approved_by column\n";
    echo "  " . ($hasApprovedAt ? "✓" : "✗") . " approved_at column\n";
    echo "  " . ($hasWaitingStatus ? "✓" : "✗") . " waiting_completion status\n";

    // Check foreign keys
    $fks = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'events' 
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "\nForeign Keys:\n";
    foreach ($fks as $fk) {
        echo "  ✓ " . $fk['CONSTRAINT_NAME'] . "\n";
    }

    $allGood = $hasCompletedBy && $hasCompletedAt && $hasApprovedBy && $hasApprovedAt && $hasWaitingStatus;

    echo "\n" . str_repeat("=", 80) . "\n";
    if ($allGood) {
        echo "✓ MIGRATION SUCCESS! All changes applied correctly.\n";
    } else {
        echo "✗ WARNING! Some changes may be missing.\n";
    }
    echo str_repeat("=", 80) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
