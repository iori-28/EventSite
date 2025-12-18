<?php

/**
 * Authentication API Endpoint
 * 
 * RESTful API untuk mengelola user authentication.
 * Mendukung actions: login, register, logout, google-login
 * 
 * Features:
 * - Traditional email/password authentication
 * - Google OAuth 2.0 integration
 * - Session management
 * - Password hashing dengan bcrypt (PASSWORD_DEFAULT)
 * 
 * Authentication: Not required (this is the auth endpoint)
 * 
 * Response Format: Plain text status codes
 * Success: LOGIN_SUCCESS, REGISTER_SUCCESS, LOGOUT_SUCCESS
 * Error: LOGIN_FAILED, USER_EXISTS, REGISTER_FAILED
 * 
 * @package EventSite\API
 * @author EventSite Team
 * @version 2.0
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/controllers/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($_POST['action'] === 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        if (AuthController::login($email, $password)) {
            echo "LOGIN_SUCCESS";
        } else {
            echo "LOGIN_FAILED";
        }
    }

    if ($_POST['action'] === 'register') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        echo AuthController::register($name, $email, $password);
    }

    if ($_POST['action'] === 'logout') {
        AuthController::logout();
        // Clear any previous output (warnings, whitespace)
        ob_clean();
        echo "LOGOUT_SUCCESS";
        exit;
    }
}
