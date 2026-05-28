<?php
final class PanelistMasterController
{
    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        $associationId = $this->associationId();

        $filters = [
            'difficulty' => $_GET['difficulty'] ?? '',
            'status'     => $_GET['status']     ?? '',
        ];
        render('panelist/master/index', [
            'title'     => 'Master Bank — Panel',
            'questions' => MasterQuestion::all($associationId, $filters),
            'filters'   => $filters,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        render('panelist/master/form', [
            'title'    => 'Add to Master Bank',
            'question' => null,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidPost();
        $associationId = $this->associationId();

        $v = $this->validate($_POST);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/panelist/master/new');
        }

        MasterQuestion::createDirect($associationId, (int)Auth::id(), $_POST);
        flash_set('success', 'Question added to master bank.');
        redirect('/panelist/master');
    }

    public function edit(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        $associationId = $this->associationId();
        $q = MasterQuestion::findScoped((int)$id, $associationId);
        if (!$q) { http_response_code(404); render('errors/404'); return; }
        render('panelist/master/form', [
            'title'    => 'Edit Master Question',
            'question' => $q,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidPost();
        $associationId = $this->associationId();

        $q = MasterQuestion::findScoped((int)$id, $associationId);
        if (!$q) { http_response_code(404); render('errors/404'); return; }

        $v = $this->validate($_POST, true);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/panelist/master/' . $id . '/edit');
        }

        MasterQuestion::update((int)$id, $_POST);
        flash_set('success', 'Master question updated.');
        redirect('/panelist/master');
    }

    public function destroy(string $id): void
    {
        Auth::requireRole(Auth::ROLE_PANELIST);
        Csrf::requireValidPost();
        $associationId = $this->associationId();

        $q = MasterQuestion::findScoped((int)$id, $associationId);
        if (!$q) { http_response_code(404); render('errors/404'); return; }

        try {
            MasterQuestion::delete((int)$id);
            flash_set('success', 'Master question deleted.');
        } catch (Throwable $e) {
            flash_set('error', 'Cannot delete: question is in use in a slot or attempt.');
        }
        redirect('/panelist/master');
    }

    private function associationId(): int
    {
        $id = Auth::associationId();
        if (!$id) { http_response_code(403); render('errors/403'); exit; }
        return $id;
    }

    private function validate(array $d, bool $forEdit = false): Validator
    {
        $v = (new Validator($d))
            ->required('question_text', 'Question')->max('question_text', 2000)
            ->required('option_a', 'Option A')->max('option_a', 500)
            ->required('option_b', 'Option B')->max('option_b', 500)
            ->required('option_c', 'Option C')->max('option_c', 500)
            ->required('option_d', 'Option D')->max('option_d', 500)
            ->required('correct_option', 'Correct answer')
                ->in('correct_option', ['A', 'B', 'C', 'D'], 'Correct answer')
            ->in('difficulty', MasterQuestion::DIFFICULTIES, 'Difficulty')
            ->max('sport', 100)
            ->max('category', 100);

        if (!empty($d['intended_round'])) {
            $v->integer('intended_round', 'Intended round');
        }
        if ($forEdit) {
            $v->in('status', MasterQuestion::STATUSES, 'Status');
        }
        return $v;
    }
}
