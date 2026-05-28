<?php
final class AdminAssociationUsersController
{
    private const STATUSES = ['active', 'suspended'];

    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/association_users/index', [
            'title' => 'Association Users — Admin',
            'users' => AssociationUser::all(),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/association_users/form', [
            'title'        => 'Add Association User',
            'user'         => null,
            'associations' => Association::active(),
            'statuses'     => self::STATUSES,
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
            redirect('/admin/association-users/new');
        }

        AssociationUser::create($_POST);
        flash_set('success', 'Association user created.');
        redirect('/admin/association-users');
    }

    public function edit(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $user = AssociationUser::find((int)$id);
        if (!$user) { http_response_code(404); render('errors/404'); return; }
        render('admin/association_users/form', [
            'title'        => 'Edit Association User',
            'user'         => $user,
            'associations' => Association::all(),
            'statuses'     => self::STATUSES,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $user = AssociationUser::find((int)$id);
        if (!$user) { http_response_code(404); render('errors/404'); return; }

        $needsPassword = !empty($_POST['password']);
        $v = $this->validate($_POST, (int)$id, $needsPassword);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/association-users/' . $id . '/edit');
        }

        AssociationUser::update((int)$id, $_POST, $needsPassword ? (string)$_POST['password'] : null);
        flash_set('success', 'Association user updated.');
        redirect('/admin/association-users');
    }

    public function destroy(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();
        try {
            AssociationUser::delete((int)$id);
            flash_set('success', 'Association user deleted.');
        } catch (Throwable $e) {
            flash_set('error', 'Cannot delete: user has dependent records.');
        }
        redirect('/admin/association-users');
    }

    private function validate(array $d, ?int $exceptId, bool $requirePassword): Validator
    {
        $v = (new Validator($d))
            ->required('association_id', 'Association')->integer('association_id')
            ->required('full_name', 'Full name')->max('full_name', 150)
            ->required('username', 'Username')->max('username', 64)
            ->unique('username', 'association_users', 'username', $exceptId, 'association_user_id', 'Username')
            ->required('email', 'Email')->email('email')->max('email', 190)
            ->unique('email', 'association_users', 'email', $exceptId, 'association_user_id', 'Email')
            ->max('phone', 20)
            ->max('role_label', 60)
            ->in('status', self::STATUSES, 'Status');

        if ($requirePassword) {
            $v->required('password', 'Password')->min('password', 8, 'Password')
              ->matches('password', 'password_confirmation', 'Password confirmation');
        } elseif (!empty($d['password'])) {
            $v->min('password', 8, 'Password')
              ->matches('password', 'password_confirmation', 'Password confirmation');
        }
        return $v;
    }
}
