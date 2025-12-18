<?php

/**
 * Database Connection Class
 * 
 * Singleton-style PDO database connection manager.
 * Connects to MySQL/MariaDB menggunakan credentials dari env.php.
 * 
 * Features:
 * - PDO with prepared statements (SQL injection prevention)
 * - Error mode: PDO::ERRMODE_EXCEPTION
 * - UTF-8 charset for international character support
 * - Persistent connection for performance
 * - Support both web and CLI context
 * 
 * Usage:
 * $db = Database::connect();
 * $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
 * $stmt->execute([$user_id]);
 * $user = $stmt->fetch(PDO::FETCH_ASSOC);
 * 
 * @package EventSite\Config
 * @author EventSite Team
 * @version 1.0
 */

// Support both web and CLI context
require_once __DIR__ . '/env.php';

class Database
{
    private static $conn = null;

    public static function connect()
    {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                die("Unable to connect to database. Please try again later.");
            }
        }
        return self::$conn;
    }
}
