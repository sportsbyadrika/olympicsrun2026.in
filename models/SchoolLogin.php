<?php
final class SchoolLogin
{
    public static function all(?int $schoolId = null): array
    {
        $sql = 'SELECT sl.*, s.school_name, s.school_code,
                       a.name AS association_name
                  FROM school_logins sl
                  JOIN schools s ON s.school_id = sl.school_id
                  JOIN associations a ON a.association_id = s.association_id';
        $params = [];
        if ($schoolId !== null) {
            $sql .= ' WHERE sl.school_id = ?';
            $params[] = $schoolId;
        }
        $sql .= ' ORDER BY s.school_name ASC, sl.username ASC';
        return Database::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT sl.*, s.school_name, s.association_id
               FROM school_logins sl
               JOIN schools s ON s.school_id = sl.school_id
              WHERE sl.school_login_id = ?',
            [$id]
        );
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO school_logins
                (school_id, username, password_hash, team_label, status)
             VALUES (?, ?, ?, ?, ?)',
            [
                (int)$d['school_id'],
                $d['username'],
                password_hash((string)$d['password'], PASSWORD_BCRYPT),
                $d['team_label'] ?: null,
                $d['status']     ?: 'active',
            ]
        );
    }

    public static function update(int $id, array $d, ?string $newPassword = null): void
    {
        if ($newPassword !== null && $newPassword !== '') {
            Database::execute(
                'UPDATE school_logins
                    SET school_id = ?, username = ?, team_label = ?,
                        status = ?, password_hash = ?
                  WHERE school_login_id = ?',
                [
                    (int)$d['school_id'],
                    $d['username'],
                    $d['team_label'] ?: null,
                    $d['status']     ?: 'active',
                    password_hash($newPassword, PASSWORD_BCRYPT),
                    $id,
                ]
            );
        } else {
            Database::execute(
                'UPDATE school_logins
                    SET school_id = ?, username = ?, team_label = ?, status = ?
                  WHERE school_login_id = ?',
                [
                    (int)$d['school_id'],
                    $d['username'],
                    $d['team_label'] ?: null,
                    $d['status']     ?: 'active',
                    $id,
                ]
            );
        }
    }

    public static function setPassword(int $id, string $plain): void
    {
        Database::execute(
            'UPDATE school_logins SET password_hash = ? WHERE school_login_id = ?',
            [password_hash($plain, PASSWORD_BCRYPT), $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM school_logins WHERE school_login_id = ?', [$id]);
    }

    /** Suggest a username from a school code. */
    public static function suggestUsername(string $schoolCode): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '', $schoolCode));
        if ($base === '') $base = 'school';
        $base = substr($base, 0, 20) . '-team';
        $candidate = $base;
        $n = 1;
        while (Database::fetch(
            'SELECT school_login_id FROM school_logins WHERE username = ? LIMIT 1',
            [$candidate]
        )) {
            $n++;
            $candidate = $base . $n;
        }
        return $candidate;
    }

    /** Generate a short, human-readable random password. */
    public static function generatePassword(int $length = 10): string
    {
        $alphabet = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }

    public static function countAll(): int
    {
        return (int)Database::fetch('SELECT COUNT(*) AS c FROM school_logins')['c'];
    }

    public static function countForSchool(int $schoolId): int
    {
        return (int)Database::fetch(
            'SELECT COUNT(*) AS c FROM school_logins WHERE school_id = ?',
            [$schoolId]
        )['c'];
    }
}
