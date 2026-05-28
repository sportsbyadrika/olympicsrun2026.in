<?php
/**
 * Printable HTML reports. Each report renders without the app layout — it's
 * a complete document so users can save / share / print it on its own.
 *
 * Scoping:
 *   Admin           -> any association
 *   Panelist        -> their association only
 *   Association     -> their association only
 *   School          -> not allowed
 */
final class ReportsController
{
    /** Round 1 or Round 2 leaderboard. */
    public function round(string $roundId): void
    {
        Auth::requireLogin();
        $round = Round::find((int)$roundId);
        if (!$round) { http_response_code(404); render('errors/404'); return; }

        $this->guardAssociation((int)$round['association_id']);
        QuizResult::recomputeRanks((int)$roundId);

        render('reports/round', [
            'title'       => 'Olympics Run 2026 · Round '
                              . (int)$round['round_number'] . ' Results',
            'layout'      => false,
            'round'       => $round,
            'results'     => QuizResult::forRound((int)$roundId),
            'association' => Association::find((int)$round['association_id']),
            'generated_at'=> date('Y-m-d H:i'),
        ]);
    }

    /** Consolidated R1 + R2 final result for an association. */
    public function finalReport(string $associationId): void
    {
        Auth::requireLogin();
        $assocId = (int)$associationId;
        $this->guardAssociation($assocId);

        foreach (QuizResult::roundsForAssociation($assocId) as $r) {
            QuizResult::recomputeRanks((int)$r['round_id']);
        }

        $data = QuizResult::consolidated($assocId);

        render('reports/final', [
            'title'       => 'Olympics Run 2026 · Final Consolidated Result',
            'layout'      => false,
            'rounds'      => $data['rounds'],
            'rows'        => $data['rows'],
            'association' => Association::find($assocId),
            'generated_at'=> date('Y-m-d H:i'),
        ]);
    }

    private function guardAssociation(int $associationId): void
    {
        $role = Auth::role();
        if ($role === Auth::ROLE_ADMIN) return;
        if ($role === Auth::ROLE_PANELIST || $role === Auth::ROLE_ASSOCIATION) {
            if ((int)Auth::associationId() === $associationId) return;
        }
        http_response_code(403);
        render('errors/403');
        exit;
    }
}
