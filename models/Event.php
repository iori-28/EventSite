<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

class Event
{
    public static function create($data)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            INSERT INTO events 
            (title, description, location, start_at, end_at, capacity, organization_id, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['location'],
            $data['start_at'],
            $data['end_at'],
            $data['capacity'],
            $data['organization_id'],
            $data['status'],
            $data['created_by']
        ]);
    }

    public static function getApproved()
    {
        $db = Database::connect();
        return $db->query("SELECT * FROM events WHERE status = 'approved'")->fetchAll();
    }

    public static function getByOrg($org_id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("SELECT * FROM events WHERE organization_id = ?");
        $stmt->execute([$org_id]);
        return $stmt->fetchAll();
    }

    public static function approve($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function cancel($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function register($user_id, $event_id)
    {
        $db = Database::connect();

        //  Cek kapasitas event
        $stmt = $db->prepare("SELECT capacity, status FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();

        if (!$event || $event['status'] !== 'approved') {
            return "EVENT_NOT_APPROVED";
        }

        if ($event['capacity'] <= 0) {
            return "EVENT_FULL";
        }

        //  Insert participant
        $insert = $db->prepare("
        INSERT INTO participants (user_id, event_id)
        VALUES (?, ?)
        ");

        if (!$insert->execute([$user_id, $event_id])) {
            return "REGISTER_FAILED";
        }

        //  Kurangi kapasitas event
        $update = $db->prepare("
        UPDATE events SET capacity = capacity - 1 WHERE id = ?
        ");
        $update->execute([$event_id]);

        return "REGISTER_SUCCESS";
    }

    public static function getById($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("
        SELECT events.*, users.email AS creator_email
        FROM events
        JOIN users ON users.id = events.created_by
        WHERE events.id = ?
    ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
