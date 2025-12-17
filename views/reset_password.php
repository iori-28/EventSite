<?php

/**
 * Password Reset Utility Script
 * 
 * Script utility untuk mereset password user yang lupa password.
 * File ini untuk development/testing purposes, TIDAK untuk production.
 * 
 * Cara pakai:
 * 1. Ubah variable $email dengan email user yang mau direset
 * 2. Ubah variable $new_pass dengan password baru
 * 3. Akses file ini via browser: http://localhost/EventSite/views/reset_password.php
 * 4. Password akan di-hash dengan bcrypt dan disimpan ke database
 * 
 * Security Note:
 * - File ini harus dihapus atau dipindah ke folder yang tidak accessible via web di production
 * - Gunakan proper password reset flow dengan email verification untuk production
 * 
 * @package EventSite
 * @category Utility
 * @author EventSite Team
 */

// Load database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/config/db.php';
$db = Database::connect();

// ============================================
// CONFIGURATION - Edit sesuai kebutuhan
// ============================================
$email = 'user@example.com';  // Email user yang mau direset
$new_pass = 'password';        // Password baru (plain text, akan di-hash otomatis)

// Hash password dengan bcrypt (PASSWORD_DEFAULT = bcrypt)
// Cost factor default = 10 (balance antara security dan performance)
$hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);

// Update password di database
$stmt = $db->prepare("UPDATE users SET password = :password WHERE email = :email");
$result = $stmt->execute([':password' => $hashed_pass, ':email' => $email]);

// Display result
if ($result) {
    echo "✅ Password for $email has been reset to '$new_pass'.";
} else {
    echo "❌ Failed to reset password.";
}
