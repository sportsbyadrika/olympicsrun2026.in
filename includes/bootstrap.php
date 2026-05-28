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

// Helpers (functions, can't be autoloaded)
require_once __DIR__ . '/helpers.php';

// Session ------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
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
