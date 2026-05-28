<?php
final class SchoolController
{
    public function dashboard(): void
    {
        Auth::requireRole(Auth::ROLE_SCHOOL);
        $schoolId = (int)Auth::schoolId();
        // If the timer ran out while the browser was closed, fix it up before
        // we render anything.
        QuizAttempt::forceSubmitExpired();
        $attempts = QuizAttempt::allForSchool($schoolId);

        render('school/dashboard', [
            'title'    => 'School Dashboard — Olympics Run 2026',
            'attempts' => $attempts,
            'now'      => time(),
        ]);
    }
}
