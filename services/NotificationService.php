<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

class NotificationService
{
    /* ============================================================
       PRIVATE HELPERS
    ============================================================ */

    /** Ambil email berdasarkan user_id */
    private static function getEmailByUserId($user_id)
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['email'] : null;
        } catch (PDOException $e) {
            error_log("DB error in getEmailByUserId for user_id $user_id: " . $e->getMessage());
            return null;
        }
    }

    /** 
     * Log ke tabel notifications - REMOVED
     * Reason: Duplikasi dengan Notification::create() di controller
     * Single responsibility: Controller handles all DB operations
     */

    /* ============================================================
       PUBLIC FUNCTION — SEND EMAIL
    ============================================================ */

    public static function sendEmail($user_id, $toEmail, $subject, $message)
    {
        error_log("[NOTIF-SVC] sendEmail called - user_id: $user_id, toEmail: $toEmail, subject: $subject");

        // Validate required parameters
        if (empty($subject) || empty($message)) {
            error_log("[NOTIF-SVC] Email missing subject or message for user_id: $user_id");
            return false;
        }

        // Check if PHPMailer class exists (meaning autoloader worked)
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            error_log("[NOTIF-SVC] PHPMailer not found. Email cannot be sent for user_id: $user_id");
            return false;
        }

        // Validate mail configuration
        if (!defined('MAIL_HOST') || !defined('MAIL_USERNAME') || !defined('MAIL_PASSWORD')) {
            error_log("[NOTIF-SVC] Mail configuration missing. Check .env file.");
            return false;
        }

        error_log("[NOTIF-SVC] Mail config OK - Host: " . MAIL_HOST . ", Port: " . MAIL_PORT);

        // jika email kosong → ambil dari user_id
        if (empty($toEmail) && $user_id) {
            $toEmail = self::getEmailByUserId($user_id);
            error_log("[NOTIF-SVC] Fetched email from DB: $toEmail");
        }

        if (empty($toEmail)) {
            error_log("[NOTIF-SVC] Email address not found for user_id: $user_id");
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            /* CHARSET */
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            /* SMTP SERVER */
            $mail->isSMTP();
            $mail->Host       = MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_PASSWORD;

            // otomatis pilih secure mode berdasarkan port
            if (MAIL_PORT == 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->Port       = MAIL_PORT;
            $mail->SMTPDebug  = 0; // 0 = no debug, 1 = client, 2 = client+server

            /* FROM & TO */
            $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
            $mail->addAddress($toEmail);

            /* CONTENT */
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);

            /* SEND */
            $mail->send();

            // Email sent successfully - controller will update notification status
            error_log("[NOTIF-SVC] ✓ Email sent successfully to: $toEmail");
            return true;
        } catch (Exception $e) {

            $error = "Mailer Error: " . $mail->ErrorInfo;

            // Log error with context for debugging
            error_log("[NOTIF-SVC] ✗ MAIL ERROR [user_id: $user_id, to: $toEmail, subject: $subject]: " . $error);
            error_log("[NOTIF-SVC] Exception: " . $e->getMessage());

            return false;
        }
    }
}
