<?php

/**
 * Participant Model
 * 
 * Model untuk mengelola data peserta event (participants table).
 * Menangani registrasi, pembatalan, dan query data peserta.
 * 
 * @package EventSite\Models
 * @author EventSite Team
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

class Participant
{
    /**
     * Register user ke event
     * 
     * Method ini melakukan proses registrasi user ke event dengan validasi:
     * - Event harus sudah approved oleh admin
     * - Kapasitas event harus masih tersedia
     * - User belum terdaftar sebelumnya
     * - Generate QR token unik untuk kehadiran
     * 
     * @param int $user_id ID user yang akan mendaftar
     * @param int $event_id ID event tujuan
     * @return string|bool Status code atau true/false
     *                     - "INVALID_INPUT": Parameter tidak valid
     *                     - "NOT_APPROVED": Event belum di-approve admin
     *                     - "FULL": Kapasitas event penuh
     *                     - "ALREADY_REGISTERED": User sudah terdaftar
     *                     - true: Registrasi berhasil
     *                     - false: Database error
     */
    public static function register($user_id, $event_id)
    {
        // Validasi input: harus integer positif
        if (!is_numeric($user_id) || $user_id <= 0 || !is_numeric($event_id) || $event_id <= 0) {
            return "INVALID_INPUT";
        }

        $db = Database::connect();

        // Ambil data event dan hitung total peserta saat ini
        $stmt = $db->prepare("
        SELECT status, capacity,
        (SELECT COUNT(*) FROM participants WHERE event_id = ?) AS total
        FROM events WHERE id = ?
        ");
        $stmt->execute([$event_id, $event_id]);
        $event = $stmt->fetch();

        // Cek status event: hanya event approved yang bisa didaftar
        if ($event['status'] !== 'approved') {
            return "NOT_APPROVED";
        }

        // Cek kapasitas: jika sudah penuh, reject registrasi
        if ($event['total'] >= $event['capacity']) {
            return "FULL";
        }

        // Cek duplikasi: user tidak boleh daftar 2x ke event yang sama
        $stmt = $db->prepare("
        SELECT id FROM participants
        WHERE user_id = ? AND event_id = ?
        ");
        $stmt->execute([$user_id, $event_id]);

        if ($stmt->fetch()) {
            return "ALREADY_REGISTERED";
        }

        // Generate QR token unik menggunakan SHA256
        // Token ini digunakan untuk konfirmasi kehadiran via QR code
        $qr_token = hash('sha256', $user_id . $event_id . time() . random_bytes(16));

        // Insert data participant dengan QR token
        $stmt = $db->prepare("
        INSERT INTO participants (user_id, event_id, qr_token)
        VALUES (?, ?, ?)
        ");

        return $stmt->execute([$user_id, $event_id, $qr_token]);
    }


    /**
     * Batalkan registrasi user dari event
     * 
     * Menghapus data participant dari database.
     * User bisa membatalkan pendaftaran sebelum event dimulai.
     * 
     * @param int $user_id ID user yang membatalkan
     * @param int $event_id ID event yang dibatalkan
     * @return bool True jika berhasil, false jika gagal
     */
    public static function cancel($user_id, $event_id)
    {
        // Validasi input
        if (!is_numeric($user_id) || $user_id <= 0 || !is_numeric($event_id) || $event_id <= 0) {
            return false;
        }

        $db = Database::connect();

        // Hard delete participant record
        $stmt = $db->prepare("
            DELETE FROM participants
            WHERE user_id = ? AND event_id = ?
        ");
        return $stmt->execute([$user_id, $event_id]);
    }

    /**
     * Get semua event yang diikuti user
     * 
     * Mengambil list event dimana user terdaftar sebagai participant.
     * Return data event lengkap dengan JOIN ke tabel events.
     * 
     * @param int $user_id ID user
     * @return array Array of event objects
     */
    public static function getByUser($user_id)
    {
        $db = Database::connect();

        // JOIN participants dengan events untuk dapat detail event
        $stmt = $db->prepare("
            SELECT e.*
            FROM events e
            JOIN participants p ON e.id = p.event_id
            WHERE p.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}
