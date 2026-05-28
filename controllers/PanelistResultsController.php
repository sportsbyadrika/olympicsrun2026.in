<?php
final class PanelistResultsController
{
    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        $associationId = $this->associationId();

        // Refresh ranks for every round so the index is always up to date.
        foreach (QuizResult::roundsForAssociation($associationId) as $r) {
            QuizResult::recomputeRanks((int)$r['round_id']);
        }

        render('panelist/results/index', [
            'title'  => 'Results — Panel',
            'rounds' => QuizResult::roundsForAssociation($associationId),
        ]);
    }

    public function showRound(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        $round = $this->ownedRound((int)$id);

        QuizResult::recomputeRanks((int)$id);

        render('panelist/results/round', [
            'title'   => 'Round ' . $round['round_number'] . ' — ' . $round['name'],
            'round'   => $round,
            'results' => QuizResult::forRound((int)$id),
        ]);
    }

    public function qualifyForm(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        $round = $this->ownedRound((int)$id);
        if ((int)$round['round_number'] !== 1) {
            flash_set('error', 'Qualifier selection only applies to Round 1.');
            redirect('/panelist/results');
        }

        QuizResult::recomputeRanks((int)$id);
        $ctx = QuizResult::round2Context((int)$round['association_id']);

        render('panelist/results/qualify', [
            'title'      => 'Select Round 2 qualifiers',
            'round'      => $round,
            'results'    => QuizResult::forRound((int)$id),
            'r2'         => $ctx['round'],
            'r2Slots'    => $ctx['slots'],
            'r2Existing' => $ctx['existing'],
        ]);
    }

    public function qualifySave(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidPost();
        $round = $this->ownedRound((int)$id);
        if ((int)$round['round_number'] !== 1) {
            flash_set('error', 'Only Round 1 can mark qualifiers.');
            redirect('/panelist/results');
        }

        $qualifiers      = $_POST['qualifier_school'] ?? [];
        $slotAssignments = $_POST['slot_assignment']  ?? [];
        if (!is_array($qualifiers))      $qualifiers = [];
        if (!is_array($slotAssignments)) $slotAssignments = [];

        $qIds = array_values(array_unique(array_filter(
            array_map('intval', $qualifiers),
            static fn(int $v) => $v > 0
        )));

        QuizResult::setQualifiers((int)$id, $qIds);

        // Best-effort assign to chosen R2 slots. We only allow slots in the
        // panelist's association whose round_number is 2.
        $assigned = 0; $skipped = 0; $emailedSchools = [];
        foreach ($qIds as $schoolId) {
            $slotId = (int)($slotAssignments[$schoolId] ?? 0);
            if ($slotId <= 0) { $skipped++; continue; }

            $slot = Slot::find($slotId);
            if (!$slot || (int)$slot['association_id'] !== (int)$round['association_id']) {
                $skipped++; continue;
            }
            $slotRound = Round::find((int)$slot['round_id']);
            if (!$slotRound || (int)$slotRound['round_number'] !== 2) {
                $skipped++; continue;
            }

            $login = Database::fetch(
                'SELECT school_login_id FROM school_logins
                  WHERE school_id = ? AND status = "active"
                  ORDER BY school_login_id LIMIT 1',
                [$schoolId]
            );

            Slot::assignSchool(
                $slotId,
                $schoolId,
                $login ? (int)$login['school_login_id'] : null,
                null
            );
            $assigned++;
            $emailedSchools[] = $schoolId;
        }

        $msg = count($qIds) . ' qualifier(s) marked';
        if ($assigned > 0) $msg .= ', ' . $assigned . ' assigned to Round 2';
        if ($skipped > 0)  $msg .= ' (' . $skipped . ' qualifier(s) had no slot picked)';

        if ($assigned > 0 && Settings::bool('mail_auto_send_on_assign')) {
            $sent = $failed = 0;
            foreach ($emailedSchools as $sid) {
                $r = SchoolMail::sendCredentialsForSchool($sid);
                $sent   += $r['sent'];
                $failed += $r['failed'];
            }
            $msg .= ". Credentials emailed: {$sent} sent, {$failed} failed";
        }
        flash_set('success', $msg . '.');
        redirect('/panelist/results/round/' . (int)$id);
    }

    public function publish(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidPost();
        $round = $this->ownedRound((int)$id);

        $n = QuizResult::publishRound((int)$id);
        flash_set('success', $n . ' result(s) published to schools.');
        redirect('/panelist/results/round/' . (int)$id);
    }

    public function unpublish(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidPost();
        $round = $this->ownedRound((int)$id);

        $n = QuizResult::unpublishRound((int)$id);
        flash_set('success', $n . ' result(s) un-published.');
        redirect('/panelist/results/round/' . (int)$id);
    }

    public function finalView(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        $associationId = $this->associationId();

        foreach (QuizResult::roundsForAssociation($associationId) as $r) {
            QuizResult::recomputeRanks((int)$r['round_id']);
        }

        $data = QuizResult::consolidated($associationId);
        render('panelist/results/final', [
            'title'  => 'Final consolidated result',
            'rounds' => $data['rounds'],
            'rows'   => $data['rows'],
        ]);
    }

    /* --- helpers --- */

    private function associationId(): int
    {
        $id = Auth::associationId();
        if (!$id) { http_response_code(403); render('errors/403'); exit; }
        return $id;
    }

    private function ownedRound(int $roundId): array
    {
        $round = Round::find($roundId);
        if (!$round || (int)$round['association_id'] !== $this->associationId()) {
            http_response_code(404); render('errors/404'); exit;
        }
        return $round;
    }
}
