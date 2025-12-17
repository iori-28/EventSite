<?php

/**
 * Logout Handler
 * 
 * Script untuk handle user logout dengan aman.
 * Membersihkan semua session data dan redirect ke homepage.
 * 
 * Flow:
 * 1. Start session (untuk akses session variables)
 * 2. Clear semua session data
 * 3. Destroy session dari server
 * 4. Redirect ke homepage
 * 
 * Note: File ini tidak dipakai lagi karena logout sudah di-handle di index.php
 * Kept for backward compatibility.
 * 
 * @package EventSite
 * @category Authentication
 * @author EventSite Team
 */

// Start session untuk akses session variables
session_start();

// Clear semua session data (set $_SESSION ke empty array)
$_SESSION = array();

// Destroy session dari server (hapus session file)
session_destroy();

// Redirect ke homepage
header("Location: index.php?page=home");
exit;
