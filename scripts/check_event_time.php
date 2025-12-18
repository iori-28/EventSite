<?php
require_once __DIR__ . '/../config/db.php';

$db = Database::connect();
$stmt = $db->query('SELECT id, title, start_at FROM events WHERE id = 33');
$event = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Event in database:\n";
print_r($event);

echo "\nPHP time: " . date('Y-m-d H:i:s') . "\n";
echo "Expected window: " . date('Y-m-d H:i:s', strtotime('+24 hours')) . " to " . date('Y-m-d H:i:s', strtotime('+25 hours')) . "\n";
