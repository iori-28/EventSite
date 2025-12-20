<?php

/**
 * Toggle Email Reminders API
 * 
 * API untuk toggle global email reminder preference user.
 * User bisa enable/disable semua email reminders (H-1, H-0) sekaligus.
 * 
 * Method: POST
 * Params: enabled (1 atau 0)
 * Response: JSON
 * 
 * @package EventSite\API
 * @author EventSite Team
 */

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Validate input
if (!isset($_POST['enabled'])) {
    echo json_encode(['success' => false, 'error' => 'Enabled parameter required']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$enabled = (int) $_POST['enabled']; // Convert to integer (0 or 1)

// Validate value
if ($enabled !== 0 && $enabled !== 1) {
    echo json_encode(['success' => false, 'error' => 'Invalid value. Must be 0 or 1']);
    exit;
}

try {
    $db = Database::connect();

    $stmt = $db->prepare("
        UPDATE users 
        SET email_reminders_enabled = ?
        WHERE id = ?
    ");

    $stmt->execute([$enabled, $user_id]);

    // Update session if needed (optional, for immediate reflection)
    $_SESSION['user']['email_reminders_enabled'] = $enabled;

    echo json_encode([
        'success' => true,
        'enabled' => $enabled,
        'message' => $enabled ? 'Email reminders enabled' : 'Email reminders disabled'
    ]);
} catch (Exception $e) {
    error_log("Toggle Email Reminders Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update preference'
    ]);
}
exit;
