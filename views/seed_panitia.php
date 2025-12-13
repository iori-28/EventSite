<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

$email = 'panitia@example.com';
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Panitia found. Resetting password...\n";
    $new_pass = 'password';
    $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = :password WHERE email = :email");
    $stmt->execute([':password' => $hashed_pass, ':email' => $email]);
    echo "Password reset to '$new_pass'.";
} else {
    echo "Panitia not found. Creating...\n";
    $name = 'Panitia Demo';
    $password = password_hash('password', PASSWORD_DEFAULT);
    $role = 'panitia';

    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $role]);
    echo "Panitia user created with password 'password'.";
}
