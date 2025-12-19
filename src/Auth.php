<?php
declare(strict_types=1);

namespace App;

class Auth
{
    private const SESSION_LIFETIME = 7200; // 2 hours
    private const CSRF_TOKEN_KEY = 'csrf_token';
    private const AUTH_KEY = 'authenticated';
    private const AUTH_TIME_KEY = 'auth_time';

    public static function init(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => self::SESSION_LIFETIME,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        session_start();

        // Check session timeout
        if (isset($_SESSION[self::AUTH_TIME_KEY])) {
            $elapsed = time() - $_SESSION[self::AUTH_TIME_KEY];
            if ($elapsed > self::SESSION_LIFETIME) {
                self::logout();
            }
        }
    }

    public static function login(string $password): bool
    {
        self::init();

        $adminPassword = Config::get('ADMIN_PASSWORD');

        if ($adminPassword === null || $adminPassword === '') {
            return false;
        }

        if (hash_equals($adminPassword, $password)) {
            // Regenerate session ID on login for security
            session_regenerate_id(true);

            $_SESSION[self::AUTH_KEY] = true;
            $_SESSION[self::AUTH_TIME_KEY] = time();

            // Generate new CSRF token
            self::regenerateCsrfToken();

            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        self::init();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public static function check(): bool
    {
        self::init();

        if (!isset($_SESSION[self::AUTH_KEY]) || $_SESSION[self::AUTH_KEY] !== true) {
            return false;
        }

        // Check session timeout
        if (isset($_SESSION[self::AUTH_TIME_KEY])) {
            $elapsed = time() - $_SESSION[self::AUTH_TIME_KEY];
            if ($elapsed > self::SESSION_LIFETIME) {
                self::logout();
                return false;
            }

            // Update last activity time
            $_SESSION[self::AUTH_TIME_KEY] = time();
        }

        return true;
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            if (isAjax()) {
                jsonError('Unauthorized', 401);
            }
            redirect(baseUrl() . '/login');
        }
    }

    public static function getCsrfToken(): string
    {
        self::init();

        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            self::regenerateCsrfToken();
        }

        return $_SESSION[self::CSRF_TOKEN_KEY];
    }

    public static function regenerateCsrfToken(): string
    {
        self::init();
        $_SESSION[self::CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::CSRF_TOKEN_KEY];
    }

    public static function validateCsrf(?string $token = null): bool
    {
        self::init();

        if ($token === null) {
            // Check header first, then POST data
            $token = $_SERVER['HTTP_X_CSRF_TOKEN']
                ?? $_POST['csrf_token']
                ?? null;
        }

        if ($token === null || !isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            return false;
        }

        return hash_equals($_SESSION[self::CSRF_TOKEN_KEY], $token);
    }

    public static function requireCsrf(): void
    {
        if (!self::validateCsrf()) {
            jsonError('Invalid CSRF token', 403);
        }
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(self::getCsrfToken()) . '">';
    }

    public static function csrfMeta(): string
    {
        return '<meta name="csrf-token" content="' . e(self::getCsrfToken()) . '">';
    }
}
