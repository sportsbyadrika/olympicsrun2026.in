<?php
final class Association
{
    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT * FROM associations ORDER BY name ASC'
        );
    }

    public static function active(): array
    {
        return Database::fetchAll(
            "SELECT * FROM associations WHERE status = 'active' ORDER BY name ASC"
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT * FROM associations WHERE association_id = ?',
            [$id]
        );
    }

    public static function create(array $d, ?int $adminId): int
    {
        return Database::insert(
            'INSERT INTO associations
                (name, short_code, region, contact_email, contact_phone,
                 address, status, created_by_admin_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $d['name'],
                $d['short_code'],
                $d['region']        ?: null,
                $d['contact_email'] ?: null,
                $d['contact_phone'] ?: null,
                $d['address']       ?: null,
                $d['status']        ?: 'active',
                $adminId,
            ]
        );
    }

    public static function update(int $id, array $d): void
    {
        Database::execute(
            'UPDATE associations
                SET name = ?, short_code = ?, region = ?, contact_email = ?,
                    contact_phone = ?, address = ?, status = ?
              WHERE association_id = ?',
            [
                $d['name'],
                $d['short_code'],
                $d['region']        ?: null,
                $d['contact_email'] ?: null,
                $d['contact_phone'] ?: null,
                $d['address']       ?: null,
                $d['status']        ?: 'active',
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM associations WHERE association_id = ?', [$id]);
    }

    public static function countAll(): int
    {
        return (int)Database::fetch('SELECT COUNT(*) AS c FROM associations')['c'];
    }
}
