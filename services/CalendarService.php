<?php

/**
 * Calendar Service
 * 
 * Service untuk calendar integration dan event export.
 * Support 2 format:
 * 1. Google Calendar URL (add to Google Calendar)
 * 2. iCalendar (.ics) file (untuk Outlook, Apple Calendar, dll)
 * 
 * Use Cases:
 * - User add event ke personal calendar setelah registrasi
 * - Download .ics file untuk import ke Outlook/Apple Calendar
 * - Share event via calendar invitation
 * 
 * Timezone Handling:
 * - Convert dari Asia/Jakarta ke UTC
 * - Format sesuai RFC 5545 (iCalendar spec)
 * 
 * @package EventSite\Services
 * @author EventSite Team
 */
class CalendarService
{
    /**
     * Generate Google Calendar "Add Event" URL
     * 
     * Method ini membuat URL untuk user add event ke Google Calendar.
     * URL menggunakan Google Calendar's "Add Event" template feature.
     * 
     * URL Parameters:
     * - action=TEMPLATE: Use template mode
     * - text: Event title
     * - dates: Start/End dalam format YYYYMMDDTHHmmssZ
     * - details: Event description (HTML stripped)
     * - location: Event location
     * - sf=true: Show "add to calendar" form
     * - output=xml: Output format
     * 
     * Example output:
     * https://calendar.google.com/calendar/render?action=TEMPLATE&text=Workshop+PHP&dates=20240120T100000Z/20240120T120000Z&details=...
     * 
     * @param array $event Event data dengan keys: title, description, location, start_at, end_at
     * @return string Google Calendar URL (ready untuk href link)
     */
    public static function generateGoogleCalendarUrl($event)
    {
        // URL encode semua parameters untuk safe URL
        $title = urlencode($event['title']);
        $description = urlencode(strip_tags($event['description'] ?? ''));  // Strip HTML dari description
        $location = urlencode($event['location'] ?? '');

        // Convert datetime dari MySQL format ke Google Calendar format
        // Format: YYYYMMDDTHHmmssZ (UTC timezone)
        $startDate = self::formatDateForGoogle($event['start_at']);
        $endDate = self::formatDateForGoogle($event['end_at']);

        // Google Calendar dates format: START/END
        $dates = $startDate . '/' . $endDate;

        // Build Google Calendar URL dengan semua parameters
        $url = 'https://calendar.google.com/calendar/render?action=TEMPLATE';
        $url .= '&text=' . $title;           // Event title
        $url .= '&dates=' . $dates;          // Start/End times
        $url .= '&details=' . $description;  // Event description
        $url .= '&location=' . $location;    // Event location
        $url .= '&sf=true&output=xml';       // Show form & output format

        return $url;
    }

