<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

class Notification
{
    public static function create($user_id, $type, $payload, $status = 'pending')
    {
        // Validate inputs
        if (!is_numeric($user_id) || $user_id <= 0) {
            error_log("Invalid user_id in Notification::create: $user_id");
            return false;
        }

        if (empty($type)) {
            error_log("Empty type in Notification::create for user_id: $user_id");
            return false;
        }

        $db = Database::connect();

        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, payload, status, send_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        if ($stmt->execute([
            $user_id,
            $type,
            json_encode($payload),  // payload harus JSON
            $status
        ])) {
            return $db->lastInsertId();
        }

        return false;
    }

    public static function updateStatus($id, $status)
    {
        // Validate inputs
        if (!is_numeric($id) || $id <= 0) {
            error_log("Invalid id in Notification::updateStatus: $id");
            return false;
        }

        if (!in_array($status, ['pending', 'sent', 'failed'])) {
            error_log("Invalid status in Notification::updateStatus: $status");
            return false;
        }

        $db = Database::connect();

        $stmt = $db->prepare("
            UPDATE notifications SET status = ? WHERE id = ?
        ");

        return $stmt->execute([$status, $id]);
    }

    public static function getByUser($user_id)
    {
        // Validate input
        if (!is_numeric($user_id) || $user_id <= 0) {
            return [];
        }

        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT * FROM notifications WHERE user_id = ?
        ");

        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
