<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/Participant.php';

/**
 * Participant Controller
 * 
 * Controller untuk mengelola participant operations.
 * Thin wrapper around Participant model untuk business logic tambahan.
 * 
 * This controller should be used instead of EventController::register()
 * karena support QR token generation.
 * 
 * @package EventSite\Controllers
 * @author EventSite Team
 */
class ParticipantController
{
    /**
     * Register user ke event dengan QR token generation
     * 
     * Method ini delegate ke Participant::register() yang include:
     * - Validation: event approved, capacity available, no duplicate
     * - QR token generation untuk attendance tracking
     * - Capacity decrement
     * 
     * Return codes:
     * - "EVENT_NOT_APPROVED": Event belum approved
     * - "EVENT_FULL": Kapasitas penuh
     * - "ALREADY_REGISTERED": User sudah terdaftar
     * - "REGISTER_SUCCESS": Berhasil register
     * 
     * @param int $user_id ID user yang akan mendaftar
     * @param int $event_id ID event tujuan
     * @return string Status code
     */
    public static function register($user_id, $event_id)
    {
        // Pass-through ke model layer
        // Model akan handle validation dan QR token generation
        return Participant::register($user_id, $event_id);
    }

    /**
     * Cancel registrasi (unregister)
     * 
     * Method ini untuk user membatalkan registrasi event.
     * Delegate ke Participant::cancel().
     * 
     * @param int $user_id ID user yang akan cancel
     * @param int $event_id ID event yang akan di-cancel
     * @return bool True jika berhasil cancel, false jika gagal
     */
    public static function cancel($user_id, $event_id)
    {
        return Participant::cancel($user_id, $event_id);
    }

    /**
     * Ambil semua event yang diikuti user
     * 
     * Method ini untuk tampilan "My Events" user.
     * Delegate ke Participant::getByUser().
     * 
     * @param int $user_id ID user yang dicari eventnya
     * @return array Array of events dengan participant data dan QR token
     */
    public static function getByUser($user_id)
    {
        return Participant::getByUser($user_id);
    }
}
