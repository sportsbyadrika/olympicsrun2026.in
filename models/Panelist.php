<?php
final class Panelist
{
    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT p.*, a.name AS association_name
               FROM expert_panelists p
               JOIN associations a ON a.association_id = p.association_id
              ORDER BY p.full_name ASC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT * FROM expert_panelists WHERE panelist_id = ?',
            [$id]
        );
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO expert_panelists
                (association_id, username, email, password_hash, full_name,
                 phone, expertise, bio, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int)$d['association_id'],
                $d['username'],
                $d['email'],
                password_hash((string)$d['password'], PASSWORD_BCRYPT),
                $d['full_name'],
                $d['phone']     ?: null,
                $d['expertise'] ?: null,
                $d['bio']       ?: null,
                $d['status']    ?: 'active',
            ]
        );
    }

    public static function update(int $id, array $d, ?string $newPassword = null): void
    {
        if ($newPassword !== null && $newPassword !== '') {
            Database::execute(
                'UPDATE expert_panelists
                    SET association_id = ?, username = ?, email = ?,
                        full_name = ?, phone = ?, expertise = ?, bio = ?,
                        status = ?, password_hash = ?
                  WHERE panelist_id = ?',
                [
                    (int)$d['association_id'],
                    $d['username'],
                    $d['email'],
                    $d['full_name'],
                    $d['phone']     ?: null,
                    $d['expertise'] ?: null,
                    $d['bio']       ?: null,
                    $d['status']    ?: 'active',
                    password_hash($newPassword, PASSWORD_BCRYPT),
                    $id,
                ]
            );
        } else {
            Database::execute(
                'UPDATE expert_panelists
                    SET association_id = ?, username = ?, email = ?,
                        full_name = ?, phone = ?, expertise = ?, bio = ?,
                        status = ?
                  WHERE panelist_id = ?',
                [
                    (int)$d['association_id'],
                    $d['username'],
                    $d['email'],
                    $d['full_name'],
                    $d['phone']     ?: null,
                    $d['expertise'] ?: null,
                    $d['bio']       ?: null,
                    $d['status']    ?: 'active',
                    $id,
                ]
            );
        }
    }

    public static function delete(int $id): void
    {
        Database::execute(
            'DELETE FROM expert_panelists WHERE panelist_id = ?',
            [$id]
        );
    }

    public static function countAll(): int
    {
        return (int)Database::fetch('SELECT COUNT(*) AS c FROM expert_panelists')['c'];
    }
}
