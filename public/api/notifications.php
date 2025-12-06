<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';

if (!isset($_SESSION['user'])) {
    die("NO_SESSION");
}

$action = $_POST['action'] ?? '';

if ($action === 'test-create') {
    // manual test endpoint: buat notifikasi pending lalu kirim
    $user_id = $_SESSION['user']['id'];
    $email = $_SESSION['user']['email'] ?? '';
    $payload = ['email' => $email, 'note' => 'test'];
    $subject = "TEST EMAIL DARI SISTEM";
    $body = "<p>Ini email test.</p>";

    $res = NotificationController::createAndSend($user_id, 'test', $payload, $subject, $body);
    echo json_encode($res);
}
