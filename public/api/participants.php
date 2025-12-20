<?php

/**
 * Participants API Endpoint
 * 
 * RESTful API untuk mengelola participant registrations.
 * Mendukung actions: register, cancel, get_user_events, get_all
 * 
 * Features:
 * - Event registration dengan QR token generation
 * - Cancel registration dengan capacity increment
 * - Query user's registered events
 * - Get all participants (admin/panitia only)
 * 
 * Authentication: Required (session-based)
 * Authorization: User untuk register/cancel, Admin/Panitia untuk get_all
 * 
 * Response Format: JSON or plain text status codes
 * 
 * @package EventSite\API
 * @author EventSite Team
 * @version 2.0
 */

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/ParticipantController.php';

if (!isset($_SESSION['user'])) {
    die("NO_SESSION");
}

$action = $_POST['action'] ?? '';

/* =========================
   REGISTER USER TO EVENT
   ========================= */
if ($action === 'register') {

    $user_id  = $_SESSION['user']['id'];
    $event_id = $_POST['event_id'];

    $result = ParticipantController::register($user_id, $event_id);

    if ($result === "NOT_APPROVED") {
        die("EVENT_NOT_APPROVED");
    } elseif ($result === "FULL") {
        die("EVENT_FULL");
    } elseif ($result === "ALREADY_REGISTERED") {
        die("ALREADY_REGISTERED");
    } elseif ($result === true) {

        // Send registration confirmation notification
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Event.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/GoogleCalendarController.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/CalendarService.php';

        $event = Event::getById($event_id);

        // Load email template
        $template = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/EventSite/templates/emails/registration_confirmation_template.php');

        // Build full image URL if event has image
        $event_image_url = '';
        if (!empty($event['event_image'])) {
            $event_image_url = 'http://localhost/EventSite/public/' . $event['event_image'];
        }

        // Replace placeholders
        $template = str_replace('{{participant_name}}', $_SESSION['user']['name'], $template);
        $template = str_replace('{{event_title}}', $event['title'], $template);
        $template = str_replace('{{event_location}}', $event['location'], $template);
        $template = str_replace('{{event_datetime}}', date('l, d F Y - H:i', strtotime($event['start_at'])) . ' WIB', $template);
        $template = str_replace('{{event_description}}', $event['description'], $template);
        $template = str_replace('{{event_detail_url}}', 'http://localhost/EventSite/public/index.php?page=event-detail&id=' . $event_id, $template);
        $template = str_replace('{{event_image}}', $event_image_url, $template);

        $payload = [
            'event_id' => $event_id,
            'user_id' => $user_id,
            'email' => $_SESSION['user']['email'],
            'event_title' => $event['title']
        ];

        $subject = "✅ Pendaftaran Event Berhasil - " . $event['title'];

        NotificationController::createAndSend($user_id, 'registration', $payload, $subject, $template);

        // Check if user has auto-add enabled
        $auto_added = false;
        if (GoogleCalendarController::isAutoAddEnabled($user_id)) {
            // Try to auto-add to Google Calendar
            $calendar_result = CalendarService::autoAddToGoogleCalendar($user_id, $event);
            $auto_added = $calendar_result['success'];
        }

        // Get calendar connection info for modal
        $calendar_info = GoogleCalendarController::getConnectionInfo($user_id);

        // Return JSON response dengan event data untuk modal
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'REGISTER_SUCCESS',
            'auto_added' => $auto_added,
            'event' => [
                'id' => $event_id,
                'title' => $event['title']
            ],
            'calendar' => [
                'connected' => $calendar_info['connected'],
                'auto_add_enabled' => $calendar_info['auto_add']
            ]
        ]);
        exit;
    } else {
        die("REGISTER_FAILED");
    }
}

/* =========================
   CANCEL REGISTRATION
   ========================= */
if ($action === 'cancel') {

    $user_id  = $_SESSION['user']['id'];
    $event_id = $_POST['event_id'];

    echo ParticipantController::cancel($user_id, $event_id)
        ? "CANCEL_SUCCESS"
        : "CANCEL_FAILED";
}

/* =========================
   GET USER EVENTS
   ========================= */
if ($action === 'my-events') {

    echo json_encode(
        ParticipantController::getByUser($_SESSION['user']['id'])
    );
}
