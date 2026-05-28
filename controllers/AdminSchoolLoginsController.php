<?php
final class AdminSchoolLoginsController
{
    private const STATUSES = ['active', 'suspended'];

    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/school_logins/index', [
            'title'  => 'School Logins — Admin',
            'logins' => SchoolLogin::all(),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);

        $schools = School::all();
        $prefilledUsername = '';
        $prefilledPassword = '';

        // If admin clicked "Generate" from a school row, pre-fill the form.
        $schoolId = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
        if (!empty($_GET['generate']) && $schoolId > 0) {
            $school = School::find($schoolId);
            if ($school) {
                $prefilledUsername = SchoolLogin::suggestUsername((string)($school['school_code'] ?? $school['school_name']));
                $prefilledPassword = SchoolLogin::generatePassword();
            }
        }

        render('admin/school_logins/form', [
            'title'             => 'Add School Login',
            'login'             => null,
            'schools'           => $schools,
            'statuses'          => self::STATUSES,
            'preselectSchoolId' => $schoolId,
            'prefilledUsername' => $prefilledUsername,
            'prefilledPassword' => $prefilledPassword,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $v = $this->validate($_POST, null, true);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/school-logins/new');
        }

        SchoolLogin::create($_POST);
        flash_set('success', 'School login created.');
        if (!empty($_POST['_show_password'])) {
            flash_set('info', 'Password set: ' . $_POST['password']
                . ' — copy it now, it will not be shown again.');
        }
        redirect('/admin/school-logins');
    }

    public function edit(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $login = SchoolLogin::find((int)$id);
        if (!$login) { http_response_code(404); render('errors/404'); return; }
        render('admin/school_logins/form', [
            'title'    => 'Edit School Login',
            'login'    => $login,
            'schools'  => School::all(),
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $login = SchoolLogin::find((int)$id);
        if (!$login) { http_response_code(404); render('errors/404'); return; }

        $needsPw = !empty($_POST['password']);
        $v = $this->validate($_POST, (int)$id, $needsPw);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/school-logins/' . $id . '/edit');
        }

        SchoolLogin::update((int)$id, $_POST, $needsPw ? (string)$_POST['password'] : null);
        flash_set('success', 'School login updated.');
        redirect('/admin/school-logins');
    }

    public function destroy(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();
        try {
            SchoolLogin::delete((int)$id);
            flash_set('success', 'School login deleted.');
        } catch (Throwable $e) {
            flash_set('error', 'Cannot delete: login has dependent records.');
        }
        redirect('/admin/school-logins');
    }

    public function resetPassword(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $login = SchoolLogin::find((int)$id);
        if (!$login) { http_response_code(404); render('errors/404'); return; }

        $newPassword = SchoolLogin::generatePassword();
        SchoolLogin::setPassword((int)$id, $newPassword);

        flash_set('success', 'Password reset for ' . $login['username'] . '.');
        flash_set('info', 'New password: ' . $newPassword
            . ' — copy it now, it will not be shown again.');
        redirect('/admin/school-logins');
    }

    private function validate(array $d, ?int $exceptId, bool $requirePassword): Validator
    {
        $v = (new Validator($d))
            ->required('school_id', 'School')->integer('school_id')
            ->required('username', 'Username')->max('username', 64)
            ->unique('username', 'school_logins', 'username', $exceptId, 'school_login_id', 'Username')
            ->max('team_label', 100)
            ->in('status', self::STATUSES, 'Status');

        if ($requirePassword) {
            $v->required('password', 'Password')->min('password', 8, 'Password');
        } elseif (!empty($d['password'])) {
            $v->min('password', 8, 'Password');
        }
        return $v;
    }
}
