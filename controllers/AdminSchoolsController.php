<?php
final class AdminSchoolsController
{
    private const STATUSES = ['pending', 'approved', 'rejected', 'suspended'];

    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/schools/index', [
            'title'   => 'Schools — Admin',
            'schools' => School::all(),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/schools/form', [
            'title'        => 'Add School',
            'school'       => null,
            'associations' => Association::active(),
            'statuses'     => self::STATUSES,
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
            redirect('/admin/schools/new');
        }

        $id = School::create($_POST);
        flash_set('success', 'School created.');
        redirect('/admin/schools/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $school = School::find((int)$id);
        if (!$school) { http_response_code(404); render('errors/404'); return; }
        render('admin/schools/form', [
            'title'        => 'Edit School',
            'school'       => $school,
            'associations' => Association::all(),
            'statuses'     => self::STATUSES,
            'logins'       => SchoolLogin::all((int)$id),
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $school = School::find((int)$id);
        if (!$school) { http_response_code(404); render('errors/404'); return; }

        $v = $this->validate($_POST, (int)$id);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/schools/' . $id . '/edit');
        }

        School::update((int)$id, $_POST);
        flash_set('success', 'School updated.');
        redirect('/admin/schools');
    }

    public function destroy(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();
        try {
            School::delete((int)$id);
            flash_set('success', 'School deleted.');
        } catch (Throwable $e) {
            flash_set('error', 'Cannot delete: this school has dependent records.');
        }
        redirect('/admin/schools');
    }

    private function validate(array $d, ?int $exceptId): Validator
    {
        return (new Validator($d))
            ->required('association_id', 'Association')->integer('association_id')
            ->required('school_name', 'School name')->max('school_name', 200)
            ->max('school_code', 50)
            ->unique('school_code', 'schools', 'school_code', $exceptId, 'school_id', 'School code')
            ->max('region', 100)
            ->max('principal_name', 150)
            ->max('coach_name', 150)
            ->email('contact_email', 'Contact email')
            ->max('contact_phone', 20)
            ->in('status', self::STATUSES, 'Status');
    }
}
