<?php

/**
 * Google Calendar Connect API
 * 
 * Redirect user ke Google OAuth untuk connect Google Calendar.
 * Setelah user authorize, Google akan redirect ke google-calendar-callback.php
 * 
 * Usage:
 * <a href="api/google-calendar-connect.php">Connect Google Calendar</a>
 */

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/GoogleCalendarController.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../index.php?page=login');
    exit;
}

// Get OAuth authorization URL
$auth_url = GoogleCalendarController::getAuthUrl();

// Redirect to Google OAuth
header('Location: ' . $auth_url);
exit;
