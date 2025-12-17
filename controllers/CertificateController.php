<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/CertificateService.php';

/**
 * Certificate Controller
 * 
 * Controller untuk mengelola certificate operations.
 * Thin wrapper around CertificateService untuk business logic tambahan.
 * 
 * Certificates di-generate sebagai PDF file setelah event completed.
 * File disimpan di public/certificates/ directory.
 * 
 * @package EventSite\Controllers
 * @author EventSite Team
 */
class CertificateController
{
    /**
     * Generate certificate PDF untuk participant
     * 
     * Method ini delegate ke CertificateService::generate() yang:
     * - Validate participant sudah check-in
     * - Generate PDF certificate dengan TCPDF library
     * - Save file ke public/certificates/
     * - Create database record di certificates table
     * 
     * Validation di service layer:
     * - Participant harus exist
     * - Event harus completed
     * - Participant harus attended (checked_in)
     * - Belum punya certificate sebelumnya
     * 
     * @param int $participant_id ID participant yang akan dapat certificate
     * @return array|false Certificate data dengan file_path, atau false jika gagal/validation error
     */
    public static function generate($participant_id)
    {
        // Pass-through ke service layer
        // Service akan handle PDF generation dan file saving
        return CertificateService::generate($participant_id);
    }

    /**
     * Ambil certificate berdasarkan participant ID
     * 
     * Method ini untuk check apakah participant sudah punya certificate.
     * Delegate ke CertificateService::getByParticipant().
     * 
     * @param int $participant_id ID participant yang dicari certificatenya
     * @return array|false Certificate data, atau false jika belum ada
     */
    public static function getByParticipant($participant_id)
    {
        return CertificateService::getByParticipant($participant_id);
    }

    /**
     * Ambil semua certificate yang dimiliki user
     * 
     * Method ini untuk tampilan halaman "My Certificates" user.
     * Delegate ke CertificateService::getByUser().
     * 
     * Result include:
     * - Certificate data (id, file_path, issued_at)
     * - Event info (title, start_at)
     * - Participant info (registered_at)
     * 
     * @param int $user_id ID user yang dicari certificatenya
     * @return array Array of certificates dengan event info, sorted by issued_at DESC
     */
    public static function getByUser($user_id)
    {
        return CertificateService::getByUser($user_id);
    }
}
