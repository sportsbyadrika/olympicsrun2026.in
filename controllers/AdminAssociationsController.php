<?php
final class AdminAssociationsController
{
    private const STATUSES = ['active', 'inactive'];

    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/associations/index', [
            'title'        => 'Associations — Admin',
            'associations' => Association::all(),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/associations/form', [
            'title'       => 'Add Association',
            'association' => null,
            'statuses'    => self::STATUSES,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $v = $this->validate($_POST, null);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/associations/new');
        }

        $id = Association::create($_POST, Auth::id());
        flash_set('success', 'Association created.');
        redirect('/admin/associations/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $assoc = Association::find((int)$id);
        if (!$assoc) { http_response_code(404); render('errors/404'); return; }
        render('admin/associations/form', [
            'title'       => 'Edit Association',
            'association' => $assoc,
            'statuses'    => self::STATUSES,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $assoc = Association::find((int)$id);
        if (!$assoc) { http_response_code(404); render('errors/404'); return; }

        $v = $this->validate($_POST, (int)$id);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/associations/' . $id . '/edit');
        }

        Association::update((int)$id, $_POST);
        flash_set('success', 'Association updated.');
        redirect('/admin/associations');
    }

    public function destroy(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();
        try {
            Association::delete((int)$id);
            flash_set('success', 'Association deleted.');
        } catch (Throwable $e) {
            flash_set('error', 'Cannot delete: this association has dependent records.');
        }
        redirect('/admin/associations');
    }

    private function validate(array $d, ?int $exceptId): Validator
    {
        return (new Validator($d))
            ->required('name', 'Name')->max('name', 200)
            ->required('short_code', 'Short code')->max('short_code', 20)
            ->unique('short_code', 'associations', 'short_code', $exceptId, 'association_id', 'Short code')
            ->max('region', 100)
            ->email('contact_email', 'Contact email')
            ->max('contact_phone', 20)
            ->in('status', self::STATUSES, 'Status');
    }
}
