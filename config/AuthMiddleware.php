<?php
/**
 * Authentication Middleware
 * 
 * Ensures user session is always in sync with database
 * Call this at the top of every protected page
 */

class Auth {
    /**
     * Check if user is authenticated and refresh session from database
     * 
     * @param string|array $allowed_roles Role(s) allowed to access this page
     * @return array User data from database
     */
    public static function check($allowed_roles = null) {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            header('Location: index.php?page=login');
            exit;
        }
        
        // Refresh user data from database to keep session in sync
        try {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
            $db = Database::connect();
            
            $stmt = $db->prepare("
                SELECT id, name, email, role, profile_picture, oauth_provider, google_id 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user']['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // User not found in database (deleted?)
            if (!$user) {
                session_destroy();
                header('Location: index.php?page=login');
                exit;
            }
            
            // Update session with fresh data from database
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'profile_picture' => $user['profile_picture'],
                'oauth_provider' => $user['oauth_provider'],
                'google_id' => $user['google_id']
            ];
            
            // Check if user has required role
            if ($allowed_roles !== null) {
                $allowed = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
                
                if (!in_array($user['role'], $allowed)) {
                    header('Location: index.php?page=login');
                    exit;
                }
            }
            
            return $user;
            
        } catch (Exception $e) {
            error_log("Auth check failed: " . $e->getMessage());
            session_destroy();
            header('Location: index.php?page=login');
            exit;
        }
    }
    
    /**
     * Check if user is guest (not logged in)
     */
    public static function guest() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return !isset($_SESSION['user']);
    }
    
    /**
     * Get current user from session
     */
    public static function user() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Logout current user
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        session_destroy();
    }
}
