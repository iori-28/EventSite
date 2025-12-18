<?php

/**
 * Google OAuth Login Initiator
 * 
 * This script generates the Google OAuth URL and redirects the user to Google's login page.
 * After user authorizes, Google will redirect back to google-callback.php
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/env.php';

// Build OAuth URL
$params = [
    'client_id' => GOOGLE_OAUTH_CLIENT_ID,
    'redirect_uri' => GOOGLE_OAUTH_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => implode(' ', [
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
        'openid'
    ]),
    'access_type' => 'offline',
    'prompt' => 'consent'
];

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

// Redirect to Google
header('Location: ' . $authUrl);
exit;
