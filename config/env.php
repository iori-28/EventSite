<?php

$env = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/EventSite/.env');

/* DATABASE */
define('DB_HOST', $env['DB_HOST']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);
define('DB_NAME', $env['DB_NAME']);

/* MAIL */
define('MAIL_HOST', $env['MAIL_HOST']);
define('MAIL_PORT', $env['MAIL_PORT']);
define('MAIL_USERNAME', $env['MAIL_USERNAME']);
define('MAIL_PASSWORD', $env['MAIL_PASSWORD']);
define('MAIL_FROM_NAME', $env['MAIL_FROM_NAME']);

/*EVENT REMAINDER*/
define('EVENT_REMINDER_ENABLED', $env['EVENT_REMINDER_ENABLED']);
define('EVENT_REMINDER_HOURS', $env['EVENT_REMINDER_HOURS']);


/* APP */
define('APP_BASE_URL', $env['APP_BASE_URL']);

/* GOOGLE API */
define('GOOGLE_CALENDAR_API_KEY', $env['GOOGLE_CALENDAR_API_KEY']);
