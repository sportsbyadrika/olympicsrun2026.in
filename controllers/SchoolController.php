<?php
final class SchoolController
{
    public function dashboard(): void
    {
        Auth::requireRole(Auth::ROLE_SCHOOL);
        render('school/dashboard', [
            'title' => 'School Dashboard — Olympics Run 2026',
        ]);
    }
}
