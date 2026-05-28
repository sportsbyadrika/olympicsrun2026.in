<?php
final class SchoolController
{
    public function dashboard(): void
    {
        Auth::requireRole(Auth::ROLE_SCHOOL);
        $schoolId = (int)Auth::schoolId();
        $attempts = QuizAttempt::allForSchool($schoolId);

        render('school/dashboard', [
            'title'    => 'School Dashboard — Olympics Run 2026',
            'attempts' => $attempts,
            'now'      => time(),
        ]);
    }
}
