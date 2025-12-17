<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

/**
 * Certificate Model
 * 
 * Model untuk mengelola data sertifikat peserta (certificates table).
 * Menangani pembuatan, query, dan penghapusan sertifikat.
 * Sertifikat di-generate sebagai PDF file setelah event selesai.
 * 
 * @package EventSite\Models
 * @author EventSite Team
 */
class Certificate
{
    /**
     * Buat record sertifikat baru
     * 
     * Method ini dipanggil setelah PDF sertifikat berhasil di-generate.
     * Menyimpan path file PDF dan timestamp issued_at.
     * 
     * @param int $participant_id ID participant yang mendapat sertifikat
     * @param string $file_path Path relatif ke file PDF sertifikat
     * @return int|false ID sertifikat yang baru dibuat, atau false jika gagal
     */
    public static function create($participant_id, $file_path)
    {
        $db = Database::connect();

        // Insert certificate record dengan timestamp NOW()
        $stmt = $db->prepare("
            INSERT INTO certificates (participant_id, file_path, issued_at) 
            VALUES (?, ?, NOW())
        ");

        if ($stmt->execute([$participant_id, $file_path])) {
            // Return ID certificate yang baru dibuat
            return $db->lastInsertId();
        }
        return false;
    }

    /**
     * Ambil data sertifikat berdasarkan participant_id
     * 
     * Method ini untuk check apakah participant sudah punya sertifikat.
     * Setiap participant hanya boleh punya 1 sertifikat per event.
     * 
     * @param int $participant_id ID participant yang dicari
     * @return array|false Data sertifikat sebagai associative array, atau false jika belum ada
     */
    public static function getByParticipant($participant_id)
    {
        $db = Database::connect();

        // Query single certificate untuk participant tertentu
        $stmt = $db->prepare("SELECT * FROM certificates WHERE participant_id = ?");
        $stmt->execute([$participant_id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil semua sertifikat yang dimiliki seorang user
     * 
     * Method ini untuk tampilan halaman "My Certificates" user.
     * JOIN dengan participants dan events untuk dapat info event.
     * Hasil diurutkan berdasarkan tanggal issued (terbaru dulu).
     * 
     * @param int $user_id ID user yang dicari sertifikatnya
     * @return array Array of certificates dengan info event, sorted by issued_at DESC
     */
    public static function getByUser($user_id)
    {
        $db = Database::connect();

        // JOIN 3 tables: certificates -> participants -> events
        // Untuk dapat info lengkap: certificate + event title + timestamps
        $stmt = $db->prepare("
            SELECT 
                c.*,
                e.title as event_title,
                e.start_at,
                p.registered_at
            FROM certificates c
            JOIN participants p ON c.participant_id = p.id
            JOIN events e ON p.event_id = e.id
            WHERE p.user_id = ?
            ORDER BY c.issued_at DESC
        ");
        $stmt->execute([$user_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Hapus sertifikat berdasarkan ID
     * 
     * Method ini untuk admin/panitia yang ingin revoke sertifikat.
     * Note: File PDF tidak dihapus otomatis, hanya record di database.
     * 
     * @param int $id ID sertifikat yang akan dihapus
     * @return bool True jika berhasil delete, false jika gagal
     */
    public static function delete($id)
    {
        $db = Database::connect();

        // Hard delete certificate record
        $stmt = $db->prepare("DELETE FROM certificates WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
