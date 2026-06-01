<?php
/**
 * App bootstrap. Loaded by public/index.php on every request.
 * Sets up config, error mode, autoloader, session, and DB lazily.
 */

declare(strict_types=1);

// Config -------------------------------------------------------------------
$GLOBALS['APP_CONFIG'] = require __DIR__ . '/../config/config.php';
$cfg = $GLOBALS['APP_CONFIG'];

date_default_timezone_set($cfg['app']['timezone']);

if ($cfg['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php-error.log');
}

// Autoload classes from includes/, controllers/, models/.
spl_autoload_register(function (string $class): void {
    $bases = [
        __DIR__ . '/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../models/',
    ];
    foreach ($bases as $base) {
        $file = $base . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

// Composer autoload (PHPMailer etc.) — optional; the app continues to boot
// even before `composer install` is run, with Mailer degrading gracefully.
$__vendor = __DIR__ . '/../vendor/autoload.php';
if (is_file($__vendor)) {
    require_once $__vendor;
}

// Helpers (functions, can't be autoloaded)
require_once __DIR__ . '/helpers.php';

// Security response headers — applied to every HTTP response (no-op in CLI).
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    if (($cfg['app']['env'] ?? 'local') === 'production'
        && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Session ------------------------------------------------------------------
// Skipped in CLI: cron scripts (e.g. force-submit-expired) have no user
// session and writing a session cookie under SAPI=cli is noisy and pointless.
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    // Use an app-local, writable session store rather than the host default,
    // which on shared hosting is frequently unwritable/shared and silently
    // drops session data — the usual cause of "CSRF token mismatch" on login.
    // Pick the first writable private store. NEVER fall back to the host's
    // default session.save_path — on shared hosting it is frequently
    // unwritable or shared between accounts, which silently drops the session
    // between the login form (GET) and its submit (POST) and surfaces as
    // "CSRF token mismatch". A namespaced dir under the system temp dir is the
    // last resort, still private to this app.
    $candidates = array_values(array_filter([
        $cfg['session']['save_path'] ?? '',
        sys_get_temp_dir() . '/olyrun2026_sessions',
    ]));
    foreach ($candidates as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            session_save_path($dir);
            ini_set('session.gc_maxlifetime', (string)$cfg['session']['lifetime']);
            break;
        }
        error_log('Session save_path not writable, trying next: ' . $dir);
    }

    session_name($cfg['session']['name']);
    session_set_cookie_params([
        'lifetime' => $cfg['session']['lifetime'],
        'path'     => '/',
        'domain'   => '',
        'secure'   => $cfg['session']['cookie_secure'],
        'httponly' => $cfg['session']['cookie_httponly'],
        'samesite' => $cfg['session']['cookie_samesite'],
    ]);
    session_start();
}
