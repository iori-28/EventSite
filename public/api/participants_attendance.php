<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

// Check panitia role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'panitia') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

header('Content-Type: application/json');

$db = Database::connect();
$user_id = $_SESSION['user']['id'];

// Get request data
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);

$action = $_POST['action'] ?? $json_data['action'] ?? '';

/* =========================
   SINGLE UPDATE STATUS
   ========================= */
if ($action === 'update_status') {
    $participant_id = filter_var($_POST['participant_id'] ?? null, FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? '';

    if (!$participant_id || !in_array($status, ['registered', 'checked_in', 'cancelled'])) {
        die(json_encode(['success' => false, 'message' => 'Invalid input']));
    }

    try {
        // Verify ownership - panitia can only update their own event's participants
        $check = $db->prepare("
            SELECT p.id 
            FROM participants p 
            JOIN events e ON p.event_id = e.id 
            WHERE p.id = ? AND e.created_by = ?
        ");
        $check->execute([$participant_id, $user_id]);

        if (!$check->fetch()) {
            die(json_encode(['success' => false, 'message' => 'Participant not found or no permission']));
        }

        // Update status
        $stmt = $db->prepare("UPDATE participants SET status = ? WHERE id = ?");
        $success = $stmt->execute([$status, $participant_id]);

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Status updated successfully' : 'Failed to update status'
        ]);
    } catch (PDOException $e) {
        error_log("Error updating attendance: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

    exit;
}

/* =========================
   BULK UPDATE STATUS
   ========================= */
if ($action === 'bulk_update') {
    $participant_ids = $json_data['participant_ids'] ?? [];
    $status = $json_data['status'] ?? '';

    if (empty($participant_ids) || !is_array($participant_ids) || !in_array($status, ['registered', 'checked_in', 'cancelled'])) {
        die(json_encode(['success' => false, 'message' => 'Invalid input']));
    }

    // Sanitize IDs
    $participant_ids = array_map('intval', $participant_ids);
    $participant_ids = array_filter($participant_ids, fn($id) => $id > 0);

    if (empty($participant_ids)) {
        die(json_encode(['success' => false, 'message' => 'No valid participant IDs']));
    }

    try {
        $db->beginTransaction();

        $placeholders = implode(',', array_fill(0, count($participant_ids), '?'));

        // Verify ownership
        $check = $db->prepare("
            SELECT p.id 
            FROM participants p 
            JOIN events e ON p.event_id = e.id 
            WHERE p.id IN ($placeholders) AND e.created_by = ?
        ");
        $check->execute([...$participant_ids, $user_id]);
        $valid_ids = $check->fetchAll(PDO::FETCH_COLUMN);

        if (empty($valid_ids)) {
            $db->rollBack();
            die(json_encode(['success' => false, 'message' => 'No valid participants found']));
        }

        // Update status for valid IDs
        $update_placeholders = implode(',', array_fill(0, count($valid_ids), '?'));
        $stmt = $db->prepare("UPDATE participants SET status = ? WHERE id IN ($update_placeholders)");
        $stmt->execute([$status, ...$valid_ids]);

        $updated_count = $stmt->rowCount();

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Bulk update successful',
            'updated_count' => $updated_count,
            'requested_count' => count($participant_ids)
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Bulk attendance update error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

    exit;
}

/* =========================
   VERIFY QR CODE
   ========================= */
if ($action === 'verify_qr') {
    $qr_token = $_POST['qr_token'] ?? '';

    if (empty($qr_token)) {
        die(json_encode(['success' => false, 'message' => 'QR token is required']));
    }

    try {
        // Find participant by QR token and verify panitia owns the event
        $stmt = $db->prepare("
            SELECT p.id, p.status, u.name as participant_name, e.title as event_title
            FROM participants p
            JOIN events e ON p.event_id = e.id
            JOIN users u ON p.user_id = u.id
            WHERE p.qr_token = ? AND e.created_by = ?
        ");
        $stmt->execute([$qr_token, $user_id]);
        $participant = $stmt->fetch();

        if (!$participant) {
            die(json_encode(['success' => false, 'message' => 'QR Code tidak valid atau bukan event Anda']));
        }

        // Check if already checked in
        if ($participant['status'] === 'checked_in') {
            echo json_encode([
                'success' => false,
                'message' => 'Peserta sudah melakukan check-in sebelumnya',
                'participant_name' => $participant['participant_name']
            ]);
            exit;
        }

        // Update to checked_in
        $update = $db->prepare("UPDATE participants SET status = 'checked_in' WHERE id = ?");
        $success = $update->execute([$participant['id']]);

        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Kehadiran berhasil dikonfirmasi' : 'Gagal konfirmasi kehadiran',
            'participant_name' => $participant['participant_name'],
            'event_title' => $participant['event_title']
        ]);
    } catch (PDOException $e) {
        error_log("QR verification error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action']);
