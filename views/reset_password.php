<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

$email = 'user@example.com';
$new_pass = 'password';
$hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

$stmt = $db->prepare("UPDATE users SET password = :password WHERE email = :email");
$result = $stmt->execute([':password' => $hashed_pass, ':email' => $email]);

if ($result) {
    echo "Password for $email has been reset to '$new_pass'.";
} else {
    echo "Failed to reset password.";
}
