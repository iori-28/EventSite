<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class NotificationService
{
    public static function sendEmail($user_id, $toEmail, $subject, $message)
    {
        // if email not provided, fetch from users table
        if (empty($toEmail) && $user_id) {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch();
            if ($row) $toEmail = $row['email'];
        }

        // if still empty, fail early
        if (empty($toEmail)) {
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // SERVER
            // debug off in production
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_PORT;

            // FROM & TO
            $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
            $mail->addAddress($toEmail);

            // CONTENT
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;

            $mail->send();

            self::log($user_id, 'email', $subject, 'sent');
            return true;
        } catch (Exception $e) {
            // you can write error log to file for debugging:
            error_log("Mail error: " . $mail->ErrorInfo);
            return false;
        }
    }

    private static function log($user_id, $type, $payload, $status)
    {
        $db = Database::connect();

        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, payload, status, send_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([$user_id, $type, $payload, $status]);
    }
}
