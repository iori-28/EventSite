<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/EventController.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

// Check admin role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$action = $_POST['bulk_action'] ?? '';
$event_ids = $_POST['event_ids'] ?? [];

// Validate inputs
if (empty($action)) {
    die(json_encode(['success' => false, 'message' => 'No action specified']));
}

if (empty($event_ids) || !is_array($event_ids)) {
    die(json_encode(['success' => false, 'message' => 'No events selected']));
}

// Sanitize event IDs
$event_ids = array_map('intval', $event_ids);
$event_ids = array_filter($event_ids, fn($id) => $id > 0);

if (empty($event_ids)) {
    die(json_encode(['success' => false, 'message' => 'Invalid event IDs']));
}

$db = Database::connect();
$success_count = 0;
$error_count = 0;
$errors = [];

try {
    // Begin transaction
    $db->beginTransaction();

    foreach ($event_ids as $event_id) {
        try {
            switch ($action) {
                case 'approve':
                    if (EventController::approve($event_id)) {
                        // Send notification (optional - implement if needed)
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Failed to approve event ID: $event_id";
                    }
                    break;

                case 'reject':
                    if (EventController::reject($event_id)) {
                        // Send notification (optional)
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Failed to reject event ID: $event_id";
                    }
                    break;

                case 'delete':
                    if (EventController::delete($event_id)) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Failed to delete event ID: $event_id";
                    }
                    break;

                default:
                    $error_count++;
                    $errors[] = "Unknown action: $action";
            }
        } catch (Exception $e) {
            $error_count++;
            $errors[] = "Error processing event ID $event_id: " . $e->getMessage();
            error_log("Bulk action error for event $event_id: " . $e->getMessage());
        }
    }

    // Commit transaction
    $db->commit();

    // Prepare response
    $response = [
        'success' => ($error_count === 0),
        'message' => "$success_count event(s) processed successfully",
        'success_count' => $success_count,
        'error_count' => $error_count
    ];

    if ($error_count > 0) {
        $response['message'] .= ", $error_count failed";
        $response['errors'] = $errors;
    }

    // Redirect back with message
    $msg = $error_count === 0 ? 'bulk_success' : 'bulk_partial';
    header("Location: ../index.php?page=admin_manage_events&msg=$msg&success=$success_count&failed=$error_count");
    exit;
} catch (Exception $e) {
    // Rollback on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log("Bulk action transaction error: " . $e->getMessage());
    header("Location: ../index.php?page=admin_manage_events&msg=bulk_error");
    exit;
}
