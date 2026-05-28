<?php
/**
 * Per-session CSRF token. Generated lazily, validated with hash_equals.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    public static function check(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    public static function requireValidPost(): void
    {
        $token = $_POST['_csrf'] ?? null;
        if (!self::check($token)) {
            http_response_code(419);
            exit('CSRF token mismatch.');
        }
    }

    /** Accept token from either POST body or X-CSRF-Token header. */
    public static function requireValidRequest(): void
    {
        $token = $_POST['_csrf']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;
        if (!self::check($token)) {
            http_response_code(419);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'CSRF token mismatch']);
            exit;
        }
    }
}
