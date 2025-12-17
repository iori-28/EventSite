<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

/**
 * Notification Model
 * 
 * Model untuk mengelola data notifikasi (notifications table).
 * Menangani logging notifikasi email untuk tracking dan history.
 * Status flow: pending -> sent/failed
 * 
 * Notification types:
 * - event_approved: Event di-approve admin
 * - event_rejected: Event ditolak admin
 * - registration_success: Berhasil daftar event
 * - event_reminder: Reminder H-1 dan H-0
 * - certificate_issued: Sertifikat sudah tersedia
 * 
 * @package EventSite\Models
 * @author EventSite Team
 */
class Notification
{
    /**
     * Buat notifikasi baru (log email)
     * 
     * Method ini membuat record notifikasi untuk tracking email.
     * Payload berisi data context yang akan digunakan di email template.
     * 
     * Validation:
     * - user_id harus integer positif
     * - type tidak boleh empty
     * - payload akan di-encode ke JSON
     * 
     * @param int $user_id ID user penerima notifikasi
     * @param string $type Tipe notifikasi (event_approved, registration_success, etc)
     * @param array $payload Data context untuk email template (akan di-encode ke JSON)
     * @param string $status Status awal: 'pending' (default), 'sent', atau 'failed'
     * @return int|false ID notifikasi yang baru dibuat, atau false jika gagal
     */
    public static function create($user_id, $type, $payload, $status = 'pending')
    {
        // Validasi input: user_id harus integer positif
        if (!is_numeric($user_id) || $user_id <= 0) {
            error_log("Invalid user_id in Notification::create: $user_id");
            return false;
        }

        // Validasi input: type tidak boleh empty
        if (empty($type)) {
            error_log("Empty type in Notification::create for user_id: $user_id");
            return false;
        }

        $db = Database::connect();

        // Insert notification record dengan timestamp NOW()
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, type, payload, status, send_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        if ($stmt->execute([
            $user_id,
            $type,
            json_encode($payload),  // Encode payload array ke JSON string
            $status
        ])) {
            // Return ID notification yang baru dibuat
            return $db->lastInsertId();
        }

        return false;
    }

    /**
     * Update status notifikasi
     * 
     * Method ini dipanggil setelah email dikirim (success/fail).
     * Digunakan untuk tracking status pengiriman email.
     * 
     * Validation:
     * - id harus integer positif
     * - status harus salah satu dari: pending, sent, failed
     * 
     * @param int $id ID notifikasi yang akan diupdate
     * @param string $status Status baru: 'pending', 'sent', atau 'failed'
     * @return bool True jika berhasil update, false jika gagal atau validation error
     */
    public static function updateStatus($id, $status)
    {
        // Validasi input: id harus integer positif
        if (!is_numeric($id) || $id <= 0) {
            error_log("Invalid id in Notification::updateStatus: $id");
            return false;
        }

        // Validasi input: status harus salah satu dari 3 nilai yang diizinkan
        if (!in_array($status, ['pending', 'sent', 'failed'])) {
            error_log("Invalid status in Notification::updateStatus: $status");
            return false;
        }

        $db = Database::connect();

        // Update status notification
        $stmt = $db->prepare("
            UPDATE notifications SET status = ? WHERE id = ?
        ");

        return $stmt->execute([$status, $id]);
    }

    /**
     * Ambil semua notifikasi user
     * 
     * Method ini untuk tampilan halaman "Notifications" user.
     * Menampilkan history semua email yang pernah dikirim ke user.
     * 
     * @param int $user_id ID user yang dicari notifikasinya
     * @return array Array of notifications, atau empty array jika tidak ada / invalid user_id
     */
    public static function getByUser($user_id)
    {
        // Validasi input: user_id harus integer positif
        // Return empty array jika invalid (bukan false, untuk konsistensi)
        if (!is_numeric($user_id) || $user_id <= 0) {
            return [];
        }

        $db = Database::connect();

        // Query semua notifications untuk user tertentu
        $stmt = $db->prepare("
            SELECT * FROM notifications WHERE user_id = ?
        ");

        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
