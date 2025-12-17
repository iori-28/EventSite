<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Notification.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/NotificationService.php';

/**
 * Notification Controller
 * 
 * Controller untuk mengelola notification/email operations.
 * Orchestrate antara Notification Model (database) dan NotificationService (SMTP).
 * 
 * Flow:
 * 1. Create notification record di database (status: pending)
 * 2. Attempt email delivery via NotificationService (SMTP)
 * 3. Update status based on delivery result (sent/failed)
 * 
 * Uses error_log() extensively untuk debugging email delivery issues.
 * 
 * @package EventSite\Controllers
 * @author EventSite Team
 */
class NotificationController
{
    /**
     * Buat notification record dan kirim email
     * 
     * Method ini adalah main orchestrator untuk email sending.
     * 
     * Process:
     * 1. Validate user_id
     * 2. Create notification record (status: pending)
     * 3. Extract email dari payload atau fetch dari DB
     * 4. Send email via NotificationService (SMTP/PHPMailer)
     * 5. Update notification status based on delivery result
     * 
     * Logging:
     * - Semua steps di-log ke error_log untuk debugging
     * - Useful untuk troubleshoot email delivery issues
     * 
     * Return array keys:
     * - db_id: ID notification record di database
     * - delivered: Boolean apakah email berhasil terkirim
     * - status: 'sent' atau 'failed'
     * - error: (optional) Error message jika gagal
     * 
     * @param int $user_id ID user penerima notifikasi
     * @param string $type Tipe notifikasi (event_approved, registration_success, etc)
     * @param array $payload Data context untuk email template
     * @param string $subject Email subject line
     * @param string $htmlBody Email body (HTML format)
     * @return array Result array dengan keys: db_id, delivered, status, [error]
     */
    public static function createAndSend($user_id, $type, $payload, $subject = '', $htmlBody = '')
    {
        // Validasi input: user_id harus integer positif
        if (!is_numeric($user_id) || $user_id <= 0) {
            error_log("[NOTIF-CTRL] Invalid user_id in createAndSend: $user_id");
            return ['db_id' => null, 'delivered' => false, 'error' => 'invalid_user_id'];
        }

        // Log start of notification process
        error_log("[NOTIF-CTRL] Creating notification for user_id: $user_id, type: $type");

        // Step 1: Create notification record di database (status: pending)
        $id = Notification::create($user_id, $type, $payload, 'pending');
        if (!$id) {
            error_log("[NOTIF-CTRL] Failed to create notification for user_id: $user_id, type: $type");
            return ['db_id' => null, 'delivered' => false, 'error' => 'db_insert_failed'];
        }

        error_log("[NOTIF-CTRL] Notification created with ID: $id");

        // Step 2: Extract email address dari payload
        // Jika tidak ada, NotificationService akan fetch dari users table
        $userEmail = null;
        if (is_array($payload) && isset($payload['email'])) {
            $userEmail = $payload['email'];
        }

        error_log("[NOTIF-CTRL] Target email: " . ($userEmail ?? 'none - will fetch from DB'));

        // Step 3: Attempt to send email via NotificationService
        // NotificationService menggunakan PHPMailer dengan SMTP
        $delivered = NotificationService::sendEmail($user_id, $userEmail ?? '', $subject, $htmlBody);

        error_log("[NOTIF-CTRL] Email delivery result: " . ($delivered ? 'SUCCESS' : 'FAILED'));

        // Step 4: Update notification status based on delivery result
        // Status: 'sent' jika success, 'failed' jika error
        $statusUpdated = Notification::updateStatus($id, $delivered ? 'sent' : 'failed');

        if (!$statusUpdated) {
            error_log("[NOTIF-CTRL] Failed to update notification status for id: $id");
        } else {
            error_log("[NOTIF-CTRL] Notification status updated to: " . ($delivered ? 'sent' : 'failed'));
        }

        // Return result array
        return [
            'db_id' => $id,
            'delivered' => $delivered,
            'status' => $delivered ? 'sent' : 'failed'
        ];
    }

    /**
     * Hitung jumlah notifikasi unread (pending)
     * 
     * Method ini untuk badge counter di navbar.
     * Status 'pending' dianggap sebagai unread.
     * 
     * @param int $user_id ID user yang dicari notifikasinya
     * @return int Jumlah notifikasi unread, atau 0 jika invalid user_id
     */
    public static function getUnreadCount($user_id)
    {
        // Validasi input
        if (!is_numeric($user_id) || $user_id <= 0) {
            return 0;
        }

        $db = Database::connect();

        // Count notifications dengan status pending
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$user_id]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Ambil notifikasi terbaru user (untuk dropdown)
     * 
     * Method ini untuk notification dropdown di navbar.
     * Limit default 5, max 50 untuk prevent performance issues.
     * 
     * @param int $user_id ID user yang dicari notifikasinya
     * @param int $limit Jumlah notifikasi yang akan diambil (default: 5, max: 50)
     * @return array Array of latest notifications, sorted by send_at DESC
     */
    public static function getLatest($user_id, $limit = 5)
    {
        // Validasi input: user_id harus integer positif
        if (!is_numeric($user_id) || $user_id <= 0) {
            return [];
        }

        // Sanitize limit: minimum 1, maximum 50
        // Prevent SQL injection dan performance issues
        $limit = max(1, min((int)$limit, 50));

        $db = Database::connect();

        // Query latest notifications dengan limit
        // Sort by send_at DESC untuk newest first
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY send_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);

        return $stmt->fetchAll();
    }
}