    /**
     * Generate iCalendar (.ics) file content
     * 
     * Method ini membuat iCalendar format file untuk import ke calendar apps.
     * Compatible dengan: Outlook, Apple Calendar, Thunderbird, dll.
     * 
     * iCalendar Format (RFC 5545):
     * - VCALENDAR container dengan VEVENT inside
     * - Datetime dalam UTC timezone (Z suffix)
     * - Special characters di-escape (\,\;\n)
     * - Line folding max 75 chars (RFC requirement)
     * 
     * Generated Fields:
     * - UID: Unique identifier untuk event (MD5 hash)
     * - DTSTAMP: File generation timestamp
     * - DTSTART/DTEND: Event start/end time
     * - SUMMARY: Event title
     * - DESCRIPTION: Event description (HTML stripped)
     * - LOCATION: Event location
     * - STATUS: CONFIRMED (event confirmed)
     * 
     * Use case:
     * - Download .ics file button
     * - Email attachment (calendar invitation)
     * - Import batch events
     * 
     * @param array $event Event data dengan keys: id, title, description, location, start_at, end_at
     * @return string iCalendar file content (ready untuk file download atau email attachment)
     */
    public static function generateICalendar($event)
    {
        // Escape semua text fields untuk iCalendar format
        // Strip HTML tags dari description
        $title = self::escapeICalText($event['title']);
        $description = self::escapeICalText(strip_tags($event['description'] ?? ''));
        $location = self::escapeICalText($event['location'] ?? '');

        // Convert datetime dari MySQL ke iCalendar format (UTC)
        $startDate = self::formatDateForICal($event['start_at']);
        $endDate = self::formatDateForICal($event['end_at']);

        // Generate unique ID untuk event (required by RFC 5545)
        // Format: <hash>@eventsite.local
        $uid = md5($event['id'] . $event['title'] . $event['start_at']) . '@eventsite.local';

        // Current timestamp untuk DTSTAMP field (file creation time)
        $now = gmdate('Ymd\THis\Z');

        // Build iCalendar content sesuai RFC 5545
        // Line ending: \r\n (CRLF) adalah mandatory
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";                                    // iCal version
        $ical .= "PRODID:-//EventSite//Event Calendar//EN\r\n";       // Product identifier
        $ical .= "CALSCALE:GREGORIAN\r\n";                            // Calendar scale
        $ical .= "METHOD:PUBLISH\r\n";                                // Method: publish event
        $ical .= "BEGIN:VEVENT\r\n";                                  // Event start
        $ical .= "UID:" . $uid . "\r\n";                              // Unique ID
        $ical .= "DTSTAMP:" . $now . "\r\n";                          // Creation timestamp
        $ical .= "DTSTART:" . $startDate . "\r\n";                    // Event start time
        $ical .= "DTEND:" . $endDate . "\r\n";                        // Event end time
        $ical .= "SUMMARY:" . $title . "\r\n";                        // Event title
        $ical .= "DESCRIPTION:" . $description . "\r\n";              // Event description
        $ical .= "LOCATION:" . $location . "\r\n";                    // Event location
        $ical .= "STATUS:CONFIRMED\r\n";                              // Event confirmed
        $ical .= "SEQUENCE:0\r\n";                                    // Revision sequence
        $ical .= "END:VEVENT\r\n";                                    // Event end
        $ical .= "END:VCALENDAR\r\n";                                 // Calendar end

        return $ical;
    }

