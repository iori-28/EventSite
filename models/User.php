<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';

/**
 * User Model
 * 
 * Model untuk mengelola data pengguna (users table).
 * Menangani autentikasi, registrasi, dan query data user.
 * Supports 3 role types: admin, panitia, user
 * 
 * @package EventSite\Models
 * @author EventSite Team
 */
class User
{
    /**
     * Cari user berdasarkan email address
     * 
     * Method ini digunakan untuk autentikasi (login) dan validasi email unik.
     * Email adalah unique identifier dalam sistem.
     * 
     * @param string $email Email address user yang dicari
     * @return array|false User data sebagai associative array, atau false jika tidak ditemukan
     */
    public static function findByEmail($email)
    {
        $db = Database::connect();

        // Query user berdasarkan email (unique field)
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Buat user baru (registrasi)
     * 
     * Method ini menangani registrasi user baru dengan:
     * - Password hashing menggunakan PASSWORD_DEFAULT (bcrypt)
     * - Role default adalah 'user' (bisa dioverride untuk admin/panitia)
     * - Email harus unique (handled by database constraint)
     * 
     * @param string $name Nama lengkap user
     * @param string $email Email address (harus unique)
     * @param string $password Password plain text (akan di-hash)
     * @param string $role Role user: 'admin', 'panitia', atau 'user' (default)
     * @return bool True jika berhasil create user, false jika gagal
     */
    public static function create($name, $email, $password, $role = 'user')
    {
        $db = Database::connect();

        // Insert user baru ke database
        $stmt = $db->prepare(
            "INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)"
        );

        return $stmt->execute([
            $name,
            $email,
            password_hash($password, PASSWORD_DEFAULT), // Hash password menggunakan bcrypt
            $role
        ]);
    }
}
