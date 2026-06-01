<?php
final class School
{
    public static function all(?int $associationId = null, ?string $nameFilter = null): array
    {
        $sql = 'SELECT s.*, a.name AS association_name,
                       st.name AS school_type_name,
                       sy.name AS syllabus_name,
                       (SELECT COUNT(*) FROM school_logins sl
                         WHERE sl.school_id = s.school_id) AS team_count
                  FROM schools s
                  JOIN associations a ON a.association_id = s.association_id
             LEFT JOIN school_types st ON st.school_type_id = s.school_type_id
             LEFT JOIN syllabuses   sy ON sy.syllabus_id    = s.syllabus_id';
        $where  = [];
        $params = [];
        if ($associationId !== null) {
            $where[]  = 's.association_id = ?';
            $params[] = $associationId;
        }
        if ($nameFilter !== null && $nameFilter !== '') {
            $where[]  = 's.school_name LIKE ?';
            $params[] = '%' . $nameFilter . '%';
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY s.school_name ASC';
        return Database::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT s.*, a.name AS association_name,
                    st.name AS school_type_name,
                    sy.name AS syllabus_name
               FROM schools s
               JOIN associations a ON a.association_id = s.association_id
          LEFT JOIN school_types st ON st.school_type_id = s.school_type_id
          LEFT JOIN syllabuses   sy ON sy.syllabus_id    = s.syllabus_id
              WHERE s.school_id = ?',
            [$id]
        );
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO schools
                (association_id, school_name, school_code, school_type_id,
                 syllabus_id, region, address, principal_name, coach_name,
                 contact_email, contact_phone, status, approved_by_user_id, approved_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL)',
            [
                (int)$d['association_id'],
                $d['school_name'],
                $d['school_code']    ?: null,
                self::intOrNull($d['school_type_id'] ?? null),
                self::intOrNull($d['syllabus_id'] ?? null),
                $d['region']         ?: null,
                $d['address']        ?: null,
                $d['principal_name'] ?: null,
                $d['coach_name']     ?: null,
                $d['contact_email']  ?: null,
                $d['contact_phone']  ?: null,
                $d['status']         ?: 'pending',
            ]
        );
    }

    public static function update(int $id, array $d): void
    {
        Database::execute(
            'UPDATE schools
                SET association_id = ?, school_name = ?, school_code = ?,
                    school_type_id = ?, syllabus_id = ?,
                    region = ?, address = ?, principal_name = ?, coach_name = ?,
                    contact_email = ?, contact_phone = ?, status = ?
              WHERE school_id = ?',
            [
                (int)$d['association_id'],
                $d['school_name'],
                $d['school_code']    ?: null,
                self::intOrNull($d['school_type_id'] ?? null),
                self::intOrNull($d['syllabus_id'] ?? null),
                $d['region']         ?: null,
                $d['address']        ?: null,
                $d['principal_name'] ?: null,
                $d['coach_name']     ?: null,
                $d['contact_email']  ?: null,
                $d['contact_phone']  ?: null,
                $d['status']         ?: 'pending',
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM schools WHERE school_id = ?', [$id]);
    }

    public static function countByStatus(): array
    {
        $rows = Database::fetchAll(
            'SELECT status, COUNT(*) AS c FROM schools GROUP BY status'
        );
        $out = ['total' => 0];
        foreach ($rows as $r) {
            $out[$r['status']] = (int)$r['c'];
            $out['total'] += (int)$r['c'];
        }
        return $out;
    }

    private static function intOrNull(mixed $v): ?int
    {
        return ($v === null || $v === '') ? null : (int)$v;
    }
}
