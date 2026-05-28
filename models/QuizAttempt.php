<?php
/**
 * The school's quiz attempt. A row in slot_schools doubles as the attempt
 * record; this class wraps the read/write paths the quiz engine needs.
 *
 * Time is *always* derived from server time. The browser clock is never
 * trusted.
 */
final class QuizAttempt
{
    /**
     * Every active assignment for a school (used by the dashboard).
     * @return array<int,array<string,mixed>>
     */
    public static function allForSchool(int $schoolId): array
    {
        return Database::fetchAll(
            'SELECT ss.*, s.slot_id, s.slot_label, s.starts_at AS slot_starts,
                    s.ends_at AS slot_ends, s.status AS slot_status,
                    r.round_id, r.round_number, r.name AS round_name,
                    r.quiz_duration_minutes, r.questions_per_quiz,
                    r.marks_correct, r.marks_wrong, r.marks_unanswered,
                    r.status AS round_status, r.association_id
               FROM slot_schools ss
               JOIN slots  s ON s.slot_id  = ss.slot_id
               JOIN rounds r ON r.round_id = s.round_id
              WHERE ss.school_id = ?
              ORDER BY r.round_number ASC, s.starts_at ASC',
            [$schoolId]
        );
    }

    /**
     * The single attempt this school should be acting on right now —
     * prefers an in_progress row, then the soonest still-assigned slot
     * whose window is currently open.
     */
    public static function currentAttemptForSchool(int $schoolId): ?array
    {
        return Database::fetch(
            'SELECT ss.*, s.slot_id, s.slot_label, s.starts_at AS slot_starts,
                    s.ends_at AS slot_ends, s.status AS slot_status,
                    r.round_id, r.round_number, r.name AS round_name,
                    r.quiz_duration_minutes, r.questions_per_quiz,
                    r.marks_correct, r.marks_wrong, r.marks_unanswered,
                    r.association_id
               FROM slot_schools ss
               JOIN slots  s ON s.slot_id  = ss.slot_id
               JOIN rounds r ON r.round_id = s.round_id
              WHERE ss.school_id = ?
                AND ss.attempt_status IN ("assigned", "in_progress")
              ORDER BY
                CASE ss.attempt_status WHEN "in_progress" THEN 0 ELSE 1 END,
                s.starts_at ASC
              LIMIT 1',
            [$schoolId]
        );
    }

    public static function findById(int $slotSchoolId): ?array
    {
        return Database::fetch(
            'SELECT ss.*, s.slot_id, s.slot_label, s.starts_at AS slot_starts,
                    s.ends_at AS slot_ends,
                    r.round_id, r.round_number, r.name AS round_name,
                    r.quiz_duration_minutes, r.questions_per_quiz,
                    r.marks_correct, r.marks_wrong, r.marks_unanswered,
                    r.association_id
               FROM slot_schools ss
               JOIN slots  s ON s.slot_id  = ss.slot_id
               JOIN rounds r ON r.round_id = s.round_id
              WHERE ss.slot_school_id = ?',
            [$slotSchoolId]
        );
    }

    /** Is the slot window currently open for this attempt to start in? */
    public static function isWithinSlotWindow(array $attempt, ?int $now = null): bool
    {
        $now   = $now ?? time();
        $start = strtotime((string)$attempt['slot_starts']);
        $end   = strtotime((string)$attempt['slot_ends']);
        return $now >= $start && $now <= $end;
    }

    /** Atomically flip an 'assigned' row to 'in_progress' + stamp started_at. */
    public static function start(int $slotSchoolId): bool
    {
        return Database::execute(
            'UPDATE slot_schools
                SET started_at     = NOW(),
                    attempt_status = "in_progress"
              WHERE slot_school_id = ?
                AND attempt_status = "assigned"',
            [$slotSchoolId]
        ) > 0;
    }

    /**
     * Server-truth remaining seconds for an in-progress attempt.
     * Returns the full quiz duration if not yet started.
     */
    public static function remainingSeconds(array $attempt, ?int $now = null): int
    {
        $now   = $now ?? time();
        $total = (int)$attempt['quiz_duration_minutes'] * 60;
        if (empty($attempt['started_at'])) return $total;
        $elapsed = $now - strtotime((string)$attempt['started_at']);
        return max(0, $total - $elapsed);
    }

    /** Questions for a slot in order, joined to master content. */
    public static function questionsForSlot(int $slotId): array
    {
        return Database::fetchAll(
            'SELECT sq.slot_question_id, sq.position,
                    mq.master_question_id, mq.question_text,
                    mq.option_a, mq.option_b, mq.option_c, mq.option_d
               FROM slot_questions sq
               JOIN master_questions mq
                 ON mq.master_question_id = sq.master_question_id
              WHERE sq.slot_id = ?
              ORDER BY sq.position ASC',
            [$slotId]
        );
    }

    /**
     * Existing responses for an attempt, keyed by slot_question_id.
     * Returns rows as ['chosen_option' => 'A'|'B'|'C'|'D'|null, 'status' => ...].
     */
    public static function responsesByQuestion(int $slotSchoolId): array
    {
        $rows = Database::fetchAll(
            'SELECT slot_question_id, chosen_option, status
               FROM responses
              WHERE slot_school_id = ?',
            [$slotSchoolId]
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['slot_question_id']] = $r;
        }
        return $out;
    }

    /**
     * Upsert one answer. $chosenOption may be 'A'..'D' (answered) or null
     * (an explicit "draft / saved without choosing" — gives the side panel a
     * distinct visual state).
     */
    public static function saveAnswer(
        int $slotSchoolId,
        int $slotQuestionId,
        ?string $chosenOption
    ): void {
        // Resolve the master_question_id by joining through slot_questions,
        // and also confirm the slot_question really belongs to this attempt's
        // slot — prevents an authenticated school from answering some other
        // slot's question by guessing IDs.
        $row = Database::fetch(
            'SELECT sq.master_question_id
               FROM slot_questions sq
               JOIN slot_schools  ss ON ss.slot_id = sq.slot_id
              WHERE sq.slot_question_id = ?
                AND ss.slot_school_id   = ?',
            [$slotQuestionId, $slotSchoolId]
        );
        if (!$row) {
            throw new RuntimeException('slot_question not in this attempt');
        }

        if ($chosenOption !== null && !in_array($chosenOption, ['A','B','C','D'], true)) {
            throw new InvalidArgumentException('chosen_option must be A/B/C/D or null');
        }

        // Upsert via the (slot_school_id, slot_question_id) unique key.
        Database::execute(
            'INSERT INTO responses
                (slot_school_id, slot_question_id, master_question_id,
                 chosen_option, status, answered_at)
             VALUES (?, ?, ?, ?, "draft", NOW())
             ON DUPLICATE KEY UPDATE
                chosen_option = VALUES(chosen_option),
                status        = "draft",
                answered_at   = NOW()',
            [$slotSchoolId, $slotQuestionId, (int)$row['master_question_id'], $chosenOption]
        );
    }
}
