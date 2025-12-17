<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Event.php';

/**
 * Event Controller
 * 
 * Controller untuk mengelola event operations.
 * Thin wrapper around Event model untuk business logic tambahan.
 * 
 * Current implementation: Pass-through ke Model layer
 * Future: Add business logic, validation, authorization checks
 * 
 * @package EventSite\Controllers
 * @author EventSite Team
 */
class EventController
{
    /**
     * Buat event baru
     * 
     * Delegate ke Event::create().
     * Future enhancement: Add validation, file upload handling, etc.
     * 
     * @param array $data Data event dari form
     * @return bool True jika berhasil create
     */
    public static function create($data)
    {
        // Pass-through ke model layer
        return Event::create($data);
    }

    /**
     * Ambil semua event yang approved
     * 
     * Delegate ke Event::getApproved().
     * 
     * @return array Array of approved events
     */
    public static function getApproved()
    {
        return Event::getApproved();
    }

    /**
     * Approve event (admin only)
     * 
     * Delegate ke Event::approve().
     * Authorization check dilakukan di API layer.
     * 
     * @param int $id ID event yang akan di-approve
     * @return bool True jika berhasil approve
     */
    public static function approve($id)
    {
        return Event::approve($id);
    }

    /**
     * Cancel event
     * 
     * Delegate ke Event::cancel().
     * 
     * @param int $id ID event yang akan di-cancel
     * @return bool True jika berhasil cancel
     */
    public static function cancel($id)
    {
        return Event::cancel($id);
    }

    /**
     * Reject event (admin only)
     * 
     * Delegate ke Event::reject().
     * Authorization check dilakukan di API layer.
     * 
     * @param int $id ID event yang akan di-reject
     * @return bool True jika berhasil reject
     */
    public static function reject($id)
    {
        return Event::reject($id);
    }

    /**
     * Hapus event (hard delete)
     * 
     * Delegate ke Event::delete().
     * 
     * @param int $id ID event yang akan dihapus
     * @return bool True jika berhasil delete
     */
    public static function delete($id)
    {
        return Event::delete($id);
    }

    /**
     * Register user ke event (DEPRECATED)
     * 
     * Delegate ke Event::register().
     * 
     * @deprecated Use ParticipantController::register() for QR token generation
     * @param int $user_id ID user yang akan mendaftar
     * @param int $event_id ID event tujuan
     * @return string Status code
     */
    public static function register($user_id, $event_id)
    {
        return Event::register($user_id, $event_id);
    }
}
