<?php
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
