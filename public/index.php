<?php
/**
 * Front controller. .htaccess rewrites every non-file request here.
 * Tiny route table maps "METHOD /path" -> controller method.
 */

require __DIR__ . '/../includes/bootstrap.php';

$path   = strtok($_SERVER['REQUEST_URI'] ?? '/', '?') ?: '/';
$path   = rtrim($path, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$key    = $method . ' ' . $path;

$routes = [
    'GET /'                       => fn() => Auth::check()
                                          ? redirect(Auth::dashboardUrl(Auth::role()))
                                          : redirect('/login'),

    'GET /login'                  => [AuthController::class, 'showLogin'],
    'POST /login'                 => [AuthController::class, 'doLogin'],
    'GET /logout'                 => [AuthController::class, 'logout'],

    'GET /admin/dashboard'        => [AdminController::class, 'dashboard'],
    'GET /association/dashboard'  => [AssociationController::class, 'dashboard'],
    'GET /panelist/dashboard'     => [PanelistController::class, 'dashboard'],
    'GET /school/dashboard'       => [SchoolController::class, 'dashboard'],
];

if (!isset($routes[$key])) {
    http_response_code(404);
    render('errors/404', ['title' => 'Not found — Olympics Run 2026']);
    exit;
}

$handler = $routes[$key];
if (is_array($handler)) {
    [$class, $method] = $handler;
    (new $class)->$method();
} else {
    $handler();
}
