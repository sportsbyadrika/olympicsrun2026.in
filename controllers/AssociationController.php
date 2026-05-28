<?php
final class AssociationController
{
    public function dashboard(): void
    {
        Auth::requireRole(Auth::ROLE_ASSOCIATION);

        $associationId = Auth::associationId();
        $qCounts = $associationId
            ? AssociationQuestion::countsByStatus($associationId)
            : ['total' => 0, 'draft' => 0, 'pending' => 0, 'approved' => 0,
               'rejected' => 0, 'needs_revision' => 0];

        render('association/dashboard', [
            'title'   => 'Association Dashboard — Olympics Run 2026',
            'qCounts' => $qCounts,
        ]);
    }
}
