<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

class Certificate
{
    public static function create($participant_id, $file_path)
    {
        $db = Database::connect();
        $stmt = $db->prepare("
            INSERT INTO certificates (participant_id, file_path, issued_at) 
            VALUES (?, ?, NOW())
        ");
        
        if ($stmt->execute([$participant_id, $file_path])) {
            return $db->lastInsertId();
        }
        return false;
    }

    public static function getByParticipant($participant_id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM certificates WHERE participant_id = ?");
        $stmt->execute([$participant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getByUser($user_id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT 
                c.*,
                e.title as event_title,
                e.start_at,
                p.registered_at
            FROM certificates c
            JOIN participants p ON c.participant_id = p.id
            JOIN events e ON p.event_id = e.id
            WHERE p.user_id = ?
            ORDER BY c.issued_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function delete($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM certificates WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
