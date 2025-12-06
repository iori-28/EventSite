<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

class Notification
{
    public static function create($user_id, $type, $payload, $status = 'pending')
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, payload, status, send_at)
            VALUES (?, ?, ?, ?, NULL)
        ");

        // payload simpan sebagai JSON string
        $payload_json = is_string($payload) ? $payload : json_encode($payload);

        $ok = $stmt->execute([$user_id, $type, $payload_json, $status]);

        if ($ok) {
            return $db->lastInsertId();
        }

        return false;
    }

    public static function updateStatus($id, $status)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            UPDATE notifications SET status = ?, send_at = NOW() WHERE id = ?
        ");

        return $stmt->execute([$status, $id]);
    }

    public static function getPending()
    {
        $db = Database::connect();
        return $db->query("SELECT * FROM notifications WHERE status = 'pending'")->fetchAll();
    }
}
