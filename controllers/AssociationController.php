<?php
final class AssociationController
{
    public function dashboard(): void
    {
        Auth::requireRole(Auth::ROLE_ASSOCIATION);
        render('association/dashboard', [
            'title' => 'Association Dashboard — Olympics Run 2026',
        ]);
    }
}
