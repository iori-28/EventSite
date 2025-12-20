<?php

/**
 * Google Calendar Toggle Auto-Add API
 * 
 * Enable/disable auto-add preference tanpa disconnect calendar.
 * 
 * Method: POST
 * Params: enabled (1 or 0)
 * Response: JSON
 */

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/GoogleCalendarController.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!isset($_POST['enabled'])) {
    echo json_encode(['success' => false, 'error' => 'Enabled parameter required']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$enabled = $_POST['enabled'] == '1';

$result = GoogleCalendarController::setAutoAdd($user_id, $enabled);

if ($result) {
    echo json_encode(['success' => true, 'enabled' => $enabled]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update preference']);
}

exit;
