<?php
/**
 * Session-based authentication with role scoping.
 *
 * Four roles, each backed by its own table:
 *   - admin       -> admins
 *   - association -> association_users
 *   - panelist    -> expert_panelists
 *   - school      -> school_logins (joined to schools)
 */
final class Auth
{
    public const ROLE_ADMIN       = 'admin';
    public const ROLE_ASSOCIATION = 'association';
    public const ROLE_PANELIST    = 'panelist';
    public const ROLE_SCHOOL      = 'school';

    /** @return array<string,string> role => human label */
    public static function roles(): array
    {
        return [
            self::ROLE_ADMIN       => 'Admin',
            self::ROLE_ASSOCIATION => 'Association User',
            self::ROLE_PANELIST    => 'Expert Panelist',
            self::ROLE_SCHOOL      => 'School Team',
        ];
    }

    public static function attempt(string $role, string $username, string $password): bool
    {
        $row = match ($role) {
            self::ROLE_ADMIN => Database::fetch(
                'SELECT admin_id AS id, username, full_name AS name,
                        password_hash, status
                 FROM admins WHERE username = ? LIMIT 1',
                [$username]
            ),
            self::ROLE_ASSOCIATION => Database::fetch(
                'SELECT association_user_id AS id, association_id, username,
                        full_name AS name, password_hash, status
                 FROM association_users WHERE username = ? LIMIT 1',
                [$username]
            ),
            self::ROLE_PANELIST => Database::fetch(
                'SELECT panelist_id AS id, association_id, username,
                        full_name AS name, password_hash, status
                 FROM expert_panelists WHERE username = ? LIMIT 1',
                [$username]
            ),
            self::ROLE_SCHOOL => Database::fetch(
                'SELECT sl.school_login_id AS id, sl.school_id,
                        s.association_id, sl.username,
                        COALESCE(sl.team_label, s.school_name) AS name,
                        sl.password_hash, sl.status
                 FROM school_logins sl
                 JOIN schools s ON s.school_id = sl.school_id
                 WHERE sl.username = ? LIMIT 1',
                [$username]
            ),
            default => null,
        };

        if (!$row) {
            return false;
        }
        if (($row['status'] ?? 'suspended') !== 'active') {
            return false;
        }
        if (!password_verify($password, (string)$row['password_hash'])) {
            return false;
        }

        self::touchLastLogin($role, (int)$row['id']);

        // Build session payload — strip the hash before storing.
        unset($row['password_hash'], $row['status']);
        $payload = ['role' => $role] + $row + ['login_at' => time()];
        $payload['id'] = (int)$payload['id'];

        // Mitigate session fixation
        session_regenerate_id(true);
        $_SESSION['auth'] = $payload;
        return true;
    }

    private static function touchLastLogin(string $role, int $id): void
    {
        [$table, $idCol] = match ($role) {
            self::ROLE_ADMIN       => ['admins',            'admin_id'],
            self::ROLE_ASSOCIATION => ['association_users', 'association_user_id'],
            self::ROLE_PANELIST    => ['expert_panelists',  'panelist_id'],
            self::ROLE_SCHOOL      => ['school_logins',     'school_login_id'],
        };
        Database::execute(
            "UPDATE {$table} SET last_login_at = NOW() WHERE {$idCol} = ?",
            [$id]
        );
    }

    public static function check(): bool
    {
        return !empty($_SESSION['auth']);
    }

    public static function user(): ?array
    {
        return $_SESSION['auth'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['auth']['role'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['auth']['id']) ? (int)$_SESSION['auth']['id'] : null;
    }

    public static function associationId(): ?int
    {
        return isset($_SESSION['auth']['association_id'])
            ? (int)$_SESSION['auth']['association_id']
            : null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            $_SESSION['flash']['error'] = 'Please sign in to continue.';
            redirect('/login');
        }
    }

    public static function requireRole(string ...$roles): void
    {
        self::requireLogin();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            render('errors/403', ['title' => 'Forbidden']);
            exit;
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $p['path'],
                $p['domain'],
                $p['secure'],
                $p['httponly']
            );
        }
        session_destroy();
    }

    public static function dashboardUrl(string $role): string
    {
        return match ($role) {
            self::ROLE_ADMIN       => '/admin/dashboard',
            self::ROLE_ASSOCIATION => '/association/dashboard',
            self::ROLE_PANELIST    => '/panelist/dashboard',
            self::ROLE_SCHOOL      => '/school/dashboard',
            default                => '/login',
        };
    }
}
