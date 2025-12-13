<?php

/**
 * Calendar Service
 * Handles Google Calendar integration and iCalendar file generation
 */

class CalendarService
{
    /**
     * Generate Google Calendar URL
     * 
     * @param array $event Event data with title, description, location, start_at, end_at
     * @return string Google Calendar URL
     */
    public static function generateGoogleCalendarUrl($event)
    {
        $title = urlencode($event['title']);
        $description = urlencode(strip_tags($event['description'] ?? ''));
        $location = urlencode($event['location'] ?? '');

        // Convert datetime to Google Calendar format (YYYYMMDDTHHmmssZ)
        $startDate = self::formatDateForGoogle($event['start_at']);
        $endDate = self::formatDateForGoogle($event['end_at']);

        $dates = $startDate . '/' . $endDate;

        // Build Google Calendar URL
        $url = 'https://calendar.google.com/calendar/render?action=TEMPLATE';
        $url .= '&text=' . $title;
        $url .= '&dates=' . $dates;
        $url .= '&details=' . $description;
        $url .= '&location=' . $location;
        $url .= '&sf=true&output=xml';

        return $url;
    }

    /**
     * Generate iCalendar (.ics) file content
     * 
     * @param array $event Event data
     * @return string iCalendar file content
     */
    public static function generateICalendar($event)
    {
        $title = self::escapeICalText($event['title']);
        $description = self::escapeICalText(strip_tags($event['description'] ?? ''));
        $location = self::escapeICalText($event['location'] ?? '');

        // Convert datetime to iCalendar format
        $startDate = self::formatDateForICal($event['start_at']);
        $endDate = self::formatDateForICal($event['end_at']);

        // Generate unique ID
        $uid = md5($event['id'] . $event['title'] . $event['start_at']) . '@eventsite.local';

        // Current timestamp for DTSTAMP
        $now = gmdate('Ymd\THis\Z');

        // Build iCalendar content
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//EventSite//Event Calendar//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . $uid . "\r\n";
        $ical .= "DTSTAMP:" . $now . "\r\n";
        $ical .= "DTSTART:" . $startDate . "\r\n";
        $ical .= "DTEND:" . $endDate . "\r\n";
        $ical .= "SUMMARY:" . $title . "\r\n";
        $ical .= "DESCRIPTION:" . $description . "\r\n";
        $ical .= "LOCATION:" . $location . "\r\n";
        $ical .= "STATUS:CONFIRMED\r\n";
        $ical .= "SEQUENCE:0\r\n";
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        return $ical;
    }

    /**
     * Format datetime for Google Calendar (YYYYMMDDTHHmmssZ)
     * 
     * @param string $datetime MySQL datetime string
     * @return string Formatted date
     */
    private static function formatDateForGoogle($datetime)
    {
        $dt = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Ymd\THis\Z');
    }

    /**
     * Format datetime for iCalendar (YYYYMMDDTHHmmssZ)
     * 
     * @param string $datetime MySQL datetime string
     * @return string Formatted date
     */
    private static function formatDateForICal($datetime)
    {
        $dt = new DateTime($datetime, new DateTimeZone('Asia/Jakarta'));
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt->format('Ymd\THis\Z');
    }

    /**
     * Escape text for iCalendar format
     * 
     * @param string $text Text to escape
     * @return string Escaped text
     */
    private static function escapeICalText($text)
    {
        // Replace special characters
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\n", '\\n', $text);

        // Limit line length to 75 characters as per RFC 5545
        return self::foldLine($text);
    }

    /**
     * Fold long lines for iCalendar format (max 75 chars per line)
     * 
     * @param string $text Text to fold
     * @return string Folded text
     */
    private static function foldLine($text)
    {
        if (strlen($text) <= 75) {
            return $text;
        }

        $folded = '';
        $lines = str_split($text, 75);

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $folded .= "\r\n " . $line; // Continuation lines start with space
            } else {
                $folded .= $line;
            }
        }

        return $folded;
    }
}
