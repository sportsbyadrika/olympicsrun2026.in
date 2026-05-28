<?php
final class Round
{
    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT r.*, a.name AS association_name
               FROM rounds r
               JOIN associations a ON a.association_id = r.association_id
              ORDER BY a.name, r.round_number'
        );
    }

    public static function forAssociation(int $associationId): array
    {
        return Database::fetchAll(
            'SELECT * FROM rounds WHERE association_id = ? ORDER BY round_number',
            [$associationId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT r.*, a.name AS association_name
               FROM rounds r
               JOIN associations a ON a.association_id = r.association_id
              WHERE r.round_id = ?',
            [$id]
        );
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO rounds
                (association_id, round_number, name, description,
                 slot_duration_minutes, quiz_duration_minutes, questions_per_quiz,
                 marks_correct, marks_wrong, marks_unanswered,
                 qualifiers_count, status, starts_at, ends_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                (int)$d['association_id'],
                (int)$d['round_number'],
                $d['name'],
                $d['description']           ?: null,
                (int)($d['slot_duration_minutes'] ?: 30),
                (int)($d['quiz_duration_minutes'] ?: 15),
                (int)($d['questions_per_quiz']    ?: 30),
                (float)($d['marks_correct']    ?? 1),
                (float)($d['marks_wrong']      ?? 0),
                (float)($d['marks_unanswered'] ?? 0),
                $d['qualifiers_count'] !== '' ? (int)$d['qualifiers_count'] : null,
                $d['status'] ?: 'draft',
                self::dtOrNull($d['starts_at'] ?? null),
                self::dtOrNull($d['ends_at']   ?? null),
            ]
        );
    }

    public static function update(int $id, array $d): void
    {
        Database::execute(
            'UPDATE rounds
                SET association_id = ?, round_number = ?, name = ?, description = ?,
                    slot_duration_minutes = ?, quiz_duration_minutes = ?,
                    questions_per_quiz = ?, marks_correct = ?, marks_wrong = ?,
                    marks_unanswered = ?, qualifiers_count = ?, status = ?,
                    starts_at = ?, ends_at = ?
              WHERE round_id = ?',
            [
                (int)$d['association_id'],
                (int)$d['round_number'],
                $d['name'],
                $d['description']           ?: null,
                (int)($d['slot_duration_minutes'] ?: 30),
                (int)($d['quiz_duration_minutes'] ?: 15),
                (int)($d['questions_per_quiz']    ?: 30),
                (float)($d['marks_correct']    ?? 1),
                (float)($d['marks_wrong']      ?? 0),
                (float)($d['marks_unanswered'] ?? 0),
                $d['qualifiers_count'] !== '' ? (int)$d['qualifiers_count'] : null,
                $d['status'] ?: 'draft',
                self::dtOrNull($d['starts_at'] ?? null),
                self::dtOrNull($d['ends_at']   ?? null),
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM rounds WHERE round_id = ?', [$id]);
    }

    /** Reject duplicate (association_id, round_number) combinations. */
    public static function roundNumberTaken(int $associationId, int $roundNumber, ?int $exceptId = null): bool
    {
        $sql = 'SELECT round_id FROM rounds WHERE association_id = ? AND round_number = ?';
        $params = [$associationId, $roundNumber];
        if ($exceptId !== null) { $sql .= ' AND round_id != ?'; $params[] = $exceptId; }
        return (bool)Database::fetch($sql . ' LIMIT 1', $params);
    }

    private static function dtOrNull(?string $v): ?string
    {
        if ($v === null || $v === '') return null;
        $ts = strtotime($v);
        return $ts === false ? null : date('Y-m-d H:i:s', $ts);
    }
}
