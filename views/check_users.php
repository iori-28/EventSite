<?php

/**
 * User Database Verification Script
 * 
 * Script utility untuk mengecek semua user di database dan verify password mereka.
 * Berguna untuk debugging authentication issues atau verify seeding berhasil.
 * 
 * Output:
 * - List semua user dengan email, role, dan status password verification
 * - Password 'password' adalah default password untuk development/testing
 * 
 * Cara pakai:
 * Akses via browser: http://localhost/EventSite/views/check_users.php
 * 
 * Security Note:
 * - File ini HARUS dihapus di production environment
 * - File ini expose semua user data dan password hash
 * 
 * @package EventSite
 * @category Utility
 * @author EventSite Team
 */

// Load database connection
require_once 'config/db.php';
$db = Database::connect();

// Query semua user dari database
// Ambil id, name, email, role, dan password hash untuk verification
$users = $db->query("SELECT id, name, email, role, password FROM users")->fetchAll(PDO::FETCH_ASSOC);

// Loop dan display info setiap user
foreach ($users as $u) {
    // Verify password 'password' dengan hash yang tersimpan di database
    // password_verify() akan return true jika password match dengan hash
    $passCheck = password_verify('password', $u['password']) ? '✅ OK' : '❌ FAIL';

    echo "User: {$u['email']} | Role: {$u['role']} | Pass Check ('password'): {$passCheck}\n";
}
