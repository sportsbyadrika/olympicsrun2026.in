<?php
final class AssociationQuestionsController
{
    public function index(): void
    {
        Auth::requireRole(Auth::ROLE_ASSOCIATION);
        $associationId = $this->associationId();

        $filter = $_GET['status'] ?? 'all';
        if (!in_array($filter, array_merge(['all'], AssociationQuestion::STATUSES), true)) {
            $filter = 'all';
        }

        render('association/questions/index', [
            'title'     => 'My Question Bank — Association',
            'questions' => AssociationQuestion::forAssociation($associationId, $filter === 'all' ? null : $filter),
            'counts'    => AssociationQuestion::countsByStatus($associationId),
            'filter'    => $filter,
        ]);
    }

    public function create(): void
    {
        Auth::requireRole(Auth::ROLE_ASSOCIATION);
        render('association/questions/form', [
            'title'    => 'New Question',
            'question' => null,
        ]);
    }

    public function store(): void
    {
        Auth::requireRole(Auth::ROLE_ASSOCIATION);
        Csrf::requireValidPost();

        $associationId = $this->associationId();

        $v = $this->validate($_POST);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/association/questions/new');
        }

        $id = AssociationQuestion::create($associationId, (int)Auth::id(), $_POST);

        // "Save and submit" submits in the same request
        if (!empty($_POST['_submit_now'])) {
            AssociationQuestion::submitToPanel([$id], $associationId);
            flash_set('success', 'Question saved and submitted to the expert panel.');
        } else {
            flash_set('success', 'Question saved as draft.');
        }
        redirect('/association/questions');
    }

    public function edit(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ASSOCIATION);
        $associationId = $this->associationId();
        $q = AssociationQuestion::findScoped((int)$id, $associationId);
        if (!$q) { http_response_code(404); render('errors/404'); return; }

        if (!AssociationQuestion::isEditable($q['status'])) {
            flash_set('error', 'This question is in review and cannot be edited.');
            redirect('/association/questions');
        }

        render('association/questions/form', [
            'title'    => 'Edit Question',
            'question' => $q,
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ASSOCIATION);
        Csrf::requireValidPost();

        $associationId = $this->associationId();
        $q = AssociationQuestion::findScoped((int)$id, $associationId);
        if (!$q) { http_response_code(404); render('errors/404'); return; }

        if (!AssociationQuestion::isEditable($q['status'])) {
            flash_set('error', 'This question is in review and cannot be edited.');
            redirect('/association/questions');
        }

        $v = $this->validate($_POST);
        if ($v->fails()) {
            flash_errors($v->errors());
            flash_old($_POST);
            redirect('/association/questions/' . $id . '/edit');
        }

        $resubmit = !empty($_POST['_submit_now']);
        AssociationQuestion::update((int)$id, $associationId, $_POST, $resubmit);

        if ($resubmit && $q['status'] === 'needs_revision') {
            flash_set('success', 'Revision saved and re-submitted to the expert panel.');
        } elseif ($q['status'] === 'draft' && $resubmit) {
            // Draft + submit: do the actual transition
            AssociationQuestion::submitToPanel([(int)$id], $associationId);
            flash_set('success', 'Question updated and submitted to the expert panel.');
        } else {
            flash_set('success', 'Question updated.');
        }
        redirect('/association/questions');
    }

    public function destroy(string $id): void
    {
        Auth::requireRole(Auth::ROLE_ASSOCIATION);
        Csrf::requireValidPost();

        $associationId = $this->associationId();
        $q = AssociationQuestion::findScoped((int)$id, $associationId);
        if (!$q) { http_response_code(404); render('errors/404'); return; }

        if (!AssociationQuestion::isEditable($q['status'])) {
            flash_set('error', 'Cannot delete: question is in review.');
            redirect('/association/questions');
        }

        AssociationQuestion::delete((int)$id, $associationId);
        flash_set('success', 'Question deleted.');
        redirect('/association/questions');
    }

    public function submitBulk(): void
    {
        Auth::requireRole(Auth::ROLE_ASSOCIATION);
        Csrf::requireValidPost();

        $associationId = $this->associationId();
        $ids = $_POST['question_ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            flash_set('error', 'Select at least one question to submit.');
            redirect('/association/questions');
        }

        $n = AssociationQuestion::submitToPanel($ids, $associationId);
        if ($n === 0) {
            flash_set('warning', 'Nothing was submitted — selected questions are not in draft or needs-revision state.');
        } else {
            flash_set('success', $n . ' question' . ($n === 1 ? '' : 's')
                . ' submitted to the expert panel.');
        }
        redirect('/association/questions');
    }

    /* ----- Helpers ----- */

    private function associationId(): int
    {
        $id = Auth::associationId();
        if (!$id) {
            http_response_code(403);
            render('errors/403');
            exit;
        }
        return $id;
    }

    private function validate(array $d): Validator
    {
        return (new Validator($d))
            ->required('question_text', 'Question')->max('question_text', 2000)
            ->required('option_a', 'Option A')->max('option_a', 500)
            ->required('option_b', 'Option B')->max('option_b', 500)
            ->required('option_c', 'Option C')->max('option_c', 500)
            ->required('option_d', 'Option D')->max('option_d', 500)
            ->required('correct_option', 'Correct answer')
                ->in('correct_option', ['A', 'B', 'C', 'D'], 'Correct answer')
            ->in('difficulty', AssociationQuestion::DIFFICULTIES, 'Difficulty')
            ->max('sport', 100)
            ->max('category', 100)
            ->max('reference_source', 255);
    }
}
