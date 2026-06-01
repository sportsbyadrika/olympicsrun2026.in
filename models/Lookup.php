<?php
/**
 * Read-only access to the small admin-managed value lists used by dropdowns
 * (school_types, syllabuses). Cached per request.
 */
final class Lookup
{
    /** @var array<string,array<int,array<string,mixed>>> */
    private static array $cache = [];

    /** @return array<int,array<string,mixed>> */
    public static function schoolTypes(): array
    {
        return self::load('school_types', 'school_type_id');
    }

    /** @return array<int,array<string,mixed>> */
    public static function syllabuses(): array
    {
        return self::load('syllabuses', 'syllabus_id');
    }

    private static function load(string $table, string $idCol): array
    {
        if (isset(self::$cache[$table])) {
            return self::$cache[$table];
        }
        try {
            $rows = Database::fetchAll(
                "SELECT {$idCol} AS id, name FROM {$table} ORDER BY sort_order, name"
            );
        } catch (Throwable $e) {
            $rows = []; // table not migrated yet — degrade gracefully.
        }
        return self::$cache[$table] = $rows;
    }
}
