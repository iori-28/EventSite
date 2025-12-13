<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

class Participant
{
    public static function register($user_id, $event_id)
    {
        // Validate inputs
        if (!is_numeric($user_id) || $user_id <= 0 || !is_numeric($event_id) || $event_id <= 0) {
            return "INVALID_INPUT";
        }

        $db = Database::connect();

        // ✅ Ambil data event + status
        $stmt = $db->prepare("
        SELECT status, capacity,
        (SELECT COUNT(*) FROM participants WHERE event_id = ?) AS total
        FROM events WHERE id = ?
        ");
        $stmt->execute([$event_id, $event_id]);
        $event = $stmt->fetch();

        // ✅ BLOKIR JIKA BELUM APPROVED
        if ($event['status'] !== 'approved') {
            return "NOT_APPROVED";
        }

        // ✅ CEK KAPASITAS
        if ($event['total'] >= $event['capacity']) {
            return "FULL";
        }

        // ✅ CEK SUDAH TERDAFTAR BELUM
        $stmt = $db->prepare("
        SELECT id FROM participants
        WHERE user_id = ? AND event_id = ?
        ");
        $stmt->execute([$user_id, $event_id]);

        if ($stmt->fetch()) {
            return "ALREADY_REGISTERED";
        }

        // ✅ REGISTER
        $stmt = $db->prepare("
        INSERT INTO participants (user_id, event_id)
        VALUES (?, ?)
        ");

        return $stmt->execute([$user_id, $event_id]);
    }


    public static function cancel($user_id, $event_id)
    {
        // Validate inputs
        if (!is_numeric($user_id) || $user_id <= 0 || !is_numeric($event_id) || $event_id <= 0) {
            return false;
        }

        $db = Database::connect();
        $stmt = $db->prepare("
            DELETE FROM participants
            WHERE user_id = ? AND event_id = ?
        ");
        return $stmt->execute([$user_id, $event_id]);
    }

    public static function getByUser($user_id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("
            SELECT e.*
            FROM events e
            JOIN participants p ON e.id = p.event_id
            WHERE p.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}
