<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/AuthController.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die("FORBIDDEN");
}

$action = $_POST['action'] ?? '';

/* =========================
   DELETE USER
   ========================= */
if ($action === 'delete') {
    $user_id = $_POST['id'];

    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
    $db = Database::connect();

    // Delete user
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        header('Location: ../index.php?page=admin_manage_users&msg=deleted');
    } else {
        header('Location: ../index.php?page=admin_manage_users&msg=failed');
    }
    exit;
}
