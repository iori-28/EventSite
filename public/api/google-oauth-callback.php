<?php

/**
 * Universal Google OAuth Callback Handler
 * 
 * Handle semua OAuth callback dari Google:
 * 1. Login/Register - jika user belum login
 * 2. Calendar Connection - jika user sudah login
 * 
 * Flow detection:
 * - No session → LOGIN flow
 * - Has session → CALENDAR CONNECTION flow
 * 
 * @package EventSite\API
 * @author EventSite Team
 */

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/env.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/vendor/autoload.php';

use Google\Auth\OAuth2;

// Check if authorization code is present
if (!isset($_GET['code'])) {
    header('Location: ../index.php?page=login&error=oauth_failed');
    exit;
}

$auth_code = $_GET['code'];

/* ============================================
   DETECT FLOW: Login vs Calendar Connection
   ============================================ */

// Jika user BELUM login → LOGIN/REGISTER FLOW
if (!isset($_SESSION['user'])) {
    handleLoginFlow($auth_code);
} else {
    // Jika user SUDAH login → CALENDAR CONNECTION FLOW
    handleCalendarFlow($auth_code, $_SESSION['user']['id']);
}

/* ============================================
   LOGIN/REGISTER FLOW
   ============================================ */
function handleLoginFlow($auth_code)
{
    try {
        // Initialize OAuth2 client untuk get user info
        $oauth = new OAuth2([
            'clientId' => GOOGLE_OAUTH_CLIENT_ID,
            'clientSecret' => GOOGLE_OAUTH_CLIENT_SECRET,
            'authorizationUri' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'tokenCredentialUri' => 'https://oauth2.googleapis.com/token',
            'redirectUri' => GOOGLE_OAUTH_REDIRECT_URI,
            'scope' => [
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile',
            ],
        ]);

        // Exchange authorization code for access token
        $oauth->setCode($auth_code);
        $authToken = $oauth->fetchAuthToken();

        if (!isset($authToken['access_token'])) {
            throw new Exception('Failed to obtain access token');
        }

        $accessToken = $authToken['access_token'];

        // Get user info from Google
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception('Failed to get user info from Google');
        }

        $userInfo = json_decode($response, true);

        if (!isset($userInfo['email'])) {
            throw new Exception('Email not provided by Google');
        }

        // Extract user data
        $googleId = $userInfo['id'];
        $email = $userInfo['email'];
        $name = $userInfo['name'] ?? '';
        $profilePicture = $userInfo['picture'] ?? null;

        // Connect to database
        $db = Database::connect();

        // Check if user already exists (by google_id or email)
        $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
        $stmt->execute([$googleId, $email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            // USER EXISTS - Update google_id and profile picture
            $updateStmt = $db->prepare("
                UPDATE users 
                SET google_id = ?, 
                    profile_picture = ?, 
                    oauth_provider = 'google' 
                WHERE id = ?
            ");
            $updateStmt->execute([$googleId, $profilePicture, $existingUser['id']]);

            // Login the user
            $_SESSION['user'] = [
                'id' => $existingUser['id'],
                'email' => $existingUser['email'],
                'name' => $existingUser['name'],
                'role' => $existingUser['role'],
                'profile_picture' => $profilePicture
            ];

            // Redirect based on role
            $redirectPage = match ($existingUser['role']) {
                'admin' => 'admin_dashboard',
                'panitia' => 'panitia_dashboard',
                default => 'user_dashboard'
            };

            $baseUrl = rtrim(APP_BASE_URL, '/');
            header("Location: $baseUrl/index.php?page=$redirectPage&oauth=success");
            exit;
        } else {
            // USER DOESN'T EXIST - Create new account
            $role = 'user';
            $randomPassword = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);

            $insertStmt = $db->prepare("
                INSERT INTO users (name, email, password, role, google_id, profile_picture, oauth_provider, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'google', NOW())
            ");

            $insertStmt->execute([
                $name,
                $email,
                $hashedPassword,
                $role,
                $googleId,
                $profilePicture
            ]);

            $newUserId = $db->lastInsertId();

            // Auto login the new user
            $_SESSION['user'] = [
                'id' => $newUserId,
                'email' => $email,
                'name' => $name,
                'role' => $role,
                'profile_picture' => $profilePicture
            ];

            // Redirect to user dashboard
            $baseUrl = rtrim(APP_BASE_URL, '/');
            header("Location: $baseUrl/index.php?page=user_dashboard&oauth=registered");
            exit;
        }
    } catch (Exception $e) {
        error_log('Google OAuth Login Error: ' . $e->getMessage());
        $_SESSION['error_message'] = 'Login dengan Google gagal: ' . $e->getMessage();

        $baseUrl = rtrim(APP_BASE_URL, '/');
        header("Location: $baseUrl/index.php?page=login&oauth=error");
        exit;
    }
}

/* ============================================
   CALENDAR CONNECTION FLOW
   ============================================ */
function handleCalendarFlow($auth_code, $user_id)
{
    try {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/GoogleCalendarController.php';

        // Exchange code untuk tokens & save ke database
        $result = GoogleCalendarController::handleCallback($user_id, $auth_code);

        if ($result) {
            // Success - redirect ke dashboard
            header('Location: ../index.php?page=user_dashboard&calendar_connected=1');
        } else {
            // Failed
            header('Location: ../index.php?page=user_dashboard&error=calendar_connection_failed');
        }
    } catch (Exception $e) {
        error_log('Google Calendar Connection Error: ' . $e->getMessage());
        header('Location: ../index.php?page=user_dashboard&error=calendar_connection_failed');
    }
    exit;
}
