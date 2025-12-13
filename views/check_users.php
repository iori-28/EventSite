<?php
require_once 'config/db.php';
$db = Database::connect();
$users = $db->query("SELECT id, name, email, role, password FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "User: {$u['email']} | Role: {$u['role']} | Pass Check ('password'): " . (password_verify('password', $u['password']) ? 'OK' : 'FAIL') . "\n";
}
