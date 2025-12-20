<?php

/**
 * Google Calendar Controller
 * 
 * Controller untuk mengelola Google Calendar OAuth integration.
 * Handle connection, disconnection, dan auto-add events to user's Google Calendar.
 * 
 * Features:
 * - OAuth flow untuk connect Google Calendar
 * - Store & refresh OAuth tokens
 * - Auto-add events saat user register
 * - Disconnect calendar connection
 * - Check connection status
 * 
 * OAuth Scopes Required:
 * - https://www.googleapis.com/auth/calendar.events (read/write calendar events)
 * 
 * @package EventSite\Controllers
 * @author EventSite Team
 * @version 1.0
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/env.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/vendor/autoload.php';

class GoogleCalendarController
{
    /**
     * Initialize Google Client untuk Calendar API
     * 
     * Setup Google Client dengan OAuth credentials dan Calendar scope.
     * 
     * @return Google_Client Configured Google Client instance
     */
    private static function getGoogleClient()
    {
        $client = new Google_Client();
        $client->setClientId(GOOGLE_OAUTH_CLIENT_ID);
        $client->setClientSecret(GOOGLE_OAUTH_CLIENT_SECRET);
        $client->setRedirectUri(GOOGLE_OAUTH_REDIRECT_URI);
        $client->addScope('https://www.googleapis.com/auth/calendar.events');
        $client->setAccessType('offline'); // Get refresh token
        $client->setPrompt('consent'); // Force consent untuk dapat refresh token

        return $client;
    }

    /**
     * Generate OAuth URL untuk connect Google Calendar
     * 
     * User klik link ini untuk authorize app access ke Google Calendar.
     * 
     * @return string OAuth authorization URL
     */
    public static function getAuthUrl()
    {
        $client = self::getGoogleClient();
        return $client->createAuthUrl();
    }

    /**
     * Handle OAuth callback dan store tokens
     * 
     * Setelah user authorize, Google redirect ke sini dengan auth code.
     * Exchange auth code untuk access token & refresh token, lalu simpan ke database.
     * 
     * @param int $user_id User ID yang connect calendar
     * @param string $auth_code Authorization code dari Google
     * @return bool True jika berhasil, false jika gagal
     */
    public static function handleCallback($user_id, $auth_code)
    {
        try {
            $client = self::getGoogleClient();

            // Exchange authorization code untuk tokens
            $token = $client->fetchAccessTokenWithAuthCode($auth_code);

            if (isset($token['error'])) {
                error_log("Google Calendar OAuth Error: " . $token['error']);
                return false;
            }

            $access_token = $token['access_token'];
            $refresh_token = $token['refresh_token'] ?? null;

            // Calculate token expiry time
            $expires_in = $token['expires_in'] ?? 3600; // Default 1 hour
            $expires_at = date('Y-m-d H:i:s', time() + $expires_in);

            // Save tokens to database
            $db = Database::connect();
            $stmt = $db->prepare("
                UPDATE users 
                SET google_calendar_token = ?,
                    google_calendar_refresh_token = ?,
                    google_calendar_token_expires = ?,
                    calendar_auto_add = 1,
                    calendar_connected_at = NOW()
                WHERE id = ?
            ");

            $stmt->execute([
                $access_token,
                $refresh_token,
                $expires_at,
                $user_id
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Google Calendar Connection Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Disconnect Google Calendar
     * 
     * Remove OAuth tokens dan disable auto-add feature.
     * 
     * @param int $user_id User ID yang akan disconnect
     * @return bool True jika berhasil
     */
    public static function disconnect($user_id)
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                UPDATE users 
                SET google_calendar_token = NULL,
                    google_calendar_refresh_token = NULL,
                    google_calendar_token_expires = NULL,
                    calendar_auto_add = 0,
                    calendar_connected_at = NULL
                WHERE id = ?
            ");

            $stmt->execute([$user_id]);
            return true;
        } catch (Exception $e) {
            error_log("Google Calendar Disconnect Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get valid access token (auto refresh jika expired)
     * 
     * Check apakah token sudah expired. Jika expired, refresh menggunakan refresh token.
     * 
     * @param int $user_id User ID
     * @return string|false Access token jika berhasil, false jika gagal
     */
    public static function getValidAccessToken($user_id)
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT google_calendar_token, 
                       google_calendar_refresh_token,
                       google_calendar_token_expires
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !$user['google_calendar_token']) {
                return false; // No token stored
            }

            $now = new DateTime();
            $expires_at = new DateTime($user['google_calendar_token_expires']);

            // Check if token is expired
            if ($now >= $expires_at) {
                // Token expired, refresh it
                if (!$user['google_calendar_refresh_token']) {
                    return false; // Cannot refresh without refresh token
                }

                $client = self::getGoogleClient();
                $client->refreshToken($user['google_calendar_refresh_token']);
                $new_token = $client->getAccessToken();

                if (!$new_token || isset($new_token['error'])) {
                    error_log("Token refresh failed for user $user_id");
                    return false;
                }

                // Update database dengan token baru
                $new_access_token = $new_token['access_token'];
                $new_expires_in = $new_token['expires_in'] ?? 3600;
                $new_expires_at = date('Y-m-d H:i:s', time() + $new_expires_in);

                $updateStmt = $db->prepare("
                    UPDATE users 
                    SET google_calendar_token = ?,
                        google_calendar_token_expires = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$new_access_token, $new_expires_at, $user_id]);

                return $new_access_token;
            }

            // Token masih valid
            return $user['google_calendar_token'];
        } catch (Exception $e) {
            error_log("Get Access Token Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check apakah user punya Google Calendar connected & auto-add enabled
     * 
     * @param int $user_id User ID
     * @return bool True jika connected & enabled, false jika tidak
     */
    public static function isAutoAddEnabled($user_id)
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT calendar_auto_add, google_calendar_token
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return $user && $user['calendar_auto_add'] == 1 && !empty($user['google_calendar_token']);
        } catch (Exception $e) {
            error_log("Check Auto-Add Status Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Toggle auto-add preference
     * 
     * User bisa disable auto-add tanpa disconnect calendar.
     * 
     * @param int $user_id User ID
     * @param bool $enabled True untuk enable, false untuk disable
     * @return bool True jika berhasil
     */
    public static function setAutoAdd($user_id, $enabled)
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                UPDATE users 
                SET calendar_auto_add = ?
                WHERE id = ?
            ");

            $stmt->execute([$enabled ? 1 : 0, $user_id]);
            return true;
        } catch (Exception $e) {
            error_log("Set Auto-Add Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get calendar connection info untuk user
     * 
     * @param int $user_id User ID
     * @return array Connection info dengan keys: connected, auto_add, connected_at
     */
    public static function getConnectionInfo($user_id)
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT google_calendar_token,
                       calendar_auto_add,
                       calendar_connected_at
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'connected' => !empty($user['google_calendar_token']),
                'auto_add' => $user['calendar_auto_add'] == 1,
                'connected_at' => $user['calendar_connected_at']
            ];
        } catch (Exception $e) {
            error_log("Get Connection Info Error: " . $e->getMessage());
            return [
                'connected' => false,
                'auto_add' => false,
                'connected_at' => null
            ];
        }
    }
}
