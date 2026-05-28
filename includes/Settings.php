<?php
/**
 * Tiny typed reader for the `settings` key/value table. Cached per request.
 * Writes go through set() which also bumps updated_at and updated_by_admin_id.
 */
final class Settings
{
    /** @var array<string,string|null>|null */
    private static ?array $cache = null;

    private static function loadAll(): array
    {
        if (self::$cache !== null) return self::$cache;
        $rows = Database::fetchAll('SELECT setting_key, setting_value FROM settings');
        $out = [];
        foreach ($rows as $r) $out[$r['setting_key']] = $r['setting_value'];
        return self::$cache = $out;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $all = self::loadAll();
        return $all[$key] ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::get($key);
        return $v === null || $v === '' ? $default : (int)$v;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null) return $default;
        return in_array(strtolower($v), ['1', 'true', 'yes', 'on'], true);
    }

    public static function set(string $key, string $value, ?int $byAdminId = null): void
    {
        Database::execute(
            'UPDATE settings SET setting_value = ?, updated_by_admin_id = ?
              WHERE setting_key = ?',
            [$value, $byAdminId, $key]
        );
        if (self::$cache !== null) self::$cache[$key] = $value;
    }
}
