<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/User.php';

/**
 * Authentication Controller
 * 
 * Controller untuk mengelola autentikasi user (login, logout, register).
 * Menangani session management dan password verification.
 * 
 * @package EventSite\Controllers
 * @author EventSite Team
 */
class AuthController
{

    /**
     * Login user dan buat session
     * 
     * Method ini melakukan autentikasi dengan:
     * 1. Cari user berdasarkan email
     * 2. Verify password menggunakan password_verify() (bcrypt)
     * 3. Buat session dengan data user (id, name, email, role)
     * 
     * Session data digunakan untuk:
     * - Check apakah user sudah login
     * - Determine role (admin/panitia/user) untuk authorization
     * - Display user info di navbar
     * 
     * @param string $email Email address user
     * @param string $password Password plain text (akan diverify dengan hash)
     * @return bool True jika login berhasil, false jika email tidak ditemukan atau password salah
     */
    public static function login($email, $password)
    {
        // Cari user berdasarkan email
        $user = User::findByEmail($email);

        // Validasi: user harus exist dan password harus match
        // password_verify() membandingkan plain text dengan bcrypt hash
        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        // Buat session dengan data user
        // Session ini akan digunakan untuk authorization di semua halaman
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']  // admin, panitia, atau user
        ];

        return true;
    }

    /**
     * Logout user dan destroy session
     * 
     * Method ini melakukan:
     * 1. Clear semua data session
     * 2. Delete session cookie di browser
     * 3. Destroy session di server
     * 
     * Setelah logout, user akan redirect ke login page.
     * 
     * @return void
     */
    public static function logout()
    {
        // Clear semua session variables
        $_SESSION = array();

        // Delete session cookie di browser
        // Ini untuk security: prevent session hijacking
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,  // Set expired time di masa lalu
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy session di server
        session_destroy();
    }

    /**
     * Register user baru
     * 
     * Method ini melakukan registrasi dengan validation:
     * - Email harus unique (check di database)
     * - Password akan di-hash sebelum disimpan
     * - Role default adalah 'user' (bisa override untuk admin/panitia)
     * 
     * Return codes:
     * - "EMAIL_EXISTS": Email sudah terdaftar
     * - "REGISTER_SUCCESS": Registrasi berhasil
     * - "REGISTER_FAILED": Database insert gagal
     * 
     * @param string $name Nama lengkap user
     * @param string $email Email address (harus unique)
     * @param string $password Password plain text (akan di-hash)
     * @param string $role Role user: 'admin', 'panitia', atau 'user' (default)
     * @return string Status code
     */
    public static function register($name, $email, $password, $role = 'user')
    {
        // Validasi: email harus unique
        // Check apakah email sudah terdaftar
        if (User::findByEmail($email)) {
            return 'EMAIL_EXISTS';
        }

        // Buat user baru (password akan di-hash di User::create())
        $result = User::create($name, $email, $password, $role);

        // Return status code berdasarkan hasil insert
        return $result ? 'REGISTER_SUCCESS' : 'REGISTER_FAILED';
    }
}
