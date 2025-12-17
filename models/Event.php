<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/NotificationService.php';

/**
 * Event Model
 * 
 * Model untuk mengelola data event (events table).
 * Menangani CRUD operations dan status workflow untuk event.
 * 
 * Status workflow:
 * - pending: Event baru dibuat, menunggu approval admin
 * - approved: Event di-approve admin, bisa dibuka untuk registrasi
 * - rejected: Event ditolak admin
 * - cancelled: Event dibatalkan oleh panitia/admin
 * - completed: Event sudah selesai dilaksanakan
 * 
 * Categories:
 * - Seminar, Workshop, Webinar, Kompetisi, Pelatihan, Lainnya
 * 
 * @package EventSite\Models
 * @author EventSite Team
 */
class Event
{
    /**
     * Buat event baru
     * 
     * Method ini untuk panitia/admin membuat event baru.
     * Event yang baru dibuat default status = 'pending' (menunggu approval).
     * Category default adalah 'Lainnya' jika tidak dispesifikasi.
     * 
     * @param array $data Data event dengan keys: title, description, category, location, 
     *                    start_at, end_at, capacity, status, created_by
     * @return bool True jika berhasil create event, false jika gagal
     */
    public static function create($data)
    {
        $db = Database::connect();

        // Insert event baru dengan semua field yang diperlukan
        $stmt = $db->prepare("
            INSERT INTO events 
            (title, description, category, location, start_at, end_at, capacity, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['title'],
            $data['description'],
            $data['category'] ?? 'Lainnya',  // Default category jika tidak ada
            $data['location'],
            $data['start_at'],
            $data['end_at'],
            $data['capacity'],
            $data['status'],
            $data['created_by']
        ]);
    }

    /**
     * Ambil semua event yang sudah approved
     * 
     * Method ini untuk tampilan public (user browse events).
     * Hanya event dengan status 'approved' yang visible untuk user.
     * 
     * @return array Array of approved events
     */
    public static function getApproved()
    {
        $db = Database::connect();

        // Query events dengan status approved saja
        return $db->query("SELECT * FROM events WHERE status = 'approved'")->fetchAll();
    }


