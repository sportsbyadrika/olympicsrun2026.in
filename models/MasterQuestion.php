<?php
final class MasterQuestion
{
    public const DIFFICULTIES = ['easy', 'medium', 'hard'];
    public const STATUSES     = ['active', 'retired'];

    public static function all(?int $associationId = null, array $filters = []): array
    {
        $sql = 'SELECT mq.*, a.name AS association_name
                  FROM master_questions mq
                  JOIN associations a ON a.association_id = mq.association_id';
        $where = [];
        $params = [];
        if ($associationId !== null) {
            $where[]  = 'mq.association_id = ?';
            $params[] = $associationId;
        }
        if (!empty($filters['difficulty'])) {
            $where[]  = 'mq.difficulty = ?';
            $params[] = $filters['difficulty'];
        }
        if (!empty($filters['status'])) {
            $where[]  = 'mq.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['association_id'])) {
            $where[]  = 'mq.association_id = ?';
            $params[] = (int)$filters['association_id'];
        }
        if (!empty($filters['round'])) {
            $where[]  = 'mq.intended_round = ?';
            $params[] = (int)$filters['round'];
        }
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY mq.master_question_id DESC';
        return Database::fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::fetch(
            'SELECT mq.*, a.name AS association_name
               FROM master_questions mq
               JOIN associations a ON a.association_id = mq.association_id
              WHERE mq.master_question_id = ?',
            [$id]
        );
    }

    public static function findScoped(int $id, int $associationId): ?array
    {
        return Database::fetch(
            'SELECT * FROM master_questions
              WHERE master_question_id = ? AND association_id = ?',
            [$id, $associationId]
        );
    }

    public static function createDirect(int $associationId, int $panelistId, array $d): int
    {
        return Database::insert(
            'INSERT INTO master_questions
                (source_question_id, association_id, question_text,
                 option_a, option_b, option_c, option_d, correct_option,
                 explanation, sport, category, difficulty, intended_round,
                 status, added_by_panelist_id)
             VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "active", ?)',
            [
                $associationId,
                $d['question_text'],
                $d['option_a'], $d['option_b'], $d['option_c'], $d['option_d'],
                $d['correct_option'],
                $d['explanation']      ?: null,
                $d['sport']            ?: null,
                $d['category']         ?: null,
                $d['difficulty']       ?: 'medium',
                $d['intended_round'] !== '' ? (int)$d['intended_round'] : null,
                $panelistId,
            ]
        );
    }

    /**
     * Migrate an approved bank row into master_questions in a single
     * transaction; updates the bank row to point back at the new master id.
     * Returns the new master_question_id.
     */
    public static function migrateFromBank(
        int $bankQuestionId,
        int $panelistId,
        string $difficulty
    ): int {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $bank = Database::fetch(
                'SELECT * FROM association_question_bank WHERE question_id = ?',
                [$bankQuestionId]
            );
            if (!$bank) throw new RuntimeException('Bank question not found.');
            if (!empty($bank['promoted_to_master'])) {
                throw new RuntimeException('Already migrated.');
            }

            $newId = Database::insert(
                'INSERT INTO master_questions
                    (source_question_id, association_id, question_text,
                     option_a, option_b, option_c, option_d, correct_option,
                     explanation, sport, category, difficulty, intended_round,
                     status, added_by_panelist_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, "active", ?)',
                [
                    $bankQuestionId,
                    (int)$bank['association_id'],
                    $bank['question_text'],
                    $bank['option_a'], $bank['option_b'],
                    $bank['option_c'], $bank['option_d'],
                    $bank['correct_option'],
                    $bank['explanation'],
                    $bank['sport'],
                    $bank['category'],
                    $difficulty,
                    $panelistId,
                ]
            );

            Database::execute(
                "UPDATE association_question_bank
                    SET status = 'approved',
                        reviewed_by_panelist_id = ?,
                        reviewed_at = NOW(),
                        promoted_to_master = 1,
                        master_question_id = ?,
                        reject_reason = NULL
                  WHERE question_id = ?",
                [$panelistId, $newId, $bankQuestionId]
            );

            $pdo->commit();
            return $newId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function update(int $id, array $d): void
    {
        Database::execute(
            'UPDATE master_questions
                SET question_text = ?, option_a = ?, option_b = ?,
                    option_c = ?, option_d = ?, correct_option = ?,
                    explanation = ?, sport = ?, category = ?, difficulty = ?,
                    intended_round = ?, status = ?
              WHERE master_question_id = ?',
            [
                $d['question_text'],
                $d['option_a'], $d['option_b'], $d['option_c'], $d['option_d'],
                $d['correct_option'],
                $d['explanation']      ?: null,
                $d['sport']            ?: null,
                $d['category']         ?: null,
                $d['difficulty']       ?: 'medium',
                $d['intended_round'] !== '' ? (int)$d['intended_round'] : null,
                $d['status']           ?: 'active',
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute(
            'DELETE FROM master_questions WHERE master_question_id = ?',
            [$id]
        );
    }

    public static function countActive(?int $associationId = null): int
    {
        if ($associationId === null) {
            return (int)Database::fetch(
                'SELECT COUNT(*) AS c FROM master_questions WHERE status = "active"'
            )['c'];
        }
        return (int)Database::fetch(
            'SELECT COUNT(*) AS c FROM master_questions
              WHERE association_id = ? AND status = "active"',
            [$associationId]
        )['c'];
    }
}
