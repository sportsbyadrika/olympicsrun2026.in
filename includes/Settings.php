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

    /**
     * Every settings row with its metadata (type, description), in a stable
     * order. Used by the admin Settings editor.
     * @return array<int,array<string,mixed>>
     */
    public static function allWithMeta(): array
    {
        return Database::fetchAll(
            'SELECT setting_key, setting_value, value_type, description, updated_at
               FROM settings
              ORDER BY setting_key'
        );
    }

    /**
     * Persist a batch of key => value pairs. Only keys that already exist in
     * the table are written (whitelist by existing rows). Returns the number
     * of rows updated.
     */
    public static function setMany(array $values, ?int $byAdminId = null): int
    {
        $existing = [];
        foreach (self::allWithMeta() as $row) {
            $existing[$row['setting_key']] = $row['value_type'];
        }

        $count = 0;
        foreach ($values as $key => $value) {
            if (!array_key_exists($key, $existing)) continue;

            // Normalise per declared type so stored values stay consistent.
            $clean = match ($existing[$key]) {
                'bool'     => self::truthy($value) ? 'true' : 'false',
                'int'      => (string)(int)$value,
                'float'    => (string)(float)$value,
                'datetime' => self::normaliseDatetime((string)$value),
                default    => trim((string)$value),
            };

            self::set($key, $clean, $byAdminId);
            $count++;
        }
        return $count;
    }

    private static function truthy(mixed $v): bool
    {
        return in_array(strtolower((string)$v), ['1', 'true', 'yes', 'on'], true);
    }

    private static function normaliseDatetime(string $v): string
    {
        $v = trim($v);
        if ($v === '') return '';
        $ts = strtotime($v);
        return $ts === false ? $v : date('Y-m-d H:i:s', $ts);
    }
}
