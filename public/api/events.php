<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/EventController.php';

if (!isset($_SESSION['user'])) {
    die("NO_SESSION");
}

$action = $_POST['action'] ?? '';

/* =========================
   CREATE EVENT
   ========================= */
if ($action === 'create') {

    if ($_SESSION['user']['role'] === 'user') {
        die("FORBIDDEN");
    }

    $status = ($_SESSION['user']['role'] === 'admin') ? 'approved' : 'pending';

    $data = [
        'title' => $_POST['title'],
        'description' => $_POST['description'],
        'location' => $_POST['location'],
        'start_at' => $_POST['start_at'],
        'end_at' => $_POST['end_at'],
        'capacity' => $_POST['capacity'],
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

    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Event.php';

    $event_id = $_POST['id'];

    $event = Event::getById($event_id);

    // jalankan approve dari EventController
    $status = EventController::approve($event_id);

    if ($status) {

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
            "Event Disetujui",         // subject email
            "<b>Event kamu telah disetujui admin.</b>" // body email
        );

        header('Location: index.php?page=adm_apprv_event&msg=approved');
        exit;
    } else {
        header('Location: index.php?page=adm_apprv_event&msg=failed');
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
        // Notification payload
        $payload = [
            'event_id' => $event['id'],
            'user_id'  => $event['created_by'],
            'email'    => $event['creator_email']
        ];

        NotificationController::createAndSend(
            $event['created_by'],
            'event_rejected',
            $payload,
            "Event Ditolak",
            "<b>Mohon maaf, event kamu ditolak.</b>"
        );

        header('Location: index.php?page=adm_apprv_event&msg=rejected');
        exit;
    } else {
        header('Location: index.php?page=adm_apprv_event&msg=failed');
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
        echo json_encode(["status" => "EVENT_DELETED"]);
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
