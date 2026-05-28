<?php
final class PanelistReviewController
{
    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        $associationId = $this->associationId();
        render('panelist/review/index', [
            'title'    => 'Review Queue — Panel',
            'pending'  => AssociationQuestion::pendingForAssociation($associationId),
        ]);
    }

    /** Approve & migrate a single question; difficulty supplied by reviewer. */
    public function approve(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidPost();
        $associationId = $this->associationId();

        $bank = AssociationQuestion::findScoped((int)$id, $associationId);
        if (!$bank || $bank['status'] !== 'pending') {
            flash_set('error', 'Question is no longer in the pending queue.');
            redirect('/panelist/review');
        }

        $difficulty = (string)($_POST['difficulty'] ?? $bank['difficulty']);
        if (!in_array($difficulty, MasterQuestion::DIFFICULTIES, true)) {
            flash_set('error', 'Invalid difficulty.');
            redirect('/panelist/review');
        }

        try {
            MasterQuestion::migrateFromBank((int)$id, (int)Auth::id(), $difficulty);
            flash_set('success', 'Approved and migrated to master bank.');
        } catch (Throwable $e) {
            flash_set('error', 'Could not migrate: ' . $e->getMessage());
        }
        redirect('/panelist/review');
    }

    /** Bulk approve: migrate every selected question with one difficulty. */
    public function approveBulk(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidPost();
        $associationId = $this->associationId();

        $ids = $_POST['question_ids'] ?? [];
        $difficulty = (string)($_POST['difficulty'] ?? '');
        if (!in_array($difficulty, MasterQuestion::DIFFICULTIES, true)) {
            flash_set('error', 'Pick a difficulty for the bulk approve.');
            redirect('/panelist/review');
        }
        if (!is_array($ids) || empty($ids)) {
            flash_set('error', 'Select at least one question.');
            redirect('/panelist/review');
        }

        $ok = 0; $failed = 0;
        foreach ($ids as $rawId) {
            $qid = (int)$rawId;
            if ($qid <= 0) continue;
            $bank = AssociationQuestion::findScoped($qid, $associationId);
            if (!$bank || $bank['status'] !== 'pending') { $failed++; continue; }
            try {
                MasterQuestion::migrateFromBank($qid, (int)Auth::id(), $difficulty);
                $ok++;
            } catch (Throwable $e) {
                $failed++;
            }
        }

        if ($ok > 0)     flash_set('success', $ok . ' question' . ($ok === 1 ? '' : 's') . ' migrated to master bank.');
        if ($failed > 0) flash_set('warning', $failed . ' question' . ($failed === 1 ? '' : 's') . ' could not be migrated.');
        redirect('/panelist/review');
    }

    public function reject(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidPost();
        $associationId = $this->associationId();

        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($reason === '') {
            flash_set('error', 'Please provide a reason when rejecting.');
            redirect('/panelist/review');
        }

        $bank = AssociationQuestion::findScoped((int)$id, $associationId);
        if (!$bank || $bank['status'] !== 'pending') {
            flash_set('error', 'Question is no longer in the pending queue.');
            redirect('/panelist/review');
        }

        AssociationQuestion::reject((int)$id, (int)Auth::id(), $reason);
        flash_set('success', 'Question rejected.');
        redirect('/panelist/review');
    }

    public function revise(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidPost();
        $associationId = $this->associationId();

        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($reason === '') {
            flash_set('error', 'Please add a note explaining the revision.');
            redirect('/panelist/review');
        }

        $bank = AssociationQuestion::findScoped((int)$id, $associationId);
        if (!$bank || $bank['status'] !== 'pending') {
            flash_set('error', 'Question is no longer in the pending queue.');
            redirect('/panelist/review');
        }

        AssociationQuestion::sendForRevision((int)$id, (int)Auth::id(), $reason);
        flash_set('success', 'Sent back to the author for revision.');
        redirect('/panelist/review');
    }

    private function associationId(): int
    {
        $id = Auth::associationId();
        if (!$id) { http_response_code(403); render('errors/403'); exit; }
        return $id;
    }
}
