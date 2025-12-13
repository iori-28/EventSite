<?php

/**
 * Export Event to Calendar
 * Generates and downloads .ics file for calendar import
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/services/CalendarService.php';

$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    http_response_code(400);
    die('Event ID required');
}

try {
    $db = Database::connect();

    // Get event details
    $query = "
        SELECT 
            e.*,
            o.name as organization_name
        FROM events e
        LEFT JOIN organizations o ON e.organization_id = o.id
        WHERE e.id = :event_id
        AND e.status = 'approved'
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([':event_id' => $event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        die('Event not found');
    }

    // Generate iCalendar content
    $icalContent = CalendarService::generateICalendar($event);

    // Set headers for file download
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitizeFilename($event['title']) . '.ics"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    // Output iCalendar content
    echo $icalContent;
} catch (PDOException $e) {
    http_response_code(500);
    die('Database error: ' . $e->getMessage());
}

/**
 * Sanitize filename for download
 */
function sanitizeFilename($filename)
{
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $filename);
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    // Limit length
    $filename = substr($filename, 0, 50);
    return $filename;
}
