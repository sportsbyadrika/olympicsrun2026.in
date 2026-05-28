<?php
final class PanelistController
{
    public function dashboard(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        $associationId = (int)Auth::associationId();

        $pending = (int)Database::fetch(
            "SELECT COUNT(*) AS c FROM association_question_bank
              WHERE association_id = ? AND status = 'pending'",
            [$associationId]
        )['c'];

        $masterCount = MasterQuestion::countActive($associationId);

        $myReviews = (int)Database::fetch(
            'SELECT COUNT(*) AS c FROM association_question_bank
              WHERE reviewed_by_panelist_id = ?',
            [Auth::id()]
        )['c'];

        render('panelist/dashboard', [
            'title'        => 'Panelist Dashboard — Olympics Run 2026',
            'pendingCount' => $pending,
            'masterCount'  => $masterCount,
            'myReviews'    => $myReviews,
        ]);
    }
}
