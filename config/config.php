<?php
/**
 * Active application config.
 * Reads from environment variables with safe defaults.
 * Override per-host by creating config/config.local.php (gitignored).
 */

$config = [
    'app' => [
        'name'     => 'Olympics Run 2026',
        'env'      => getenv('APP_ENV') ?: 'local',
        'debug'    => filter_var(getenv('APP_DEBUG') ?: '1', FILTER_VALIDATE_BOOLEAN),
        'timezone' => 'Asia/Kolkata',
        'base_url' => getenv('APP_BASE_URL') ?: '',
    ],
    'db' => [
        'host'    => getenv('DB_HOST') ?: '127.0.0.1',
        'port'    => (int)(getenv('DB_PORT') ?: 3306),
        'name'    => getenv('DB_NAME') ?: 'olympicsrun2026',
        'user'    => getenv('DB_USER') ?: 'root',
        'pass'    => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name'            => 'olyrun2026',
        'lifetime'        => 7200,
        'cookie_secure'   => filter_var(getenv('SESSION_SECURE') ?: '0', FILTER_VALIDATE_BOOLEAN),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    'mail' => [
        'from_name'  => 'Olympics Run 2026',
        'from_email' => 'no-reply@olympicsrun2026.in',
        'support'    => 'support@olympicsrun2026.in',
    ],
];

$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

return $config;