    /**
     * Approve event (admin only)
     * 
     * Method ini dipanggil admin untuk approve event pending.
     * Setelah approved, event bisa dilihat user dan dibuka untuk registrasi.
     * JOIN dengan users untuk get email pembuat event (untuk notifikasi).
     * 
     * Validation:
     * - Event ID harus valid integer
     * - Event harus exist di database
     * 
     * @param int $id ID event yang akan di-approve
     * @return bool True jika berhasil approve, false jika validation error atau event not found
     */
    public static function approve($id)
    {
        // Validasi input: id harus integer positif
        if (!is_numeric($id) || $id <= 0) {
            return false;
        }

        $db = Database::connect();

        // Ambil data event + email pembuat event (untuk notifikasi)
        // JOIN dengan users table untuk get creator info
        $stmt = $db->prepare("
        SELECT events.title, users.id AS user_id, users.email
        FROM events
        JOIN users ON users.id = events.created_by
        WHERE events.id = ?
    ");
        $stmt->execute([$id]);
        $event = $stmt->fetch();

        // Check event exist
        if (!$event) {
            return false;
        }

        // Update status event menjadi 'approved'
        $update = $db->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
        $update->execute([$id]);

        // Notification handled by controller via NotificationController::createAndSend
        // Controller akan kirim email notifikasi ke pembuat event
        return true;
    }


    /**
     * Cancel event
     * 
     * Method ini untuk panitia/admin membatalkan event.
     * Event yang di-cancel tidak bisa diregistrasi lagi dan 
     * tidak tampil di browse events.
     * 
     * @param int $id ID event yang akan di-cancel
     * @return bool True jika berhasil cancel, false jika gagal
     */
    public static function cancel($id)
    {
        $db = Database::connect();

        // Update status event menjadi 'cancelled'
        $stmt = $db->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Reject event (admin only)
     * 
     * Method ini untuk admin menolak event pending.
     * Event yang di-reject tidak bisa diregistrasi dan tidak visible.
     * JOIN dengan users untuk get email pembuat event (untuk notifikasi).
     * 
     * @param int $id ID event yang akan di-reject
     * @return bool True jika berhasil reject, false jika event not found
     */
    public static function reject($id)
    {
        $db = Database::connect();

        // Ambil data event + email pembuat event (untuk notifikasi)
        $stmt = $db->prepare("
            SELECT events.title, users.id AS user_id, users.email
            FROM events
            JOIN users ON users.id = events.created_by
            WHERE events.id = ?
        ");
        $stmt->execute([$id]);
        $event = $stmt->fetch();

        // Check event exist
        if (!$event) return false;

        // Update status event menjadi 'rejected'
        $update = $db->prepare("UPDATE events SET status = 'rejected' WHERE id = ?");
        $update->execute([$id]);

        // Notification handled by controller via NotificationController::createAndSend
        // Controller akan kirim email notifikasi ke pembuat event
        return true;
    }

    /**
     * Hapus event (hard delete)
     * 
     * Method ini untuk admin menghapus event dari database.
     * Warning: Ini adalah hard delete, data tidak bisa di-recover.
     * Participant records akan ikut terhapus (ON DELETE CASCADE).
     * 
     * @param int $id ID event yang akan dihapus
     * @return bool True jika berhasil delete, false jika gagal
     */
    public static function delete($id)
    {
        $db = Database::connect();

        // Hard delete event record
        // Participants akan ikut terhapus karena foreign key ON DELETE CASCADE
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Register user ke event (DEPRECATED - Gunakan Participant::register())
     * 
     * Method ini untuk user mendaftar ke event.
     * 
     * Process:
     * 1. Validasi event approved dan kapasitas available
     * 2. Insert participant record
     * 3. Kurangi capacity event
     * 
     * Return codes:
     * - "EVENT_NOT_APPROVED": Event belum approved atau tidak exist
     * - "EVENT_FULL": Kapasitas event sudah penuh
     * - "REGISTER_FAILED": Insert participant gagal (duplicate atau error)
     * - "REGISTER_SUCCESS": Registrasi berhasil
     * 
     * @deprecated Use Participant::register() instead for QR token generation
     * @param int $user_id ID user yang akan mendaftar
     * @param int $event_id ID event tujuan
     * @return string Status code
     */
    public static function register($user_id, $event_id)
    {
        $db = Database::connect();

        // Ambil data event untuk validasi
        $stmt = $db->prepare("SELECT title, capacity, status FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();

        // Validasi: event harus exist dan status approved
        if (!$event || $event['status'] !== 'approved') {
            return "EVENT_NOT_APPROVED";
        }

        // Validasi: capacity harus masih tersedia (> 0)
        if ($event['capacity'] <= 0) {
            return "EVENT_FULL";
        }

        // Insert participant record
        // Jika duplicate (user sudah terdaftar), akan error karena unique constraint
        $insert = $db->prepare("
        INSERT INTO participants (user_id, event_id)
        VALUES (?, ?)
    ");

        if (!$insert->execute([$user_id, $event_id])) {
            return "REGISTER_FAILED";
        }

        // Kurangi kapasitas event (decrement by 1)
        $update = $db->prepare("
        UPDATE events SET capacity = capacity - 1 WHERE id = ?
    ");
        $update->execute([$event_id]);

        // Notification will be handled by controller layer
        // Controller akan kirim email registrasi success dengan QR code
        return "REGISTER_SUCCESS";
    }


    /**
     * Ambil data event berdasarkan ID
     * 
     * Method ini untuk detail event page.
     * JOIN dengan users untuk get email creator (untuk contact info).
     * 
     * @param int $id ID event yang dicari
     * @return array|false Data event dengan creator_email, atau false jika not found
     */
    public static function getById($id)
    {
        $db = Database::connect();

        // Query event dengan JOIN ke users untuk get creator info
        $stmt = $db->prepare("
        SELECT events.*, users.email AS creator_email
        FROM events
        JOIN users ON users.id = events.created_by
        WHERE events.id = ?
    ");
        $stmt->execute([$id]);

        return $stmt->fetch();
    }
}
