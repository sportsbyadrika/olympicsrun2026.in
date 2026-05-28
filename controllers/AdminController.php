<?php
final class AdminController
{
    public function dashboard(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/dashboard', [
            'title' => 'Admin Dashboard — Olympics Run 2026',
        ]);
    }
}