    /**
     * Format datetime untuk Google Calendar (YYYYMMDDTHHmmssZ)
     * 
     * Convert dari MySQL datetime (Asia/Jakarta) ke UTC format.
     * Format: 20240120T100000Z (T separator, Z for UTC)
     * 
     * @param string $datetime MySQL datetime string (YYYY-MM-DD HH:mm:ss)
     * @return string Formatted date untuk Google Calendar
     */
    private static function formatDateForGoogle($datetime)
    {
        // Parse datetime dengan timezone Asia/Jakarta
        $dt = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));

        // Convert ke UTC timezone (required by Google Calendar)
        $dt->setTimezone(new DateTimeZone('UTC'));

        // Format: YYYYMMDDTHHmmssZ
        return $dt->format('Ymd\THis\Z');
    }

    /**
     * Format datetime untuk iCalendar (YYYYMMDDTHHmmssZ)
     * 
     * Identical dengan formatDateForGoogle - same format required.
     * Kept separate untuk clarity dan future customization jika needed.
     * 
     * @param string $datetime MySQL datetime string (YYYY-MM-DD HH:mm:ss)
     * @return string Formatted date untuk iCalendar
     */
    private static function formatDateForICal($datetime)
    {
        // Parse datetime dengan timezone Asia/Jakarta
        $dt = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));

        // Convert ke UTC timezone (required by iCalendar spec)
        $dt->setTimezone(new DateTimeZone('UTC'));

        // Format: YYYYMMDDTHHmmssZ
        return $dt->format('Ymd\THis\Z');
    }

    /**
     * Escape text untuk iCalendar format
     * 
     * iCalendar spec (RFC 5545) requires escaping special characters:
     * - Backslash: \\ → \\\\
     * - Comma: , → \\,
     * - Semicolon: ; → \\;
     * - Newline: \\n → \\\\n
     * 
     * Also handle line folding (max 75 chars per line).
     * 
     * @param string $text Text yang akan di-escape
     * @return string Escaped text dengan line folding
     */
    private static function escapeICalText($text)
    {
        // Escape special characters sesuai RFC 5545
        $text = str_replace('\\', '\\\\', $text);   // Backslash harus pertama
        $text = str_replace(',', '\\,', $text);     // Comma
        $text = str_replace(';', '\\;', $text);     // Semicolon
        $text = str_replace("\n", '\\n', $text);    // Newline

        // Apply line folding (max 75 chars per line)
        return self::foldLine($text);
    }

    /**
     * Fold long lines untuk iCalendar format
     * 
     * RFC 5545 requires line length max 75 characters.
     * Continuation lines dimulai dengan space character.
     * 
     * Example:
     * DESCRIPTION:This is a very long description that exceeds 75 characters and
     *  needs to be wrapped to the next line with a leading space
     * 
     * @param string $text Text yang akan di-fold
     * @return string Folded text dengan CRLF line endings
     */
    private static function foldLine($text)
    {
        // Jika text <= 75 chars, tidak perlu folding
        if (strlen($text) <= 75) {
            return $text;
        }

        $folded = '';
        // Split text menjadi chunks of 75 characters
        $lines = str_split($text, 75);

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                // Continuation lines: CRLF + space + line content
                $folded .= "\r\n " . $line;
            } else {
                // First line: no prefix
                $folded .= $line;
            }
        }

        return $folded;
    }

    /**
     * Auto-add event to user's Google Calendar using OAuth
     * 
     * Method ini automatically add event ke Google Calendar user
     * menggunakan OAuth access token yang sudah disimpan.
     * 
     * Flow:
     * 1. Get valid access token (auto refresh jika expired)
     * 2. Create Google Calendar event object
     * 3. Insert event via Google Calendar API
     * 4. Return event ID atau error
     * 
     * Requirements:
     * - User harus sudah connect Google Calendar
     * - calendar_auto_add harus enabled
     * - Access token valid atau bisa di-refresh
     * 
     * @param int $user_id User ID yang akan auto-add event
     * @param array $event Event data dengan keys: id, title, description, location, start_at, end_at
     * @return array Result dengan keys: success (bool), event_id (string|null), error (string|null)
     */
    public static function autoAddToGoogleCalendar($user_id, $event)
    {
        try {
            // Load Google Calendar Controller
            require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/GoogleCalendarController.php';

            // Check if auto-add is enabled
            if (!GoogleCalendarController::isAutoAddEnabled($user_id)) {
                return [
                    'success' => false,
                    'event_id' => null,
                    'error' => 'Auto-add not enabled'
                ];
            }

            // Get valid access token (auto refresh if expired)
            $access_token = GoogleCalendarController::getValidAccessToken($user_id);
            if (!$access_token) {
                return [
                    'success' => false,
                    'event_id' => null,
                    'error' => 'Failed to get valid access token'
                ];
            }

            // Prepare event data untuk Google Calendar API
            $start_dt = new DateTime($event['start_at'], new DateTimeZone('Asia/Jakarta'));
            $end_dt = new DateTime($event['end_at'], new DateTimeZone('Asia/Jakarta'));

            $google_event = [
                'summary' => $event['title'],
                'description' => strip_tags($event['description'] ?? ''),
                'location' => $event['location'] ?? '',
                'start' => [
                    'dateTime' => $start_dt->format('c'), // ISO 8601 format
                    'timeZone' => 'Asia/Jakarta',
                ],
                'end' => [
                    'dateTime' => $end_dt->format('c'),
                    'timeZone' => 'Asia/Jakarta',
                ],
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60], // 1 day before
                        ['method' => 'popup', 'minutes' => 60], // 1 hour before
                    ],
                ],
            ];

            // Call Google Calendar API untuk insert event
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/calendar/v3/calendars/primary/events');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($google_event));

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200) {
                $result = json_decode($response, true);
                return [
                    'success' => true,
                    'event_id' => $result['id'] ?? null,
                    'error' => null
                ];
            } else {
                error_log("Google Calendar API Error (HTTP $http_code): $response");
                return [
                    'success' => false,
                    'event_id' => null,
                    'error' => "API returned HTTP $http_code"
                ];
            }
        } catch (Exception $e) {
            error_log("Auto-add Calendar Error: " . $e->getMessage());
            return [
                'success' => false,
                'event_id' => null,
                'error' => $e->getMessage()
            ];
        }
    }
}
