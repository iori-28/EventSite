<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Event.php';

class EventController
{
    public static function create($data)
    {
        return Event::create($data);
    }

    public static function getApproved()
    {
        return Event::getApproved();
    }

    public static function getByOrg($org_id)
    {
        return Event::getByOrg($org_id);
    }

    public static function approve($id)
    {
        return Event::approve($id);
    }

    public static function cancel($id)
    {
        return Event::cancel($id);
    }

    public static function register($user_id, $event_id)
    {
        return Event::register($user_id, $event_id);
    }
}
