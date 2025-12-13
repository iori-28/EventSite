<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/NotificationService.php';

class Event
{
    public static function create($data)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            INSERT INTO events 
            (title, description, location, start_at, end_at, capacity, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['location'],
            $data['start_at'],
            $data['end_at'],
            $data['capacity'],
            $data['status'],
            $data['created_by']
        ]);
    }

    public static function getApproved()
    {
        $db = Database::connect();
        return $db->query("SELECT * FROM events WHERE status = 'approved'")->fetchAll();
    }


    public static function approve($id)
    {
        // Validate input
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }

        $db = Database::connect();

        // ambil data event + pembuat event
        $stmt = $db->prepare("
        SELECT events.title, users.id AS user_id, users.email
        FROM events
        JOIN users ON users.id = events.created_by
        WHERE events.id = ?
    ");
        $stmt->execute([$id]);
        $event = $stmt->fetch();

        if (!$event) {
            return false;
        }

        // update status event
        $update = $db->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
        $update->execute([$id]);

        // Notification handled by controller via NotificationController::createAndSend
        return true;
    }


    public static function cancel($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function reject($id)
    {
        $db = Database::connect();

        // ambil data event + pembuat event
        $stmt = $db->prepare("
            SELECT events.title, users.id AS user_id, users.email
            FROM events
            JOIN users ON users.id = events.created_by
            WHERE events.id = ?
        ");
        $stmt->execute([$id]);
        $event = $stmt->fetch();

        if (!$event) return false;

        $update = $db->prepare("UPDATE events SET status = 'rejected' WHERE id = ?");
        $update->execute([$id]);

        // Notification handled by controller via NotificationController::createAndSend
        return true;
    }

    public static function delete($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function register($user_id, $event_id)
    {
        $db = Database::connect();

        // ambil kapasitas + judul event
        $stmt = $db->prepare("SELECT title, capacity, status FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();

        if (!$event || $event['status'] !== 'approved') {
            return "EVENT_NOT_APPROVED";
        }

        if ($event['capacity'] <= 0) {
            return "EVENT_FULL";
        }

        // prevent duplicate registration to avoid DB constraint errors
        $duplicateCheck = $db->prepare("
            SELECT id FROM participants
            WHERE user_id = ? AND event_id = ?
        ");
        $duplicateCheck->execute([$user_id, $event_id]);

        if ($duplicateCheck->fetch()) {
            return "ALREADY_REGISTERED";
        }

        // insert participant
        try {
            $insert = $db->prepare("
                INSERT INTO participants (user_id, event_id)
                VALUES (?, ?)
            ");
            $insert->execute([$user_id, $event_id]);

            // kurangi kapasitas event
            $update = $db->prepare("
                UPDATE events SET capacity = capacity - 1 WHERE id = ?
            ");
            $update->execute([$event_id]);
        } catch (PDOException $e) {
            // handle race condition or other DB issues gracefully
            if ($e->getCode() === '23000') {
                return "ALREADY_REGISTERED";
            }

            error_log("Failed to register user {$user_id} for event {$event_id}: " . $e->getMessage());
            return "REGISTER_FAILED";
        }

        // Notification will be handled by controller layer
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
