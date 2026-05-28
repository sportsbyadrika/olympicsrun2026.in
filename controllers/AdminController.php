<?php
final class AdminController
{
    public function dashboard(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);

        $schoolCounts = School::countByStatus();

        $stats = [
            'associations'      => Association::countAll(),
            'association_users' => AssociationUser::countAll(),
            'panelists'         => Panelist::countAll(),
            'school_logins'     => SchoolLogin::countAll(),
            'schools_total'     => $schoolCounts['total'] ?? 0,
            'schools_pending'   => $schoolCounts['pending'] ?? 0,
            'schools_approved'  => $schoolCounts['approved'] ?? 0,
        ];

        render('admin/dashboard', [
            'title' => 'Admin Dashboard — Olympics Run 2026',
            'stats' => $stats,
        ]);
    }
}
