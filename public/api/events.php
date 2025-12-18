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
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/vendor/autoload.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/QRCodeService.php';

        // Get event details and QR token
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT e.title, e.start_at, e.location, p.qr_token 
            FROM events e
            LEFT JOIN participants p ON p.event_id = e.id AND p.user_id = ?
            WHERE e.id = ?
        ");
        $stmt->execute([$user_id, $event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        // Generate QR Code
        $qrCodeImage = '';
        if (!empty($event['qr_token'])) {
            $qrCodeImage = QRCodeService::generateQRImageTag($event['qr_token'], 250);
        }

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
        $eventDetailUrl = APP_BASE_URL . "/index.php?page=event-detail&id={$event_id}&from=email";

        $body = "<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                 <h3 style='color: #c9384a;'>✓ Pendaftaran Berhasil!</h3>
                 <p>Hai <b>{$userName}</b>,</p>
                 <p>Kamu berhasil mendaftar untuk event <b>{$event['title']}</b>.</p>
                 <p><strong>Detail Event:</strong></p>
                 <ul>
                   <li>📅 Tanggal: " . date('d M Y, H:i', strtotime($event['start_at'])) . "</li>
                   <li>📍 Lokasi: {$event['location']}</li>
                 </ul>
                 <div style='text-align: center; margin: 30px 0;'>
                   <a href='" . $eventDetailUrl . "' style='display: inline-block; background: linear-gradient(135deg, #c9384a 0%, #8b1e2e 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600;'>Lihat Detail Event</a>
                 </div>
                 <hr style='border: 1px solid #eee; margin: 30px 0;'>
                 <h4 style='text-align: center; color: #1a1a1a;'>QR Code Kehadiran</h4>
                 <div style='text-align: center; padding: 20px;'>
                   {$qrCodeImage}
                 </div>
                 <p style='text-align: center; font-size: 13px; color: #666;'>
                   Tunjukkan QR code ini kepada panitia untuk konfirmasi kehadiran Anda
                 </p>
                 <hr style='border: 1px solid #eee; margin: 30px 0;'>
                 <p style='font-size: 12px; color: #999; text-align: center;'>
                   Kami menunggu kehadiran Anda! Sampai jumpa di event.
                 </p>
                 </div>";

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

    // Get event details
    $eventStmt = $db->prepare("SELECT title FROM events WHERE id = ?");
    $eventStmt->execute([$event_id]);
    $event = $eventStmt->fetch();

    // Get requester info
    $requesterStmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $requesterStmt->execute([$_SESSION['user']['id']]);
    $requester = $requesterStmt->fetch();

    // Send notification to all admins
    $adminStmt = $db->query("SELECT id, email FROM users WHERE role = 'admin'");
    $admins = $adminStmt->fetchAll();

    require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';

    foreach ($admins as $admin) {
        $subject = "✅ Permintaan Penyelesaian Event: {$event['title']}";
        $body = "<h3>Halo Admin,</h3>
                 <p>Panitia <strong>{$requester['name']}</strong> telah mengajukan permintaan penyelesaian event.</p>
                 <h4>Detail Event:</h4>
                 <ul>
                     <li><strong>Judul:</strong> {$event['title']}</li>
                     <li><strong>Peserta Hadir:</strong> $attendedCount orang</li>
                 </ul>
                 <p><a href='http://localhost/EventSite/public/index.php?page=admin_event_completion' style='display:inline-block; padding:10px 20px; background:#c9384a; color:white; text-decoration:none; border-radius:5px;'>Review Penyelesaian</a></p>
                 <p>Silakan review dan approve penyelesaian event ini untuk menerbitkan sertifikat.</p>";

        NotificationController::createAndSend(
            $admin['id'],
            'event_completion_request',
            [
                'event_id' => $event_id,
                'event_title' => $event['title'],
                'requester_name' => $requester['name'],
                'attended_count' => $attendedCount,
                'email' => $admin['email']
            ],
            $subject,
            $body
        );
    }

    echo "SUCCESS_WAITING_ADMIN_APPROVAL";
    exit;
}
