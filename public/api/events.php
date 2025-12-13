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

    // Check if event has ended
    if (strtotime($event['end_at']) > time()) {
        die("EVENT_NOT_ENDED_YET");
    }

    // Update event status to completed
    $updateStmt = $db->prepare("UPDATE events SET status = 'completed' WHERE id = ?");
    $updateStmt->execute([$event_id]);

    // Get all participants with checked_in status
    $participantsStmt = $db->prepare("
        SELECT p.id as participant_id, p.user_id, u.name, u.email
        FROM participants p
        JOIN users u ON p.user_id = u.id
        WHERE p.event_id = ? AND p.status = 'checked_in'
    ");
    $participantsStmt->execute([$event_id]);
    $participants = $participantsStmt->fetchAll(PDO::FETCH_ASSOC);

    $successCount = 0;
    $errorCount = 0;

    // Generate certificates and send notifications for each participant
    foreach ($participants as $participant) {
        // Generate certificate
        $certResult = CertificateController::generate($participant['participant_id']);

        if ($certResult['success']) {
            // Send notification with certificate
            $payload = [
                'participant_id' => $participant['participant_id'],
                'certificate_id' => $certResult['certificate_id'],
                'event_id' => $event_id,
                'event_title' => $event['title'],
                'email' => $participant['email']
            ];

            $subject = "🎉 Sertifikat Event \"{$event['title']}\" Telah Diterbitkan";
            $body = "<h3>Selamat, {$participant['name']}!</h3>
                     <p>Event <strong>{$event['title']}</strong> telah selesai dan Anda telah terdaftar sebagai peserta yang hadir.</p>
                     <p>Sertifikat kehadiran Anda telah diterbitkan dan dapat diunduh melalui dashboard.</p>
                     <p><a href='http://localhost/EventSite/public/index.php?page=user_certificates' style='display:inline-block; padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px;'>Lihat Sertifikat</a></p>
                     <p>Terima kasih atas partisipasi Anda!</p>";

            $notifResult = NotificationController::createAndSend(
                $participant['user_id'],
                'certificate_issued',
                $payload,
                $subject,
                $body
            );

            if ($notifResult['delivered']) {
                $successCount++;
            } else {
                $errorCount++;
            }
        } else {
            $errorCount++;
            error_log("Failed to generate certificate for participant_id: {$participant['participant_id']}");
        }
    }

    // Return result
    if ($errorCount === 0) {
        echo "SUCCESS";
    } else {
        echo "PARTIAL_SUCCESS: $successCount certificates sent, $errorCount failed";
    }
    exit;
}
