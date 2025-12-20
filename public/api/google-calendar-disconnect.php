<?php

/**
 * Google Calendar Disconnect API
 * 
 * Disconnect user's Google Calendar connection.
 * Remove tokens dan disable auto-add.
 * 
 * Method: POST
 * Response: JSON
 */

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/GoogleCalendarController.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['id'];

$result = GoogleCalendarController::disconnect($user_id);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to disconnect']);
}

exit;
