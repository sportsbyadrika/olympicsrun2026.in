<?php
final class AdminPanelistsController
{
    private const STATUSES = ['active', 'suspended'];

    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/panelists/index', [
            'title'     => 'Expert Panelists — Admin',
            'panelists' => Panelist::all(),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/panelists/form', [
            'title'        => 'Add Expert Panelist',
            'panelist'     => null,
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
            redirect('/admin/panelists/new');
        }

        Panelist::create($_POST);
        flash_set('success', 'Panelist created.');
        redirect('/admin/panelists');
    }

    public function edit(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $p = Panelist::find((int)$id);
        if (!$p) { http_response_code(404); render('errors/404'); return; }
        render('admin/panelists/form', [
            'title'        => 'Edit Expert Panelist',
            'panelist'     => $p,
            'associations' => Association::all(),
            'statuses'     => self::STATUSES,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $p = Panelist::find((int)$id);
        if (!$p) { http_response_code(404); render('errors/404'); return; }

        $needsPw = !empty($_POST['password']);
        $v = $this->validate($_POST, (int)$id, $needsPw);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/panelists/' . $id . '/edit');
        }

        Panelist::update((int)$id, $_POST, $needsPw ? (string)$_POST['password'] : null);
        flash_set('success', 'Panelist updated.');
        redirect('/admin/panelists');
    }

    public function destroy(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();
        try {
            Panelist::delete((int)$id);
            flash_set('success', 'Panelist deleted.');
        } catch (Throwable $e) {
            flash_set('error', 'Cannot delete: panelist has dependent records.');
        }
        redirect('/admin/panelists');
    }

    private function validate(array $d, ?int $exceptId, bool $requirePassword): Validator
    {
        $v = (new Validator($d))
            ->required('association_id', 'Association')->integer('association_id')
            ->required('full_name', 'Full name')->max('full_name', 150)
            ->required('username', 'Username')->max('username', 64)
            ->unique('username', 'expert_panelists', 'username', $exceptId, 'panelist_id', 'Username')
            ->required('email', 'Email')->email('email')->max('email', 190)
            ->unique('email', 'expert_panelists', 'email', $exceptId, 'panelist_id', 'Email')
            ->max('phone', 20)
            ->max('expertise', 150)
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
