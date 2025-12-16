<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Notification.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/NotificationService.php';

class NotificationController
{
    /**
     * Create a notification record (pending) and attempt delivery via service.
     * Returns array: ['db_id' => int, 'delivered' => bool]
     */
    public static function createAndSend($user_id, $type, $payload, $subject = '', $htmlBody = '')
    {
        // Validate inputs
        if (!is_numeric($user_id) || $user_id <= 0) {
            error_log("[NOTIF-CTRL] Invalid user_id in createAndSend: $user_id");
            return ['db_id' => null, 'delivered' => false, 'error' => 'invalid_user_id'];
        }

        error_log("[NOTIF-CTRL] Creating notification for user_id: $user_id, type: $type");

        // 1) Create notification record as pending
        $id = Notification::create($user_id, $type, $payload, 'pending');
        if (!$id) {
            error_log("[NOTIF-CTRL] Failed to create notification for user_id: $user_id, type: $type");
            return ['db_id' => null, 'delivered' => false, 'error' => 'db_insert_failed'];
        }

        error_log("[NOTIF-CTRL] Notification created with ID: $id");

        // 2) Extract email from payload if available
        $userEmail = null;
        if (is_array($payload) && isset($payload['email'])) {
            $userEmail = $payload['email'];
        }

        error_log("[NOTIF-CTRL] Target email: " . ($userEmail ?? 'none - will fetch from DB'));

        // 3) Attempt to send email via NotificationService
        $delivered = NotificationService::sendEmail($user_id, $userEmail ?? '', $subject, $htmlBody);

        error_log("[NOTIF-CTRL] Email delivery result: " . ($delivered ? 'SUCCESS' : 'FAILED'));

        // 4) Update notification status based on delivery result
        $statusUpdated = Notification::updateStatus($id, $delivered ? 'sent' : 'failed');

        if (!$statusUpdated) {
            error_log("[NOTIF-CTRL] Failed to update notification status for id: $id");
        } else {
            error_log("[NOTIF-CTRL] Notification status updated to: " . ($delivered ? 'sent' : 'failed'));
        }

        return [
            'db_id' => $id,
            'delivered' => $delivered,
            'status' => $delivered ? 'sent' : 'failed'
        ];
    }

    public static function getUnreadCount($user_id)
    {
        if (!is_numeric($user_id) || $user_id <= 0) {
            return 0;
        }

        $db = Database::connect();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$user_id]);
        return (int) $stmt->fetchColumn();
    }

    public static function getLatest($user_id, $limit = 5)
    {
        if (!is_numeric($user_id) || $user_id <= 0) {
            return [];
        }

        $limit = max(1, min((int)$limit, 50)); // Limit between 1 and 50

        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY send_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    }
}
