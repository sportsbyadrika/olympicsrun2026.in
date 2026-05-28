<?php
/**
 * Questions in an association's bank. All reads/writes here are scoped to a
 * single association_id — callers must pass the current user's association.
 */
final class AssociationQuestion
{
    public const STATUSES = ['draft', 'pending', 'approved', 'rejected', 'needs_revision'];
    public const DIFFICULTIES = ['easy', 'medium', 'hard'];

    /** Statuses the author can still edit / delete. */
    public static function isEditable(string $status): bool
    {
        return in_array($status, ['draft', 'needs_revision'], true);
    }

    /** Statuses the author can submit (or re-submit) to the panel. */
    public static function isSubmittable(string $status): bool
    {
        return in_array($status, ['draft', 'needs_revision'], true);
    }

    public static function forAssociation(int $associationId, ?string $status = null): array
    {
        $sql = 'SELECT * FROM association_question_bank WHERE association_id = ?';
        $params = [$associationId];
        if ($status !== null && $status !== '' && $status !== 'all') {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY updated_at DESC, question_id DESC';
        return Database::fetchAll($sql, $params);
    }

    public static function findScoped(int $id, int $associationId): ?array
    {
        return Database::fetch(
            'SELECT * FROM association_question_bank
              WHERE question_id = ? AND association_id = ?',
            [$id, $associationId]
        );
    }

    public static function create(int $associationId, int $createdByUserId, array $d): int
    {
        return Database::insert(
            'INSERT INTO association_question_bank
                (association_id, created_by_assoc_user_id, question_text,
                 option_a, option_b, option_c, option_d, correct_option,
                 explanation, sport, category, difficulty, reference_source,
                 status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "draft")',
            [
                $associationId,
                $createdByUserId,
                $d['question_text'],
                $d['option_a'], $d['option_b'], $d['option_c'], $d['option_d'],
                $d['correct_option'],
                $d['explanation']       ?: null,
                $d['sport']             ?: null,
                $d['category']          ?: null,
                $d['difficulty']        ?: 'medium',
                $d['reference_source']  ?: null,
            ]
        );
    }

    /**
     * Update edits the question. If $resubmit is true and the existing row is
     * in needs_revision, move it back to pending.
     */
    public static function update(int $id, int $associationId, array $d, bool $resubmit = false): void
    {
        $existing = self::findScoped($id, $associationId);
        if (!$existing) return;

        $newStatus = $existing['status'];
        $submittedAt = $existing['submitted_at'];
        if ($resubmit && $existing['status'] === 'needs_revision') {
            $newStatus = 'pending';
            $submittedAt = date('Y-m-d H:i:s');
        }

        Database::execute(
            'UPDATE association_question_bank
                SET question_text = ?, option_a = ?, option_b = ?,
                    option_c = ?, option_d = ?, correct_option = ?,
                    explanation = ?, sport = ?, category = ?, difficulty = ?,
                    reference_source = ?, status = ?, submitted_at = ?
              WHERE question_id = ? AND association_id = ?',
            [
                $d['question_text'],
                $d['option_a'], $d['option_b'], $d['option_c'], $d['option_d'],
                $d['correct_option'],
                $d['explanation']       ?: null,
                $d['sport']             ?: null,
                $d['category']          ?: null,
                $d['difficulty']        ?: 'medium',
                $d['reference_source']  ?: null,
                $newStatus,
                $submittedAt,
                $id, $associationId,
            ]
        );
    }

    public static function delete(int $id, int $associationId): void
    {
        Database::execute(
            'DELETE FROM association_question_bank
              WHERE question_id = ? AND association_id = ?',
            [$id, $associationId]
        );
    }

    /**
     * Move a set of draft / needs_revision questions to "pending" for the
     * expert panel. Other statuses are left alone.
     * Returns the count actually transitioned.
     */
    public static function submitToPanel(array $ids, int $associationId): int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, static fn(int $v) => $v > 0);
        if (empty($ids)) return 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$associationId]);

        return Database::execute(
            "UPDATE association_question_bank
                SET status = 'pending',
                    submitted_at = NOW()
              WHERE question_id IN ($placeholders)
                AND association_id = ?
                AND status IN ('draft', 'needs_revision')",
            $params
        );
    }

    /** Pending review queue for the panel of an association. */
    public static function pendingForAssociation(int $associationId): array
    {
        return Database::fetchAll(
            "SELECT qb.*, au.full_name AS author_name
               FROM association_question_bank qb
          LEFT JOIN association_users au
                 ON au.association_user_id = qb.created_by_assoc_user_id
              WHERE qb.association_id = ?
                AND qb.status = 'pending'
              ORDER BY qb.submitted_at ASC, qb.question_id ASC",
            [$associationId]
        );
    }

    public static function reject(int $id, int $panelistId, string $reason): void
    {
        Database::execute(
            "UPDATE association_question_bank
                SET status = 'rejected',
                    reviewed_by_panelist_id = ?,
                    reviewed_at = NOW(),
                    reject_reason = ?
              WHERE question_id = ?
                AND status = 'pending'",
            [$panelistId, $reason, $id]
        );
    }

    public static function sendForRevision(int $id, int $panelistId, string $reason): void
    {
        Database::execute(
            "UPDATE association_question_bank
                SET status = 'needs_revision',
                    reviewed_by_panelist_id = ?,
                    reviewed_at = NOW(),
                    reject_reason = ?
              WHERE question_id = ?
                AND status = 'pending'",
            [$panelistId, $reason, $id]
        );
    }

    public static function countsByStatus(int $associationId): array
    {
        $rows = Database::fetchAll(
            'SELECT status, COUNT(*) AS c
               FROM association_question_bank
              WHERE association_id = ?
              GROUP BY status',
            [$associationId]
        );
        $out = ['total' => 0];
        foreach (self::STATUSES as $s) $out[$s] = 0;
        foreach ($rows as $r) {
            $out[$r['status']] = (int)$r['c'];
            $out['total'] += (int)$r['c'];
        }
        return $out;
    }
}
