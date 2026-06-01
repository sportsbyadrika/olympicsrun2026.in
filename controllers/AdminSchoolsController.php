<?php
final class AdminSchoolsController
{
    private const STATUSES = ['pending', 'approved', 'rejected', 'suspended'];

    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $nameFilter = trim((string)($_GET['q'] ?? ''));
        render('admin/schools/index', [
            'title'      => 'Schools — Admin',
            'schools'    => School::all(null, $nameFilter !== '' ? $nameFilter : null),
            'nameFilter' => $nameFilter,
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
            'schoolTypes'  => Lookup::schoolTypes(),
            'syllabuses'   => Lookup::syllabuses(),
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
            'schoolTypes'  => Lookup::schoolTypes(),
            'syllabuses'   => Lookup::syllabuses(),
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

    // ---------------------------------------------------------------------
    // Combined "manage school + its team logins" page (modal-driven)
    // ---------------------------------------------------------------------

    public function show(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $school = School::find((int)$id);
        if (!$school) { http_response_code(404); render('errors/404'); return; }

        $logins = SchoolLogin::all((int)$id);
        $participants = [];
        foreach ($logins as $l) {
            $participants[(int)$l['school_login_id']] =
                TeamParticipant::mapForLogin((int)$l['school_login_id']);
        }

        render('admin/schools/show', [
            'title'        => $school['school_name'] . ' — Manage',
            'school'       => $school,
            'logins'       => $logins,
            'participants' => $participants,
            'maxTeams'     => Settings::int('max_teams_per_school', 1),
            'defaultTeam'  => $this->defaultTeamLabel($school, count($logins)),
        ]);
    }

    /** Serve a participant photo (auth-gated; files live outside web root). */
    public function participantPhoto(string $participantId): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $p = TeamParticipant::find((int)$participantId);
        if (!$p || empty($p['photo_path'])) { http_response_code(404); echo 'Not found'; return; }

        $path = TeamParticipant::photoDir() . '/' . basename((string)$p['photo_path']);
        if (!is_file($path)) { http_response_code(404); echo 'Not found'; return; }

        header('Content-Type: image/jpeg');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=300');
        readfile($path);
    }

    /** Default team label: "{school_code}-Team {n}". */
    private function defaultTeamLabel(array $school, int $existingCount): string
    {
        $code = trim((string)($school['school_code'] ?? ''));
        $code = $code !== '' ? $code : 'TEAM';
        return $code . '-Team ' . ($existingCount + 1);
    }

    public function storeLogin(string $schoolId): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $sid = (int)$schoolId;
        $school = School::find($sid);
        if (!$school) { http_response_code(404); render('errors/404'); return; }

        $max = Settings::int('max_teams_per_school', 1);
        if (SchoolLogin::countForSchool($sid) >= $max) {
            flash_set('error', "Team limit reached ({$max}). Increase it in Settings to add more.");
            redirect('/admin/schools/' . $sid);
        }

        $v = $this->validateLogin($_POST, null, true);
        if ($v->fails()) {
            flash_set('error', implode(' ', $v->errors()));
            redirect('/admin/schools/' . $sid);
        }

        $loginId = SchoolLogin::create(['school_id' => $sid] + $_POST);
        $this->saveParticipants($loginId);
        flash_set('success', 'Team created.');
        redirect('/admin/schools/' . $sid);
    }

    public function updateLogin(string $schoolId, string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $sid   = (int)$schoolId;
        $login = SchoolLogin::find((int)$id);
        if (!$login || (int)$login['school_id'] !== $sid) {
            http_response_code(404); render('errors/404'); return;
        }

        $needsPw = !empty($_POST['password']);
        $v = $this->validateLogin($_POST, (int)$id, false);
        if ($v->fails()) {
            flash_set('error', implode(' ', $v->errors()));
            redirect('/admin/schools/' . $sid);
        }

        // SchoolLogin::update() needs school_id in the data array.
        SchoolLogin::update(
            (int)$id,
            ['school_id' => $sid] + $_POST,
            $needsPw ? (string)$_POST['password'] : null
        );
        $this->saveParticipants((int)$id);
        flash_set('success', 'Team updated.');
        redirect('/admin/schools/' . $sid);
    }

    /**
     * Persist the two participant rows from the submitted form. Expects
     * $_POST['participants'][1|2] = ['name','standard','age','gender'] and
     * $_POST['participant_photo'][1|2] = base64 data-URL (optional).
     */
    private function saveParticipants(int $loginId): void
    {
        $parts  = $_POST['participants'] ?? [];
        $photos = $_POST['participant_photo'] ?? [];
        if (!is_array($parts)) return;

        foreach ([1, 2] as $slot) {
            $data  = is_array($parts[$slot] ?? null) ? $parts[$slot] : [];
            $photo = is_string($photos[$slot] ?? null) ? $photos[$slot] : null;
            TeamParticipant::save($loginId, $slot, $data, $photo);
        }
    }

    /** Reset the password and email it to the school's contact address. */
    public function resetLogin(string $schoolId, string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $sid   = (int)$schoolId;
        $login = SchoolLogin::find((int)$id);
        if (!$login || (int)$login['school_id'] !== $sid) {
            http_response_code(404); render('errors/404'); return;
        }

        // SchoolMail::sendCredentials() generates a new password, emails it,
        // and only persists the new hash if the email actually went out.
        $res = SchoolMail::sendCredentials((int)$id);
        if ($res['ok']) {
            flash_set('success', 'Password reset and emailed to the school.');
        } else {
            flash_set('error', 'Could not send: ' . ($res['error'] ?? 'unknown error')
                . ' (password unchanged).');
        }
        redirect('/admin/schools/' . $sid);
    }

    public function destroyLogin(string $schoolId, string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $sid   = (int)$schoolId;
        $login = SchoolLogin::find((int)$id);
        if (!$login || (int)$login['school_id'] !== $sid) {
            http_response_code(404); render('errors/404'); return;
        }

        try {
            SchoolLogin::delete((int)$id);
            flash_set('success', 'Team login deleted.');
        } catch (Throwable $e) {
            flash_set('error', 'Cannot delete: this login has dependent records.');
        }
        redirect('/admin/schools/' . $sid);
    }

    private function validateLogin(array $d, ?int $exceptId, bool $requirePassword): Validator
    {
        $v = (new Validator($d))
            ->required('username', 'Username')->max('username', 64)
            ->unique('username', 'school_logins', 'username', $exceptId, 'school_login_id', 'Username')
            ->max('team_label', 100)
            ->in('status', ['active', 'suspended'], 'Status');

        if ($requirePassword) {
            $v->required('password', 'Password')->min('password', 8, 'Password');
        } elseif (!empty($d['password'])) {
            $v->min('password', 8, 'Password');
        }
        return $v;
    }

    private function validate(array $d, ?int $exceptId): Validator
    {
        return (new Validator($d))
            ->required('association_id', 'Association')->integer('association_id')
            ->required('school_name', 'School name')->max('school_name', 200)
            ->max('school_code', 50)
            ->unique('school_code', 'schools', 'school_code', $exceptId, 'school_id', 'School code')
            ->integer('school_type_id', 'School type')
            ->integer('syllabus_id', 'Syllabus')
            ->max('region', 100)
            ->max('principal_name', 150)
            ->max('coach_name', 150)
            ->email('contact_email', 'Contact email')
            ->max('contact_phone', 20)
            ->in('status', self::STATUSES, 'Status');
    }
}
