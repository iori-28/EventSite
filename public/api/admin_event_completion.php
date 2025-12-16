<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/CertificateController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/NotificationController.php';

// Check admin role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized - Admin only']));
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

if (!$event_id || $event_id <= 0) {
    die(json_encode(['success' => false, 'message' => 'Invalid event ID']));
}

$db = Database::connect();

/* =========================
   APPROVE EVENT COMPLETION
   ========================= */
if ($action === 'approve_completion') {
    try {
        // Get event details
        $stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND status = 'waiting_completion'");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            die(json_encode(['success' => false, 'message' => 'Event not found or not waiting for approval']));
        }

        // Begin transaction
        $db->beginTransaction();

        // Update event status to completed
        $updateStmt = $db->prepare("
            UPDATE events 
            SET status = 'completed', 
                approved_by = ?, 
                approved_at = NOW() 
            WHERE id = ?
        ");
        $updateStmt->execute([$_SESSION['user']['id'], $event_id]);

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
        $errors = [];

        // Generate certificates and send notifications
        foreach ($participants as $participant) {
            try {
                // Log participant processing
                error_log("[CERT-GEN] Processing participant: {$participant['name']} (ID: {$participant['participant_id']})");

                // Generate certificate
                $certResult = CertificateController::generate($participant['participant_id']);

                // Log certificate result
                error_log("[CERT-GEN] Certificate result: " . json_encode($certResult));

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

                    error_log("[NOTIF-SEND] Sending notification to: {$participant['email']}");

                    $notifResult = NotificationController::createAndSend(
                        $participant['user_id'],
                        'certificate_issued',
                        $payload,
                        $subject,
                        $body
                    );

                    // Log notification result
                    error_log("[NOTIF-SEND] Notification result: " . json_encode($notifResult));

                    if ($notifResult['delivered']) {
                        $successCount++;
                        error_log("[SUCCESS] Certificate and notification sent to: {$participant['name']}");
                    } else {
                        $errorCount++;
                        $errorMsg = "Failed to send notification to {$participant['name']} - Status: {$notifResult['status']}";
                        $errors[] = $errorMsg;
                        error_log("[ERROR] $errorMsg");
                    }
                } else {
                    $errorCount++;
                    $errorMsg = "Failed to generate certificate for {$participant['name']} - Error: " . ($certResult['error'] ?? 'Unknown');
                    $errors[] = $errorMsg;
                    error_log("[ERROR] $errorMsg");
                }
            } catch (Exception $e) {
                $errorCount++;
                $errorMsg = "Error for {$participant['name']}: " . $e->getMessage();
                $errors[] = $errorMsg;
                error_log("[EXCEPTION] $errorMsg - Trace: " . $e->getTraceAsString());
            }
        }

        // Commit transaction
        $db->commit();

        // Return result
        echo json_encode([
            'success' => true,
            'message' => "$successCount certificates generated and sent",
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors,
            'total_participants' => count($participants)
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Event completion approval error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }

    exit;
}

/* =========================
   REJECT EVENT COMPLETION
   ========================= */
if ($action === 'reject_completion') {
    $reason = $_POST['reason'] ?? 'Completion rejected by admin';

    try {
        // Revert status back to approved
        $stmt = $db->prepare("
            UPDATE events 
            SET status = 'approved', 
                completed_by = NULL, 
                completed_at = NULL 
            WHERE id = ? AND status = 'waiting_completion'
        ");
        $success = $stmt->execute([$event_id]);

        if ($success && $stmt->rowCount() > 0) {
            // TODO: Send notification to panitia about rejection
            echo json_encode([
                'success' => true,
                'message' => 'Event completion rejected'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Event not found or not waiting for approval'
            ]);
        }
    } catch (Exception $e) {
        error_log("Event completion rejection error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }

    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action']);
