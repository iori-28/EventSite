<?php

/**
 * Google Calendar Auto-Add API
 * 
 * Auto-add specific event ke Google Calendar user.
 * Called dari post-registration modal.
 * 
 * Method: POST
 * Params: event_id
 * Response: JSON
 */

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/GoogleCalendarController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/CalendarService.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Event.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if (!isset($_POST['event_id'])) {
    echo json_encode(['success' => false, 'error' => 'Event ID required']);
    exit;
}

$user_id = $_SESSION['user']['id'];
$event_id = $_POST['event_id'];

// Get event data
$event = Event::getById($event_id);

if (!$event) {
    echo json_encode(['success' => false, 'error' => 'Event not found']);
    exit;
}

// Auto-add to Google Calendar
$result = CalendarService::autoAddToGoogleCalendar($user_id, $event);

echo json_encode($result);
exit;
