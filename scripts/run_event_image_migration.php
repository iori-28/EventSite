<?php

/**
 * Run Event Image Migration
 * 
 * Script untuk menambahkan kolom event_image ke table events.
 * Run this file once via browser or CLI.
 */

require_once __DIR__ . '/../config/db.php';

try {
    $db = Database::connect();

    echo "<h2>Running Event Image Migration...</h2>\n";

    // Check if column already exists
    $check = $db->query("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'events' 
          AND COLUMN_NAME = 'event_image'
    ");

    $exists = $check->fetch(PDO::FETCH_ASSOC)['count'];

    if ($exists > 0) {
        echo "<p style='color: orange;'>⚠️ Column 'event_image' already exists. Skipping migration.</p>\n";
        exit;
    }

    // Add event_image column
    $sql = "ALTER TABLE events ADD COLUMN event_image VARCHAR(255) NULL AFTER description";
    $db->exec($sql);

    echo "<p style='color: green;'>✓ Successfully added 'event_image' column to events table.</p>\n";

    // Verify
    $verify = $db->query("
        SELECT 
            COLUMN_NAME, 
            COLUMN_TYPE, 
            IS_NULLABLE, 
            COLUMN_DEFAULT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'events' 
          AND COLUMN_NAME = 'event_image'
    ");

    $result = $verify->fetch(PDO::FETCH_ASSOC);

    echo "<h3>Verification:</h3>\n";
    echo "<pre>";
    print_r($result);
    echo "</pre>";

    echo "<p style='color: green; font-weight: bold;'>✅ Migration completed successfully!</p>\n";
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Migration failed: " . $e->getMessage() . "</p>\n";
}
