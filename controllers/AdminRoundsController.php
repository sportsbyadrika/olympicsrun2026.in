<?php
final class AdminRoundsController
{
    private const STATUSES = ['draft', 'open', 'closed', 'published'];

    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/rounds/index', [
            'title'  => 'Rounds — Admin',
            'rounds' => Round::all(),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        render('admin/rounds/form', [
            'title'        => 'Add Round',
            'round'        => null,
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
            redirect('/admin/rounds/new');
        }

        Round::create($_POST);
        flash_set('success', 'Round created.');
        redirect('/admin/rounds');
    }

    public function edit(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        $r = Round::find((int)$id);
        if (!$r) { http_response_code(404); render('errors/404'); return; }
        render('admin/rounds/form', [
            'title'        => 'Edit Round',
            'round'        => $r,
            'associations' => Association::all(),
            'statuses'     => self::STATUSES,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();

        $r = Round::find((int)$id);
        if (!$r) { http_response_code(404); render('errors/404'); return; }

        $v = $this->validate($_POST, (int)$id);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/admin/rounds/' . $id . '/edit');
        }

        Round::update((int)$id, $_POST);
        flash_set('success', 'Round updated.');
        redirect('/admin/rounds');
    }

    public function destroy(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ADMIN);
        Csrf::requireValidPost();
        try {
            Round::delete((int)$id);
            flash_set('success', 'Round deleted.');
        } catch (Throwable $e) {
            flash_set('error', 'Cannot delete: round has slots or results attached.');
        }
        redirect('/admin/rounds');
    }

    private function validate(array $d, ?int $exceptId): Validator
    {
        $v = (new Validator($d))
            ->required('association_id', 'Association')->integer('association_id')
            ->required('round_number', 'Round number')->integer('round_number')
            ->required('name', 'Name')->max('name', 100)
            ->required('slot_duration_minutes', 'Slot duration')->integer('slot_duration_minutes')
            ->required('quiz_duration_minutes', 'Quiz duration')->integer('quiz_duration_minutes')
            ->required('questions_per_quiz', 'Questions per quiz')->integer('questions_per_quiz')
            ->in('status', self::STATUSES, 'Status');

        // Round-number uniqueness inside an association
        if (!empty($d['association_id']) && !empty($d['round_number'])
            && preg_match('/^\d+$/', (string)$d['round_number'])) {
            if (Round::roundNumberTaken((int)$d['association_id'], (int)$d['round_number'], $exceptId)) {
                $v->addError('round_number', 'Round number already exists for this association.');
            }
        }

        // Sane numeric ranges
        if (isset($d['quiz_duration_minutes'], $d['slot_duration_minutes'])
            && preg_match('/^\d+$/', (string)$d['quiz_duration_minutes'])
            && preg_match('/^\d+$/', (string)$d['slot_duration_minutes'])
            && (int)$d['quiz_duration_minutes'] > (int)$d['slot_duration_minutes']) {
            $v->addError('quiz_duration_minutes', 'Quiz duration cannot exceed slot duration.');
        }

        if (!empty($d['starts_at'])) $v->datetime('starts_at', 'Starts at');
        if (!empty($d['ends_at']))   $v->datetime('ends_at', 'Ends at');

        return $v;
    }
}
