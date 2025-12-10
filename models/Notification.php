<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

class Notification
{
    public static function create($user_id, $type, $payload, $status = 'pending')
    {
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
        $db = Database::connect();

        $stmt = $db->prepare("
            UPDATE notifications SET status = ? WHERE id = ?
        ");

        return $stmt->execute([$status, $id]);
    }

    public static function getByUser($user_id)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT * FROM notifications WHERE user_id = ?
        ");

        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
