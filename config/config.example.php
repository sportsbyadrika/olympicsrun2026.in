<?php
/**
 * Example configuration. Copy to config/config.local.php and fill in.
 * config/config.local.php is gitignored.
 */

return [
    'app' => [
        'name'     => 'Olympics Run 2026',
        'base_url' => 'https://olympicsrun2026.in',
        'env'      => 'production', // production | staging | local
        'debug'    => false,
        'timezone' => 'Asia/Kolkata',
    ],
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'olympicsrun2026',
        'user'    => 'olympicsrun2026',
        'pass'    => 'CHANGE_ME',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name'      => 'olyrun2026',
        'lifetime'  => 7200, // seconds
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    'mail' => [
        'from_name'  => 'Olympics Run 2026',
        'from_email' => 'no-reply@olympicsrun2026.in',
        'support'    => 'support@olympicsrun2026.in',
    ],
];
