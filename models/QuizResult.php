<?php
/**
 * Aggregate queries over the `results` table.
 *
 * Ranks are deterministic: ORDER BY total_score DESC, time_taken_seconds ASC,
 * result_id ASC. Recompute is cheap (single UPDATE with a window function).
 */
final class QuizResult
{
    /**
     * Per-round summary for the association's results index.
     * @return array<int,array<string,mixed>>
     */
    public static function roundsForAssociation(int $associationId): array
    {
        return Database::fetchAll(
            'SELECT r.round_id, r.round_number, r.name, r.status,
                    r.qualifiers_count,
                    (SELECT COUNT(*)  FROM slot_schools ss
                       JOIN slots s   ON s.slot_id = ss.slot_id
                      WHERE s.round_id = r.round_id) AS assigned_count,
                    (SELECT COUNT(*)  FROM results res
                      WHERE res.round_id = r.round_id) AS submitted_count,
                    (SELECT COUNT(*)  FROM results res
                      WHERE res.round_id = r.round_id AND res.published = 1) AS published_count,
                    (SELECT COUNT(*)  FROM results res
                      WHERE res.round_id = r.round_id
                        AND res.qualified_next_round = 1) AS qualifiers_marked,
                    (SELECT AVG(total_score) FROM results res
                      WHERE res.round_id = r.round_id) AS avg_score,
                    (SELECT MAX(total_score) FROM results res
                      WHERE res.round_id = r.round_id) AS top_score
               FROM rounds r
              WHERE r.association_id = ?
              ORDER BY r.round_number',
            [$associationId]
        );
    }

    public static function forRound(int $roundId): array
    {
        return Database::fetchAll(
            'SELECT res.*,
                    s.school_name, s.school_code, s.region,
                    sl.slot_label,
                    ss.attempt_status, ss.started_at, ss.submitted_at,
                    ss.time_taken_seconds AS attempt_time
               FROM results res
               JOIN slot_schools ss ON ss.slot_school_id = res.slot_school_id
               JOIN slots        sl ON sl.slot_id        = ss.slot_id
               JOIN schools      s  ON s.school_id      = res.school_id
              WHERE res.round_id = ?
              ORDER BY res.rank_in_round ASC,
                       res.total_score DESC,
                       ss.time_taken_seconds ASC',
            [$roundId]
        );
    }

    /** Window-function rank, deterministic tie-break. MySQL 8. */
    public static function recomputeRanks(int $roundId): void
    {
        Database::execute(
            'UPDATE results r
               JOIN (
                   SELECT result_id,
                          ROW_NUMBER() OVER (
                              ORDER BY total_score DESC,
                                       time_taken_seconds ASC,
                                       result_id ASC
                          ) AS rnk
                     FROM results
                    WHERE round_id = ?
               ) ranked ON ranked.result_id = r.result_id
                SET r.rank_in_round = ranked.rnk
              WHERE r.round_id = ?',
            [$roundId, $roundId]
        );
    }

