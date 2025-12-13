<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/CertificateService.php';

class CertificateController
{
    /**
     * Generate certificate for participant
     */
    public static function generate($participant_id)
    {
        return CertificateService::generate($participant_id);
    }

    /**
     * Get certificate by participant ID
     */
    public static function getByParticipant($participant_id)
    {
        return CertificateService::getByParticipant($participant_id);
    }

    /**
     * Get all certificates for a user
     */
    public static function getByUser($user_id)
    {
        return CertificateService::getByUser($user_id);
    }
}
