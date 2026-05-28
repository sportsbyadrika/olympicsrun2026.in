<?php
final class Slot
{
    /** All slots with round + association names, plus current assignment counts. */
    public static function all(): array
    {
        return Database::fetchAll(
            'SELECT s.*, r.round_number, r.name AS round_name,
                    r.association_id, a.name AS association_name,
                    (SELECT COUNT(*) FROM slot_schools ss WHERE ss.slot_id = s.slot_id) AS assigned_count
               FROM slots s
               JOIN rounds r       ON r.round_id = s.round_id
               JOIN associations a ON a.association_id = r.association_id
              ORDER BY s.starts_at ASC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT s.*, r.round_number, r.name AS round_name,
                    r.association_id, r.slot_duration_minutes,
                    r.quiz_duration_minutes, r.questions_per_quiz,
                    a.name AS association_name
               FROM slots s
               JOIN rounds r       ON r.round_id = s.round_id
               JOIN associations a ON a.association_id = r.association_id
              WHERE s.slot_id = ?',
            [$id]
        );
    }

    public static function create(array $d): int
    {
        return Database::insert(
            'INSERT INTO slots
                (round_id, slot_label, starts_at, ends_at, capacity, status, created_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?, NULL)',
            [
                (int)$d['round_id'],
                $d['slot_label'] ?: null,
                self::dt($d['starts_at']),
                self::dt($d['ends_at']),
                (int)($d['capacity'] ?? 50),
                $d['status'] ?: 'scheduled',
            ]
        );
    }

    public static function update(int $id, array $d): void
    {
        Database::execute(
            'UPDATE slots
                SET round_id = ?, slot_label = ?, starts_at = ?, ends_at = ?,
                    capacity = ?, status = ?
              WHERE slot_id = ?',
            [
                (int)$d['round_id'],
                $d['slot_label'] ?: null,
                self::dt($d['starts_at']),
                self::dt($d['ends_at']),
                (int)($d['capacity'] ?? 50),
                $d['status'] ?: 'scheduled',
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute('DELETE FROM slots WHERE slot_id = ?', [$id]);
    }

    /**
     * Overlap rule: two slots in the same round overlap if
     * (existing.starts_at < new.ends_at) AND (existing.ends_at > new.starts_at).
     *
     * Returns the conflicting slot or null.
     */
    public static function findConflict(
        int $roundId,
        string $startsAt,
        string $endsAt,
        ?int $exceptId = null
    ): ?array {
        $sql = 'SELECT slot_id, slot_label, starts_at, ends_at
                  FROM slots
                 WHERE round_id = ?
                   AND starts_at < ?
                   AND ends_at   > ?';
        $params = [$roundId, self::dt($endsAt), self::dt($startsAt)];
        if ($exceptId !== null) {
            $sql .= ' AND slot_id != ?';
            $params[] = $exceptId;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params);
    }

    /** Schools currently assigned to a slot. */
    public static function assignedSchools(int $slotId): array
    {
        return Database::fetchAll(
            'SELECT ss.slot_school_id, ss.school_id, ss.attempt_status,
                    s.school_name, s.school_code,
                    sl.school_login_id, sl.username AS login_username
               FROM slot_schools ss
               JOIN schools s         ON s.school_id = ss.school_id
          LEFT JOIN school_logins sl  ON sl.school_login_id = ss.school_login_id
              WHERE ss.slot_id = ?
              ORDER BY s.school_name',
            [$slotId]
        );
    }

    /**
     * Schools eligible to be assigned to this slot: in the round's association,
     * approved, and NOT already assigned to any other slot of the same round
     * (one team per school per round).
     */
    public static function availableSchools(int $slotId, int $roundId, int $associationId): array
    {
        return Database::fetchAll(
            "SELECT s.school_id, s.school_name, s.school_code,
                    (SELECT sl.school_login_id
                       FROM school_logins sl
                      WHERE sl.school_id = s.school_id AND sl.status = 'active'
                      ORDER BY sl.school_login_id LIMIT 1) AS default_login_id
               FROM schools s
              WHERE s.association_id = ?
                AND s.status = 'approved'
                AND s.school_id NOT IN (
                    SELECT ss.school_id
                      FROM slot_schools ss
                      JOIN slots s2 ON s2.slot_id = ss.slot_id
                     WHERE s2.round_id = ?
                       AND ss.slot_id != ?
                )
              ORDER BY s.school_name",
            [$associationId, $roundId, $slotId]
        );
    }

    public static function assignSchool(int $slotId, int $schoolId, ?int $loginId, ?int $assignedBy): void
    {
        // Insert or ignore if already assigned (uniq_slot_school)
        Database::execute(
            'INSERT IGNORE INTO slot_schools
                (slot_id, school_id, school_login_id, attempt_status, assigned_by_user_id)
             VALUES (?, ?, ?, "assigned", ?)',
            [$slotId, $schoolId, $loginId, $assignedBy]
        );
    }

    public static function unassignSchool(int $slotId, int $schoolId): void
    {
        Database::execute(
            'DELETE FROM slot_schools WHERE slot_id = ? AND school_id = ?',
            [$slotId, $schoolId]
        );
    }

    private static function dt(string $v): string
    {
        $ts = strtotime($v);
        if ($ts === false) throw new InvalidArgumentException('Invalid datetime: ' . $v);
        return date('Y-m-d H:i:s', $ts);
    }
}
