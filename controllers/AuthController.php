<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/EventSite/models/User.php';

class AuthController
{

    public static function login($email, $password)
    {
        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        return true;
    }

    public static function logout()
    {
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

    public static function register($name, $email, $password, $role = 'user')
    {
        if (User::findByEmail($email)) {
            return 'EMAIL_EXISTS';
        }

        $result = User::create($name, $email, $password, $role);

        return $result ? 'REGISTER_SUCCESS' : 'REGISTER_FAILED';
    }
}
