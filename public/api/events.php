<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/EventController.php';

if (!isset($_SESSION['user'])) {
    die("NO_SESSION");
}

$action = $_POST['action'] ?? '';

/* =========================
   CREATE EVENT (PANITIA)
   ========================= */
if ($action === 'create') {

    if ($_SESSION['user']['role'] === 'user') {
        die("FORBIDDEN");
    }

    $organization_id = $_SESSION['user']['organization_id'];

    if (!$organization_id) {
        die("NO_ORGANIZATION");
    }

    $status = ($_SESSION['user']['role'] === 'admin') ? 'approved' : 'pending';

    $data = [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'location' => $_POST['location'],
        'start_at' => $_POST['start_at'],
        'end_at' => $_POST['end_at'],
        'capacity' => $_POST['capacity'],
        'organization_id' => $_POST['organization_id'],
        'status' => $status,
        'created_by' => $_SESSION['user']['id']
    ];

    echo EventController::create($data)
        ? "EVENT_CREATED"
        : "EVENT_FAILED";
}

/* =========================
   GET APPROVED EVENTS (USER)
   ========================= */
if ($action === 'list') {
    echo json_encode(EventController::getApproved());
}

/* =========================
   APPROVE EVENT (ADMIN)
   ========================= */
if ($action === 'approve') {

    if ($_SESSION['user']['role'] !== 'admin') {
        die("ONLY_ADMIN");
    }

    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/NotificationService.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Event.php';

    $event_id = $_POST['id'];

    $event = Event::getById($event_id);

    $status = EventController::approve($event_id);

    if ($status) {
        NotificationService::sendEmail(
            $event['created_by'],
            $event['creator_email'],
            "Event Disetujui",
            "<b>Event kamu telah disetujui admin.</b>"
        );

        echo "EVENT_APPROVED";
    } else {
        echo "FAILED";
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

        // prepare payload & email
        $email = $_SESSION['user']['email'] ?? '';
        $payload = [
            'event_id' => $event_id,
            'user_id' => $user_id,
            'email' => $email
        ];

        $subject = "Registrasi Event Berhasil";
        $body = "<h3>Pendaftaran Berhasil</h3><p>Kamu berhasil mendaftar event (ID: {$event_id}).</p>";

        $notifResult = NotificationController::createAndSend($user_id, 'registration', $payload, $subject, $body);

        // optional: include notifResult in response for debugging
        echo json_encode(['result' => "REGISTER_SUCCESS", 'notif' => $notifResult]);
    } else {
        // return original result (EVENT_FULL, NOT_APPROVED, etc)
        echo $result;
    }
}
