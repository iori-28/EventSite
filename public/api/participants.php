<?php
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
        header('Location: /EventSite/public/index.php?page=user_browse_events&msg=not_approved');
    } elseif ($result === "FULL") {
        header('Location: /EventSite/public/index.php?page=user_browse_events&msg=event_full');
    } elseif ($result === "ALREADY_REGISTERED") {
        header('Location: /EventSite/public/index.php?page=user_browse_events&msg=already_registered');
    } elseif ($result === true) {

        // Send registration confirmation notification
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Event.php';

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

        header('Location: /EventSite/public/index.php?page=user_browse_events&msg=success');
    } else {
        header('Location: /EventSite/public/index.php?page=user_browse_events&msg=failed');
    }
    exit;
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
