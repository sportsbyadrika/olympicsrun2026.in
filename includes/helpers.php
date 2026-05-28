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

/* ------------------- Flash + Old + Errors --------------------------------*/

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

function flash_old(array $data): void
{
    // Strip out sensitive fields automatically.
    foreach (['password', 'password_confirmation', '_csrf'] as $k) {
        unset($data[$k]);
    }
    $_SESSION['_old'] = $data;
}

function pull_old(): array
{
    $o = $_SESSION['_old'] ?? [];
    unset($_SESSION['_old']);
    return $o;
}

function flash_errors(array $errors): void
{
    $_SESSION['_errors'] = $errors;
}

function pull_errors(): array
{
    $e = $_SESSION['_errors'] ?? [];
    unset($_SESSION['_errors']);
    return $e;
}

/** Form field helper: returns old value if present, otherwise the model value. */
function field(array $old, ?array $model, string $key, string $default = ''): string
{
    if (array_key_exists($key, $old))            return (string)$old[$key];
    if ($model !== null && isset($model[$key]))  return (string)$model[$key];
    return $default;
}

/** Render an inline error message for a field, or empty string. */
function err(array $errors, string $key): string
{
    return isset($errors[$key])
        ? '<div class="invalid-feedback d-block">' . e($errors[$key]) . '</div>'
        : '';
}

/** Returns the "is-invalid" class if a field has a validation error. */
function invalid_class(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' is-invalid' : '';
}

/* ------------------- Views ------------------------------------------------*/

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

/* ------------------- UI helpers ------------------------------------------*/

/** Status badge CSS class from a status string. */
function status_badge(?string $status): string
{
    $map = [
        'active'         => 'bg-success-subtle text-success-emphasis border border-success-subtle',
        'approved'       => 'bg-success-subtle text-success-emphasis border border-success-subtle',
        'open'           => 'bg-success-subtle text-success-emphasis border border-success-subtle',
        'published'      => 'bg-success-subtle text-success-emphasis border border-success-subtle',
        'pending'        => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'needs_revision' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'scheduled'      => 'bg-info-subtle text-info-emphasis border border-info-subtle',
        'draft'          => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        'closed'         => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        'inactive'       => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        'suspended'      => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
        'rejected'       => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
        'cancelled'      => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
        'disqualified'   => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
    ];
    $key = strtolower((string)$status);
    return 'badge ' . ($map[$key] ?? 'bg-light text-dark border');
}

function status_label(?string $status): string
{
    return ucwords(str_replace('_', ' ', (string)$status));
}

/* ------------------- Navigation ------------------------------------------*/

/**
 * Menu items per role. Items may have either 'url' (flat link) or
 * 'children' (dropdown).
 */
function nav_for_role(string $role): array
{
    return match ($role) {
        Auth::ROLE_ADMIN => [
            ['label' => 'Dashboard', 'url' => '/admin/dashboard'],
            ['label' => 'Masters', 'children' => [
                ['label' => 'Associations', 'url' => '/admin/associations'],
                ['label' => 'Schools',      'url' => '/admin/schools'],
            ]],
            ['label' => 'Users', 'children' => [
                ['label' => 'Association Users', 'url' => '/admin/association-users'],
                ['label' => 'Expert Panelists',  'url' => '/admin/panelists'],
                ['label' => 'School Logins',     'url' => '/admin/school-logins'],
            ]],
            ['label' => 'Quiz', 'children' => [
                ['label' => 'Rounds', 'url' => '/admin/rounds'],
                ['label' => 'Slots',  'url' => '/admin/slots'],
            ]],
            ['label' => 'Questions', 'url' => '/admin/questions'],
            ['label' => 'Email Templates', 'url' => '/admin/email-templates'],
            ['label' => 'Settings',  'url' => '/admin/settings'],
            ['label' => 'Reports',   'url' => '/admin/reports'],
        ],
        Auth::ROLE_ASSOCIATION => [
            ['label' => 'Dashboard', 'url' => '/association/dashboard'],
            ['label' => 'Questions', 'url' => '/association/questions'],
            ['label' => 'Schools',   'url' => '/association/schools'],
            ['label' => 'Slots',     'url' => '/association/slots'],
            ['label' => 'Results',   'url' => '/association/results'],
            ['label' => 'Reports',   'url' => '/association/reports'],
        ],
        Auth::ROLE_PANELIST => [
            ['label' => 'Dashboard',    'url' => '/panelist/dashboard'],
            ['label' => 'Review Queue', 'url' => '/panelist/review'],
            ['label' => 'Master Bank',  'url' => '/panelist/master'],
            ['label' => 'Slot Builder', 'url' => '/panelist/slots'],
            ['label' => 'Results',      'url' => '/panelist/results'],
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

/** Format a MySQL DATETIME for an HTML datetime-local input. */
function dt_for_input(?string $mysqlDatetime): string
{
    if (!$mysqlDatetime) return '';
    $ts = strtotime($mysqlDatetime);
    return $ts === false ? '' : date('Y-m-d\TH:i', $ts);
}

/** Format a MySQL DATETIME for display. */
function dt_display(?string $mysqlDatetime, string $format = 'd M Y, h:i A'): string
{
    if (!$mysqlDatetime) return '—';
    $ts = strtotime($mysqlDatetime);
    return $ts === false ? '—' : date($format, $ts);
}
