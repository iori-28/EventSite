<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/CertificateController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/**
 * CONFIRM ATTENDANCE & GENERATE CERTIFICATE (ADMIN ONLY)
 */
if ($action === 'confirm_attendance') {
    if ($_SESSION['user']['role'] !== 'admin') {
        die("FORBIDDEN");
    }

    $participant_id = filter_input(INPUT_POST, 'participant_id', FILTER_VALIDATE_INT);

    if (!$participant_id || $participant_id <= 0) {
        http_response_code(400);
        die(json_encode(['error' => 'Invalid participant ID']));
    }

    $db = Database::connect();

    // Update participant status to checked_in
    $stmt = $db->prepare("UPDATE participants SET status = 'checked_in' WHERE id = ?");
    $success = $stmt->execute([$participant_id]);

    if (!$success) {
        die("UPDATE_FAILED");
    }

    // Generate certificate
    $result = CertificateController::generate($participant_id);

    if ($result['success']) {
        // NOTE: Notification will be sent when panitia completes the event
        // This endpoint is only for manual certificate generation by admin
        echo "SUCCESS";
    } else {
        die("CERTIFICATE_GENERATION_FAILED: " . ($result['error'] ?? 'Unknown error'));
    }
    exit;
}

/**
 * DOWNLOAD CERTIFICATE (USER)
 */
if ($action === 'download') {
    $certificate_id = $_GET['certificate_id'] ?? null;

    if (!$certificate_id) {
        die("MISSING_CERTIFICATE_ID");
    }

    $db = Database::connect();

    // Get certificate details
    $stmt = $db->prepare("
        SELECT c.*, p.user_id 
        FROM certificates c
        JOIN participants p ON c.participant_id = p.id
        WHERE c.id = ?
    ");
    $stmt->execute([$certificate_id]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificate) {
        die("CERTIFICATE_NOT_FOUND");
    }

    // Check permission (user can only download their own certificate, admin can download all)
    if ($_SESSION['user']['role'] !== 'admin' && $certificate['user_id'] != $_SESSION['user']['id']) {
        die("FORBIDDEN");
    }

    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/EventSite/public/' . $certificate['file_path'];

    if (!file_exists($file_path)) {
        die("FILE_NOT_FOUND");
    }

    // Serve file for download
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="certificate_' . $certificate['id'] . '.html"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
}

/**
 * VIEW CERTIFICATE (Direct access)
 */
if ($action === 'view') {
    $certificate_id = $_GET['certificate_id'] ?? null;

    if (!$certificate_id) {
        die("MISSING_CERTIFICATE_ID");
    }

    $db = Database::connect();

    // Get certificate details
    $stmt = $db->prepare("
        SELECT c.*, p.user_id 
        FROM certificates c
        JOIN participants p ON c.participant_id = p.id
        WHERE c.id = ?
    ");
    $stmt->execute([$certificate_id]);
    $certificate = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificate) {
        die("CERTIFICATE_NOT_FOUND");
    }

    // Check permission
    if ($_SESSION['user']['role'] !== 'admin' && $certificate['user_id'] != $_SESSION['user']['id']) {
        die("FORBIDDEN");
    }

    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/EventSite/public/' . $certificate['file_path'];

    if (!file_exists($file_path)) {
        die("FILE_NOT_FOUND");
    }

    // Serve file for viewing
    header('Content-Type: text/html');
    readfile($file_path);
    exit;
}

die("INVALID_ACTION");
