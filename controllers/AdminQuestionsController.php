<?php
/**
 * Admin view over the curated master question pool (all associations).
 * Read-only browse with filters; authoring stays in the panelist module.
 */
final class AdminQuestionsController
{
    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);

        $filters = [
            'association_id' => $_GET['association_id'] ?? '',
            'difficulty'     => $_GET['difficulty']     ?? '',
            'status'         => $_GET['status']         ?? '',
            'round'          => $_GET['round']          ?? '',
        ];

        render('admin/questions/index', [
            'title'        => 'Questions — Admin',
            'questions'    => MasterQuestion::all(null, $filters),
            'associations' => Association::all(),
            'filters'      => $filters,
        ]);
    }
}
