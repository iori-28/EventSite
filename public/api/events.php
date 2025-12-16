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
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

        // Get event details for better notification
        $db = Database::connect();
        $stmt = $db->prepare("SELECT title, start_at, location FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        // prepare payload & email
        $email = $_SESSION['user']['email'] ?? '';
        $userName = $_SESSION['user']['name'] ?? 'User';
        $payload = [
            'event_id' => $event_id,
            'user_id' => $user_id,
            'email' => $email,
            'event_title' => $event['title'] ?? 'Event'
        ];

        $subject = "Registrasi Event Berhasil - {$event['title']}";
        $body = "<h3>Pendaftaran Berhasil!</h3>
                 <p>Hai <b>{$userName}</b>,</p>
                 <p>Kamu berhasil mendaftar untuk event <b>{$event['title']}</b>.</p>
                 <p><strong>Detail Event:</strong></p>
                 <ul>
                   <li>Tanggal: " . date('d M Y, H:i', strtotime($event['start_at'])) . "</li>
                   <li>Lokasi: {$event['location']}</li>
                 </ul>
                 <p>Kami menunggu kehadiran Anda!</p>";

        $notifResult = NotificationController::createAndSend($user_id, 'registration', $payload, $subject, $body);

        // Return success with notification status
        echo json_encode([
            'result' => "REGISTER_SUCCESS",
            'notif_sent' => $notifResult['delivered'],
            'notif_id' => $notifResult['db_id']
        ]);
    } else {
        // return original result (EVENT_FULL, NOT_APPROVED, etc)
        echo $result;
    }
}

/* =========================
   COMPLETE EVENT (PANITIA)
   ========================= */
if ($action === 'complete') {

    if ($_SESSION['user']['role'] !== 'panitia') {
        die("ONLY_PANITIA");
    }

    $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

    if (!$event_id || $event_id <= 0) {
        die("INVALID_EVENT_ID");
    }

    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/CertificateController.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';

    $db = Database::connect();

    // Verify event ownership and status
    $stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND created_by = ?");
    $stmt->execute([$event_id, $_SESSION['user']['id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("EVENT_NOT_FOUND_OR_NO_PERMISSION");
    }

    if ($event['status'] !== 'approved') {
        die("EVENT_NOT_APPROVED");
    }

    if ($event['status'] === 'completed') {
        die("ALREADY_COMPLETED");
    }


    // Check if there are any checked-in participants
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM participants WHERE event_id = ? AND status = 'checked_in'");
    $checkStmt->execute([$event_id]);
    $attendedCount = $checkStmt->fetchColumn();

    if ($attendedCount == 0) {
        die("NO_ATTENDED_PARTICIPANTS");
    }

    // Update event status to waiting_completion (waiting for admin approval)
    $updateStmt = $db->prepare("
        UPDATE events 
        SET status = 'waiting_completion', 
            completed_by = ?, 
            completed_at = NOW() 
        WHERE id = ?
    ");
    $updateStmt->execute([$_SESSION['user']['id'], $event_id]);

    // DO NOT generate certificates yet - wait for admin approval
    // Certificates will be generated when admin approves completion

    echo "SUCCESS_WAITING_ADMIN_APPROVAL";
    exit;
}
