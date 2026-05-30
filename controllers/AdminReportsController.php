<?php
/**
 * Reports hub for admins: lists every association with its rounds and links
 * to the existing printable reports (/reports/print/round|final). The actual
 * report rendering lives in ReportsController, which already permits admins.
 */
final class AdminReportsController
{
    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);

        $associations = Association::all();
        $byAssociation = [];
        foreach ($associations as $a) {
            $byAssociation[] = [
                'association' => $a,
                'rounds'      => QuizResult::roundsForAssociation((int)$a['association_id']),
            ];
        }

        render('admin/reports/index', [
            'title'  => 'Reports — Admin',
            'groups' => $byAssociation,
        ]);
    }
}
