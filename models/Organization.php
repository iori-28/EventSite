<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

class Organization
{

    public static function create($name, $desc, $user_id)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            INSERT INTO organizations (name, description, created_by)
            VALUES (?, ?, ?)
        ");

        return $stmt->execute([$name, $desc, $user_id]);
    }

    public static function getPending()
    {
        $db = Database::connect();
        return $db->query("SELECT * FROM organizations WHERE is_approved = 0")->fetchAll();
    }

    public static function approve($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("UPDATE organizations SET is_approved = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function reject($id)
    {
        $db = Database::connect();
        $stmt = $db->prepare("DELETE FROM organizations WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
