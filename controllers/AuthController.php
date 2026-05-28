<?php
/**
 * Handles login (all 4 roles) and logout.
 */
final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardUrl(Auth::role()));
        }

        render('auth/login', [
            'title'         => 'Sign in — Olympics Run 2026',
            'roles'         => Auth::roles(),
            'selected_role' => $_GET['role'] ?? Auth::ROLE_ADMIN,
        ]);
    }

    public function doLogin(): void
    {
        Csrf::requireValidPost();

        $role     = (string)($_POST['role']     ?? '');
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if (!array_key_exists($role, Auth::roles())) {
            flash_set('error', 'Please choose a valid role.');
            $_SESSION['_old'] = ['username' => $username, 'role' => $role];
            redirect('/login');
        }

        if ($username === '' || $password === '') {
            flash_set('error', 'Username and password are required.');
            $_SESSION['_old'] = ['username' => $username, 'role' => $role];
            redirect('/login?role=' . urlencode($role));
        }

        if (!Auth::attempt($role, $username, $password)) {
            flash_set('error', 'Invalid credentials, or account not active.');
            $_SESSION['_old'] = ['username' => $username, 'role' => $role];
            redirect('/login?role=' . urlencode($role));
        }

        flash_set('success', 'Welcome back, ' . (Auth::user()['name'] ?? $username) . '.');
        redirect(Auth::dashboardUrl($role));
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
