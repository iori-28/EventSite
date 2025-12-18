<?php

/**
 * CSV Export Analytics API Endpoint
 * 
 * RESTful API untuk export data analytics ke format CSV.
 * Mendukung 3 types: participants, category, full
 * 
 * Export Types:
 * - participants: Daftar peserta per event dengan metrics
 * - category: Analisis popularitas kategori event
 * - full: Complete analytics report dengan semua metrics
 * 
 * Features:
 * - UTF-8 BOM untuk Excel compatibility
 * - Timestamped filenames (e.g., analytics_participants_20231218_143022.csv)
 * - Automatic CSV headers detection
 * - Professional formatting
 * - Direct file download via headers
 * 
 * Authentication: Required (session-based)
 * Authorization: Admin only
 * 
 * Response Format: CSV file download (with headers)
 * Content-Type: text/csv; charset=utf-8
 * Content-Disposition: attachment; filename="..."
 * 
 * @package EventSite\API
 * @author EventSite Team
 * @version 1.0
 */

session_start();

// Check authentication
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    die('Unauthorized');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/AnalyticsService.php';

try {
    $db = Database::connect();
    $type = $_GET['type'] ?? 'full';

    switch ($type) {
        case 'participants':
            AnalyticsService::exportParticipantsCSV($db);
            break;

        case 'category':
            AnalyticsService::exportCategoryCSV($db);
            break;

        case 'full':
            AnalyticsService::exportFullReport($db);
            break;

        default:
            http_response_code(400);
            die('Invalid export type');
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log('Export Analytics Error: ' . $e->getMessage());
    die('Export failed');
}
