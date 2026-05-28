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

    /**
     * Per-session login throttle. Not a substitute for IP/account-based
     * limiting (which needs a shared store), but blocks the simplest
     * single-browser brute force.
     */
    private const MAX_BAD_ATTEMPTS = 5;
    private const COOLDOWN_SECONDS = 60;

    public function doLogin(): void
    {
        Csrf::requireValidPost();

        $role     = (string)($_POST['role']     ?? '');
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        // Throttle window check
        $throttle = $_SESSION['_login_throttle'] ?? ['count' => 0, 'until' => 0];
        if ($throttle['until'] > time()) {
            $wait = $throttle['until'] - time();
            flash_set('error', "Too many failed attempts. Try again in {$wait}s.");
            redirect('/login?role=' . urlencode($role));
        }

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
            $throttle['count']++;
            if ($throttle['count'] >= self::MAX_BAD_ATTEMPTS) {
                $throttle['until'] = time() + self::COOLDOWN_SECONDS;
                $throttle['count'] = 0;
            }
            $_SESSION['_login_throttle'] = $throttle;

            // Generic message — don't leak which of username/password was wrong,
            // or whether the user exists at all.
            flash_set('error', 'Invalid credentials, or account not active.');
            $_SESSION['_old'] = ['username' => $username, 'role' => $role];
            redirect('/login?role=' . urlencode($role));
        }

        // Reset throttle on success
        unset($_SESSION['_login_throttle']);

        flash_set('success', 'Welcome back, ' . (Auth::user()['name'] ?? $username) . '.');
        redirect(Auth::dashboardUrl($role));
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
