<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

$email = 'admin@example.com';
$stmt = $db->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Admin found. Resetting password...\n";
    $new_pass = 'password';
    $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

    $stmt = $db->prepare("UPDATE users SET password = :password, role = 'admin' WHERE email = :email");
    $stmt->execute([':password' => $hashed_pass, ':email' => $email]);
    echo "Password reset to '$new_pass' and role ensured as 'admin'.";
} else {
    echo "Admin not found. Creating...\n";
    $name = 'Administrator';
    $password = password_hash('password', PASSWORD_DEFAULT);
    $role = 'admin';

    $stmt = $db->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $password, $role]);
    echo "Admin user created with password 'password'.";
}
