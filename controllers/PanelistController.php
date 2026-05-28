<?php
final class PanelistController
{
    public function dashboard(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        render('panelist/dashboard', [
            'title' => 'Panelist Dashboard — Olympics Run 2026',
        ]);
    }
}
