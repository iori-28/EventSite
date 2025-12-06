<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Notification.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/NotificationService.php';

class NotificationController
{
    /**
     * Create a notification record (pending) and attempt delivery via service.
     * Returns array: ['db_id' => int, 'delivered' => bool]
     */
    public static function createAndSend($user_id, $type, $payload, $subject = '', $htmlBody = '')
    {
        // 1) log sebagai pending
        $id = Notification::create($user_id, $type, $payload, 'pending');
        if (!$id) {
            return ['db_id' => null, 'delivered' => false, 'error' => 'db_insert_failed'];
        }

        // 2) try send via NotificationService (email)
        $userEmail = null;
        // if payload contains email, prefer that. otherwise NotificationService will need email sent separately.
        if (is_array($payload) && isset($payload['email'])) {
            $userEmail = $payload['email'];
        }

        // Fallback: if user_id provided, NotificationService will look up email internally (we assume it needs email)
        $delivered = NotificationService::sendEmail($user_id, $userEmail ?? '', $subject, $htmlBody);

        // 3) update status
        Notification::updateStatus($id, $delivered ? 'sent' : 'failed');

        return ['db_id' => $id, 'delivered' => $delivered];
    }
}
