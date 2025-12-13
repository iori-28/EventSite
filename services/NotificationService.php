<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$autoloadPath = require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

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
            error_log("DB error getEmailByUserId: " . $e->getMessage());
            return null;
        }
    }

    /** Log ke tabel notifications */
    private static function log($user_id, $type, $payload, $status)
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, payload, status, send_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $type, json_encode(["message" => $payload]), $status]);
        } catch (PDOException $e) {
            error_log("DB error logging notification: " . $e->getMessage());
        }
    }

    /* ============================================================
       PUBLIC FUNCTION — SEND EMAIL
    ============================================================ */

    public static function sendEmail($user_id, $toEmail, $subject, $message)
    {
        // Check if PHPMailer class exists (meaning autoloader worked)
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            self::log($user_id, 'email', $subject . " (Email skipped: PHPMailer not installed)", 'failed');
            // error_log("PHPMailer not found. Run 'composer install' in project root.");
            return false;
        }

        // jika email kosong → ambil dari user_id
        if (empty($toEmail) && $user_id) {
            $toEmail = self::getEmailByUserId($user_id);
        }

        if (empty($toEmail)) {
            self::log($user_id, 'email', "Email kosong / tidak ditemukan", 'failed');
            return false;
        }

        $mail = new PHPMailer(true);

        try {
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

            self::log($user_id, 'email', $subject, 'sent');
            return true;
        } catch (Exception $e) {

            $error = "Mailer Error: " . $mail->ErrorInfo;

            // log error ke tabel notifications
            self::log($user_id, 'email', $subject . " | " . $error, 'failed');

            // log juga ke server error log
            error_log("MAIL ERROR: " . $error);

            return false;
        }
    }
}
