<?php
final class AssociationUser
{
    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT au.*, a.name AS association_name
               FROM association_users au
               JOIN associations a ON a.association_id = au.association_id
              ORDER BY au.full_name ASC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT * FROM association_users WHERE association_user_id = ?',
            [$id]
        );
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO association_users
                (association_id, username, email, password_hash, full_name,
                 phone, role_label, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int)$d['association_id'],
                $d['username'],
                $d['email'],
                password_hash((string)$d['password'], PASSWORD_BCRYPT),
                $d['full_name'],
                $d['phone']      ?: null,
                $d['role_label'] ?: 'operator',
                $d['status']     ?: 'active',
            ]
        );
    }

    public static function update(int $id, array $d, ?string $newPassword = null): void
    {
        if ($newPassword !== null && $newPassword !== '') {
            Database::execute(
                'UPDATE association_users
                    SET association_id = ?, username = ?, email = ?,
                        full_name = ?, phone = ?, role_label = ?, status = ?,
                        password_hash = ?
                  WHERE association_user_id = ?',
                [
                    (int)$d['association_id'],
                    $d['username'],
                    $d['email'],
                    $d['full_name'],
                    $d['phone']      ?: null,
                    $d['role_label'] ?: 'operator',
                    $d['status']     ?: 'active',
                    password_hash($newPassword, PASSWORD_BCRYPT),
                    $id,
                ]
            );
        } else {
            Database::execute(
                'UPDATE association_users
                    SET association_id = ?, username = ?, email = ?,
                        full_name = ?, phone = ?, role_label = ?, status = ?
                  WHERE association_user_id = ?',
                [
                    (int)$d['association_id'],
                    $d['username'],
                    $d['email'],
                    $d['full_name'],
                    $d['phone']      ?: null,
                    $d['role_label'] ?: 'operator',
                    $d['status']     ?: 'active',
                    $id,
                ]
            );
        }
    }

    public static function delete(int $id): void
    {
        Database::execute(
            'DELETE FROM association_users WHERE association_user_id = ?',
            [$id]
        );
    }

    public static function countAll(): int
    {
        return (int)Database::fetch('SELECT COUNT(*) AS c FROM association_users')['c'];
    }
}
