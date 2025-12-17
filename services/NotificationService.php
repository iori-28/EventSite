<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

/**
 * Notification Service
 * 
 * Service untuk mengirim email notifications menggunakan PHPMailer library.
 * Handle SMTP configuration, email sending, dan error logging.
 * 
 * Email Configuration:
 * - SMTP Host, Port, Username, Password dari config/env.php
 * - Support STARTTLS (port 587) dan SSL/TLS (port 465)
 * - Charset UTF-8 untuk support Bahasa Indonesia
 * - HTML email dengan fallback plain text
 * 
 * Error Handling:
 * - Extensive error logging untuk debugging
 * - Validate configuration before sending
 * - Return boolean success/failure status
 * 
 * Single Responsibility:
 * - Service hanya handle email delivery (SMTP)
 * - Controller handle database logging (notifications table)
 * - Separation of concerns untuk easier testing dan maintenance
 * 
 * @package EventSite\Services
 * @author EventSite Team
 */
class NotificationService
{
    /* ============================================================
       PRIVATE HELPERS
    ============================================================ */

    /**
     * Ambil email address berdasarkan user_id (fallback)
     * 
     * Method ini dipanggil jika email address tidak di-provide di parameter.
     * Fetch dari users table berdasarkan user_id.
     * \n     * Use case:\n     * - Email address tidak ada di payload\n     * - Need to lookup email dari user_id saja
     * 
     * @param int $user_id ID user yang akan dicari emailnya
     * @return string|null Email address, atau null jika user not found atau DB error
     */
    private static function getEmailByUserId($user_id)
    {
        try {
            $db = Database::connect();

            // Query email dari users table
            $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $row['email'] : null;
        } catch (PDOException $e) {
            // Log DB error untuk debugging
            error_log("DB error in getEmailByUserId for user_id $user_id: " . $e->getMessage());
            return null;
        }
    }

    /** 
     * Log ke tabel notifications - REMOVED
     * 
     * Reason: Duplikasi dengan Notification::create() di controller
     * Single responsibility principle: Controller handles all DB operations
     * Service hanya handle email delivery (SMTP)
     */

    /* ============================================================
       PUBLIC FUNCTION — SEND EMAIL
    ============================================================ */

    /**
     * Kirim email via SMTP menggunakan PHPMailer
     * 
     * Main method untuk email delivery. Handle semua SMTP configuration
     * dan error handling. Extensive logging untuk debugging issues.
     * 
     * Validation Steps:
     * 1. Check required parameters (subject, message)
     * 2. Check PHPMailer library loaded
     * 3. Check SMTP configuration defined
     * 4. Get recipient email (from param atau fetch dari DB)
     * 
     * SMTP Configuration:
     * - Auto-detect encryption: SMTPS (port 465) atau STARTTLS (others)
     * - Charset UTF-8 untuk support Bahasa Indonesia
     * - Base64 encoding untuk special characters
     * 
     * Error Handling:
     * - Return false jika validation gagal
     * - Return false jika SMTP error
     * - Log semua errors dengan context untuk debugging
     * 
     * @param int $user_id ID user penerima (untuk lookup email jika $toEmail empty)
     * @param string $toEmail Email address penerima (optional, bisa fetch dari DB)
     * @param string $subject Email subject line
     * @param string $message Email body (HTML format)
     * @return bool True jika email berhasil dikirim, false jika gagal
     */
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
