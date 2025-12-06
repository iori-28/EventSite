<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Participant.php';

class ParticipantController
{
    public static function register($user_id, $event_id)
    {
        return Participant::register($user_id, $event_id);
    }

    public static function cancel($user_id, $event_id)
    {
        return Participant::cancel($user_id, $event_id);
    }

    public static function getByUser($user_id)
    {
        return Participant::getByUser($user_id);
    }
}
