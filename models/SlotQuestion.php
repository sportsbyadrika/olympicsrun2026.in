<?php
final class SlotQuestion
{
    /** Master questions NOT yet in this slot (bank side). */
    public static function bankForSlot(int $slotId, int $associationId, array $filters): array
    {
        $sql = "SELECT mq.master_question_id, mq.question_text, mq.option_a, mq.option_b,
                       mq.option_c, mq.option_d, mq.correct_option, mq.difficulty,
                       mq.sport, mq.category, mq.intended_round
                  FROM master_questions mq
                 WHERE mq.association_id = ?
                   AND mq.status = 'active'
                   AND mq.master_question_id NOT IN (
                       SELECT sq.master_question_id FROM slot_questions sq
                        WHERE sq.slot_id = ?
                   )";
        $params = [$associationId, $slotId];
        if (!empty($filters['difficulty'])) {
            $sql .= ' AND mq.difficulty = ?';
            $params[] = $filters['difficulty'];
        }
        if (!empty($filters['sport'])) {
            $sql .= ' AND mq.sport = ?';
            $params[] = $filters['sport'];
        }
        if (!empty($filters['round'])) {
            $sql .= ' AND (mq.intended_round IS NULL OR mq.intended_round = ?)';
            $params[] = (int)$filters['round'];
        }
        $sql .= ' ORDER BY mq.master_question_id DESC';
        return Database::fetchAll($sql, $params);
    }

    /** Questions currently in this slot, ordered. */
    public static function forSlot(int $slotId): array
    {
        return Database::fetchAll(
            'SELECT mq.master_question_id, mq.question_text, mq.option_a, mq.option_b,
                    mq.option_c, mq.option_d, mq.correct_option, mq.difficulty,
                    mq.sport, sq.position, sq.slot_question_id
               FROM slot_questions sq
               JOIN master_questions mq ON mq.master_question_id = sq.master_question_id
              WHERE sq.slot_id = ?
              ORDER BY sq.position ASC',
            [$slotId]
        );
    }

    public static function countForSlot(int $slotId): int
    {
        return (int)Database::fetch(
            'SELECT COUNT(*) AS c FROM slot_questions WHERE slot_id = ?',
            [$slotId]
        )['c'];
    }

    /** Append a master question to the end of a slot. Returns true if inserted. */
    public static function assign(int $slotId, int $masterQuestionId): bool
    {
        $pos = (int)Database::fetch(
            'SELECT COALESCE(MAX(position), 0) + 1 AS p
               FROM slot_questions WHERE slot_id = ?',
            [$slotId]
        )['p'];

        try {
            Database::execute(
                'INSERT INTO slot_questions
                    (slot_id, master_question_id, position, added_by_admin_id)
                 VALUES (?, ?, ?, NULL)',
                [$slotId, $masterQuestionId, $pos]
            );
            return true;
        } catch (PDOException $e) {
            // 1062 = duplicate (already assigned). Treat as no-op.
            if (isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062) return false;
            throw $e;
        }
    }

    public static function unassign(int $slotId, int $masterQuestionId): bool
    {
        return Database::execute(
            'DELETE FROM slot_questions
              WHERE slot_id = ? AND master_question_id = ?',
            [$slotId, $masterQuestionId]
        ) > 0;
    }

    /**
     * Replace every position for this slot's questions with the order given.
     * Wrapped in a transaction; positions are bumped out of the way first to
     * avoid violating the uniq_slot_position (slot_id, position) constraint.
     */
    public static function reorder(int $slotId, array $orderedMasterIds): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            Database::execute(
                'UPDATE slot_questions
                    SET position = position + 100000
                  WHERE slot_id = ?',
                [$slotId]
            );

            $pos = 1;
            foreach ($orderedMasterIds as $rawId) {
                $qid = (int)$rawId;
                if ($qid <= 0) continue;
                Database::execute(
                    'UPDATE slot_questions
                        SET position = ?
                      WHERE slot_id = ? AND master_question_id = ?',
                    [$pos, $slotId, $qid]
                );
                $pos++;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Slots for the panelist's association, with question counts vs target. */
    public static function slotsForAssociation(int $associationId): array
    {
        return Database::fetchAll(
            'SELECT s.*, r.round_number, r.name AS round_name,
                    r.questions_per_quiz,
                    (SELECT COUNT(*) FROM slot_questions sq
                      WHERE sq.slot_id = s.slot_id) AS question_count
               FROM slots s
               JOIN rounds r ON r.round_id = s.round_id
              WHERE r.association_id = ?
              ORDER BY r.round_number, s.starts_at',
            [$associationId]
        );
    }
}
