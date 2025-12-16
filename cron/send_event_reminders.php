<?php

/**
 * Event Reminder Cron Job
 * 
 * Script ini dijalankan secara berkala untuk mengirim email reminder
 * ke peserta event yang akan dimulai dalam waktu yang ditentukan.
 * 
 * Cara menjalankan:
 * - Manual: php cron/send_event_reminders.php
 * - Windows Task Scheduler: Lihat dokumentasi di README
 */

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Load dependencies
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/NotificationService.php';

// Log function
function logMessage($message)
{
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}\n";
}

logMessage("=== Event Reminder Cron Job Started ===");

// Check if reminder is enabled
if (!EVENT_REMINDER_ENABLED) {
    logMessage("Event reminder is disabled in configuration. Exiting.");
    exit(0);
}

try {
    $db = Database::connect();
    $reminderHours = EVENT_REMINDER_HOURS;

    logMessage("Looking for events starting in {$reminderHours} hours...");

    // Calculate time window
    // Events yang akan dimulai antara NOW + REMINDER_HOURS dan NOW + REMINDER_HOURS + 1 hour
    $startWindow = date('Y-m-d H:i:s', strtotime("+{$reminderHours} hours"));
    $endWindow = date('Y-m-d H:i:s', strtotime("+" . ($reminderHours + 1) . " hours"));

    logMessage("Time window: {$startWindow} to {$endWindow}");

    // Get events in the time window that are approved
    $query = "
        SELECT 
            e.id,
            e.title,
            e.description,
            e.location,
            e.start_at,
            e.end_at
        FROM events e
        WHERE e.status = 'approved'
        AND e.start_at >= :start_window
        AND e.start_at < :end_window
    ";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':start_window' => $startWindow,
        ':end_window' => $endWindow
    ]);

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $eventCount = count($events);

    logMessage("Found {$eventCount} event(s) to process");

    if ($eventCount === 0) {
        logMessage("No events found. Exiting.");
        exit(0);
    }

    // Load email template
    $templatePath = __DIR__ . '/../templates/emails/event_reminder_template.php';
    if (!file_exists($templatePath)) {
        logMessage("ERROR: Email template not found at {$templatePath}");
        exit(1);
    }

    $emailTemplate = file_get_contents($templatePath);

    // Process each event
    $totalSent = 0;
    $totalFailed = 0;

    foreach ($events as $event) {
        $eventId = $event['id'];
        $eventTitle = $event['title'];

        logMessage("Processing event #{$eventId}: {$eventTitle}");

        // Get participants for this event
        $participantQuery = "
            SELECT 
                p.id as participant_id,
                p.user_id,
                u.name as user_name,
                u.email as user_email
            FROM participants p
            JOIN users u ON p.user_id = u.id
            WHERE p.event_id = :event_id
            AND p.status = 'registered'
        ";

        $participantStmt = $db->prepare($participantQuery);
        $participantStmt->execute([':event_id' => $eventId]);
        $participants = $participantStmt->fetchAll(PDO::FETCH_ASSOC);

        $participantCount = count($participants);
        logMessage("  Found {$participantCount} participant(s)");

        if ($participantCount === 0) {
            continue;
        }

        // Check if reminder already sent for this event
        // We'll use notifications table to track sent reminders
        foreach ($participants as $participant) {
            $userId = $participant['user_id'];
            $userName = $participant['user_name'];
            $userEmail = $participant['user_email'];

            // Check if reminder already sent
            $checkQuery = "
                SELECT id FROM notifications
                WHERE user_id = :user_id
                AND type = 'event_reminder'
                AND JSON_EXTRACT(payload, '$.event_id') = :event_id
                AND created_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
            ";

            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([
                ':user_id' => $userId,
                ':event_id' => $eventId
            ]);

            if ($checkStmt->fetch()) {
                logMessage("  Skipping {$userName} - reminder already sent");
                continue;
            }

            // Prepare email content
            $eventDatetime = date('l, d F Y - H:i', strtotime($event['start_at'])) . ' WIB';
            $eventDetailUrl = APP_BASE_URL . '/public/index.php?page=event-detail&id=' . $eventId;

            $emailBody = str_replace(
                [
                    '{{participant_name}}',
                    '{{event_title}}',
                    '{{event_datetime}}',
                    '{{event_location}}',
                    '{{event_description}}',
                    '{{event_detail_url}}'
                ],
                [
                    htmlspecialchars($userName),
                    htmlspecialchars($eventTitle),
                    $eventDatetime,
                    htmlspecialchars($event['location'] ?? 'TBA'),
                    htmlspecialchars(substr($event['description'] ?? '', 0, 200)),
                    $eventDetailUrl
                ],
                $emailTemplate
            );

            // Send email
            $subject = "⏰ Reminder: {$eventTitle} akan dimulai dalam {$reminderHours} jam";

            $sent = NotificationService::sendEmail(
                $userId,
                $userEmail,
                $subject,
                $emailBody
            );

            if ($sent) {
                logMessage("  ✓ Email sent to {$userName} ({$userEmail})");
                $totalSent++;

                // Log to notifications table with event_id in payload
                $logQuery = "
                    INSERT INTO notifications (user_id, type, payload, status, send_at)
                    VALUES (:user_id, 'event_reminder', :payload, 'sent', NOW())
                ";

                $logStmt = $db->prepare($logQuery);
                $logStmt->execute([
                    ':user_id' => $userId,
                    ':payload' => json_encode([
                        'event_id' => $eventId,
                        'event_title' => $eventTitle,
                        'message' => "Reminder: {$eventTitle} akan dimulai dalam {$reminderHours} jam"
                    ])
                ]);
            } else {
                logMessage("  ✗ Failed to send email to {$userName} ({$userEmail})");
                $totalFailed++;
            }

            // Small delay to avoid overwhelming SMTP server
            usleep(500000); // 0.5 second
        }
    }

    logMessage("=== Summary ===");
    logMessage("Total emails sent: {$totalSent}");
    logMessage("Total failed: {$totalFailed}");
    logMessage("=== Event Reminder Cron Job Completed ===");
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("Stack trace: " . $e->getTraceAsString());
    exit(1);
}

exit(0);
