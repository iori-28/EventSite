<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/EventController.php';

if (!isset($_SESSION['user'])) {
    die("NO_SESSION");
}

$action = $_POST['action'] ?? '';

/* =========================
   APPROVE EVENT (ADMIN)
   ========================= */
if ($action === 'approve') {

    if ($_SESSION['user']['role'] !== 'admin') {
        die("ONLY_ADMIN");
    }

    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Event.php';

    $event_id = $_POST['id'];

    $event = Event::getById($event_id);

    // jalankan approve dari EventController
    $status = EventController::approve($event_id);

    if ($status) {

        // Load email template
        $template = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/EventSite/templates/emails/event_approved_template.php');

        // Replace placeholders
        $template = str_replace('{{organizer_name}}', $event['creator_name'], $template);
        $template = str_replace('{{event_title}}', $event['title'], $template);
        $template = str_replace('{{event_location}}', $event['location'], $template);
        $template = str_replace('{{event_datetime}}', date('l, d F Y - H:i', strtotime($event['start_at'])) . ' WIB', $template);
        $template = str_replace('{{event_capacity}}', $event['capacity'], $template);
        $template = str_replace('{{event_manage_url}}', 'http://localhost/EventSite/public/index.php?page=panitia_my_events', $template);
        $template = str_replace('{{event_detail_url}}', 'http://localhost/EventSite/public/index.php?page=event-detail&id=' . $event['id'], $template);

        // --- buat payload untuk dicatat ke notifications ---
        $payload = [
            'event_id' => $event['id'],
            'user_id'  => $event['created_by'],
            'email'    => $event['creator_email']
        ];

        // --- kirim & catat notifikasi ---
        $notif = NotificationController::createAndSend(
            $event['created_by'],      // user yang dibuatkan notifikasi
            'event_approved',          // type notifikasi
            $payload,                  // payload JSON
            "✅ Event Disetujui - " . $event['title'],  // subject email
            $template                  // body email (HTML template)
        );

        header('Location: ../index.php?page=adm_apprv_event&msg=approved');
        exit;
    } else {
        header('Location: ../index.php?page=adm_apprv_event&msg=failed');
        exit;
    }
}

/* =========================
   REJECT EVENT (ADMIN)
   ========================= */
if ($action === 'reject') {
    if ($_SESSION['user']['role'] !== 'admin') die("ONLY_ADMIN");

    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Event.php';

    $event_id = $_POST['id'];
    $event = Event::getById($event_id);

    if (EventController::reject($event_id)) {
        // Load email template
        $template = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/EventSite/templates/emails/event_rejected_template.php');

        // Replace placeholders
        $template = str_replace('{{organizer_name}}', $event['creator_name'], $template);
        $template = str_replace('{{event_title}}', $event['title'], $template);
        $template = str_replace('{{rejection_reason}}', $_POST['reason'] ?? 'Event tidak memenuhi kriteria yang ditentukan.', $template);
        $template = str_replace('{{edit_event_url}}', 'http://localhost/EventSite/public/index.php?page=panitia_edit_event&id=' . $event['id'], $template);

        // Notification payload
        $payload = [
            'event_id' => $event['id'],
            'user_id'  => $event['created_by'],
            'email'    => $event['creator_email'],
            'reason'   => $_POST['reason'] ?? 'Event tidak memenuhi kriteria'
        ];

        NotificationController::createAndSend(
            $event['created_by'],
            'event_rejected',
            $payload,
            "❌ Event Ditolak - " . $event['title'],
            $template
        );

        header('Location: ../index.php?page=adm_apprv_event&msg=rejected');
        exit;
    } else {
        header('Location: ../index.php?page=adm_apprv_event&msg=failed');
        exit;
    }
}

/* =========================
   DELETE EVENT (ADMIN)
   ========================= */
if ($action === 'delete') {
    if ($_SESSION['user']['role'] !== 'admin') die("ONLY_ADMIN");

    $event_id = $_POST['id'];
    if (EventController::delete($event_id)) {
        // Check if from manage_events page
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'admin_manage_events') !== false) {
            header('Location: ../index.php?page=admin_manage_events&msg=deleted');
        } else {
            header('Location: ../index.php?page=adm_apprv_event&msg=deleted');
        }
        exit;
    } else {
        header('Location: ../index.php?page=admin_manage_events&msg=failed');
        exit;
    }
}

/* =========================
   REGISTER EVENT (USER)
   ========================= */
if ($action === 'register') {

    if ($_SESSION['user']['role'] !== 'user') {
        die("ONLY_USER");
    }

    $user_id  = $_SESSION['user']['id'];
    $event_id = $_POST['event_id'];

    $result = EventController::register($user_id, $event_id);

    if ($result === "REGISTER_SUCCESS") {

        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Event.php';

        // Get event details
        $event = Event::getById($event_id);

        // Load email template
        $template = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/EventSite-main/EventSite-main/templates/emails/registration_confirmation_template.php');

        // Replace placeholders
        $template = str_replace('{{participant_name}}', $_SESSION['user']['name'], $template);
        $template = str_replace('{{event_title}}', $event['title'], $template);
        $template = str_replace('{{event_location}}', $event['location'], $template);
        $template = str_replace('{{event_datetime}}', date('l, d F Y - H:i', strtotime($event['start_at'])) . ' WIB', $template);
        $template = str_replace('{{event_description}}', $event['description'], $template);
        $template = str_replace('{{event_detail_url}}', 'http://localhost/EventSite/public/index.php?page=event-detail&id=' . $event_id, $template);

        // prepare payload & email
        $email = $_SESSION['user']['email'] ?? '';
        $payload = [
            'event_id' => $event_id,
            'user_id' => $user_id,
            'email' => $email,
            'event_title' => $event['title']
        ];

        $subject = "✅ Pendaftaran Event Berhasil - " . $event['title'];

        $notifResult = NotificationController::createAndSend($user_id, 'registration', $payload, $subject, $template);

        // optional: include notifResult in response for debugging
        echo json_encode(['result' => "REGISTER_SUCCESS", 'notif' => $notifResult]);
    } else {
        // return original result (EVENT_FULL, NOT_APPROVED, etc)
        echo $result;
    }
}
