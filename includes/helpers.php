<?php
/**
 * Tiny view + URL helpers used across templates and controllers.
 */

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function csrf_field(): string
{
    $token = Csrf::token();
    return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
}

function old(string $key, string $default = ''): string
{
    $val = $_SESSION['_old'][$key] ?? $default;
    return is_string($val) ? $val : $default;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function flash_pull(string $type): ?string
{
    $msg = $_SESSION['flash'][$type] ?? null;
    if ($msg !== null) {
        unset($_SESSION['flash'][$type]);
    }
    return $msg;
}

function view(string $template, array $data = []): string
{
    $__file = __DIR__ . '/../views/' . $template . '.php';
    if (!is_file($__file)) {
        throw new RuntimeException("View not found: {$template}");
    }
    extract($data, EXTR_SKIP);
    ob_start();
    include $__file;
    return ob_get_clean();
}

/**
 * Render a view inside the base layout.
 * Pass ['layout' => false] in $data to render bare (e.g. printable reports).
 */
function render(string $template, array $data = []): void
{
    $useLayout = $data['layout'] ?? true;
    $title     = $data['title'] ?? 'Olympics Run 2026';
    $content   = view($template, $data);

    if (!$useLayout) {
        echo $content;
        return;
    }

    extract($data, EXTR_SKIP);
    include __DIR__ . '/../views/layouts/app.php';
}

/** Menu items per role for the navbar. */
function nav_for_role(string $role): array
{
    return match ($role) {
        Auth::ROLE_ADMIN => [
            ['label' => 'Dashboard',     'url' => '/admin/dashboard'],
            ['label' => 'Users',         'url' => '/admin/users'],
            ['label' => 'Questions',     'url' => '/admin/questions'],
            ['label' => 'Question Sets', 'url' => '/admin/sets'],
            ['label' => 'Slots',         'url' => '/admin/slots'],
            ['label' => 'Settings',      'url' => '/admin/settings'],
            ['label' => 'Reports',       'url' => '/admin/reports'],
        ],
        Auth::ROLE_ASSOCIATION => [
            ['label' => 'Dashboard', 'url' => '/association/dashboard'],
            ['label' => 'Schools',   'url' => '/association/schools'],
            ['label' => 'Slots',     'url' => '/association/slots'],
            ['label' => 'Results',   'url' => '/association/results'],
            ['label' => 'Reports',   'url' => '/association/reports'],
        ],
        Auth::ROLE_PANELIST => [
            ['label' => 'Dashboard',    'url' => '/panelist/dashboard'],
            ['label' => 'My Questions', 'url' => '/panelist/questions'],
            ['label' => 'New Question', 'url' => '/panelist/questions/new'],
        ],
        Auth::ROLE_SCHOOL => [
            ['label' => 'Dashboard', 'url' => '/school/dashboard'],
            ['label' => 'Profile',   'url' => '/school/profile'],
            ['label' => 'My Slot',   'url' => '/school/slot'],
            ['label' => 'Results',   'url' => '/school/result'],
        ],
        default => [],
    };
}