    /**
     * Replace the qualifier set for a round in one transaction. Any
     * previously-marked qualifier that's not in $schoolIds is unmarked.
     */
    public static function setQualifiers(int $roundId, array $schoolIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $schoolIds)));
        $ids = array_filter($ids, static fn(int $v) => $v > 0);

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            Database::execute(
                'UPDATE results SET qualified_next_round = 0 WHERE round_id = ?',
                [$roundId]
            );
            if (!empty($ids)) {
                $ph = implode(',', array_fill(0, count($ids), '?'));
                Database::execute(
                    "UPDATE results
                        SET qualified_next_round = 1
                      WHERE round_id = ?
                        AND school_id IN ({$ph})",
                    array_merge([$roundId], $ids)
                );
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function publishRound(int $roundId): int
    {
        return Database::execute(
            'UPDATE results
                SET published = 1, published_at = NOW()
              WHERE round_id = ? AND published = 0',
            [$roundId]
        );
    }

    public static function unpublishRound(int $roundId): int
    {
        return Database::execute(
            'UPDATE results
                SET published = 0, published_at = NULL
              WHERE round_id = ? AND published = 1',
            [$roundId]
        );
    }

    /**
     * Consolidated R1 + R2 board for an association.
     * Returns ['rounds' => [r1,r2], 'rows' => [...]].
     */
    public static function consolidated(int $associationId): array
    {
        $rounds = Database::fetchAll(
            'SELECT round_id, round_number, name, questions_per_quiz, status
               FROM rounds
              WHERE association_id = ?
              ORDER BY round_number',
            [$associationId]
        );
        if (empty($rounds)) return ['rounds' => [], 'rows' => []];

        $r1Id = $r2Id = null;
        foreach ($rounds as $r) {
            if ((int)$r['round_number'] === 1) $r1Id = (int)$r['round_id'];
            if ((int)$r['round_number'] === 2) $r2Id = (int)$r['round_id'];
        }

        $rows = Database::fetchAll(
            'SELECT s.school_id, s.school_name, s.school_code, s.region,
                    r1.total_score      AS r1_score,
                    r1.rank_in_round    AS r1_rank,
                    r1.correct_count    AS r1_correct,
                    r1.total_questions  AS r1_total,
                    r1.time_taken_seconds AS r1_time,
                    r1.qualified_next_round AS r1_qualified,
                    r2.total_score      AS r2_score,
                    r2.rank_in_round    AS r2_rank,
                    r2.correct_count    AS r2_correct,
                    r2.total_questions  AS r2_total,
                    r2.time_taken_seconds AS r2_time,
                    (COALESCE(r1.total_score, 0) + COALESCE(r2.total_score, 0))
                        AS combined_score
               FROM schools s
          LEFT JOIN results r1
                 ON r1.school_id = s.school_id AND r1.round_id = ?
          LEFT JOIN results r2
                 ON r2.school_id = s.school_id AND r2.round_id = ?
              WHERE s.association_id = ?
                AND (r1.result_id IS NOT NULL OR r2.result_id IS NOT NULL)
              ORDER BY combined_score DESC,
                       (COALESCE(r1.time_taken_seconds, 0)
                        + COALESCE(r2.time_taken_seconds, 0)) ASC,
                       s.school_id ASC',
            [$r1Id ?? 0, $r2Id ?? 0, $associationId]
        );

        return ['rounds' => $rounds, 'rows' => $rows];
    }

    /** Find the association's Round 2 + its slots, for R2 assignment UI. */
    public static function round2Context(int $associationId): array
    {
        $r2 = Database::fetch(
            'SELECT round_id, name, status, quiz_duration_minutes, questions_per_quiz
               FROM rounds
              WHERE association_id = ? AND round_number = 2
              LIMIT 1',
            [$associationId]
        );
        if (!$r2) return ['round' => null, 'slots' => [], 'existing' => []];

        $slots = Database::fetchAll(
            'SELECT s.slot_id, s.slot_label, s.starts_at, s.ends_at, s.capacity,
                    (SELECT COUNT(*) FROM slot_schools ss
                      WHERE ss.slot_id = s.slot_id) AS assigned_count
               FROM slots s
              WHERE s.round_id = ?
              ORDER BY s.starts_at',
            [(int)$r2['round_id']]
        );

        $assignments = Database::fetchAll(
            'SELECT ss.school_id, ss.slot_id
               FROM slot_schools ss
               JOIN slots s ON s.slot_id = ss.slot_id
              WHERE s.round_id = ?',
            [(int)$r2['round_id']]
        );
        $existing = [];
        foreach ($assignments as $a) {
            $existing[(int)$a['school_id']] = (int)$a['slot_id'];
        }

        return ['round' => $r2, 'slots' => $slots, 'existing' => $existing];
    }
}
