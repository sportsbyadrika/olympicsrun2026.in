<?php
final class SchoolQuizController
{
    /* ---------------- Start ---------------- */

    public function start(): void
    {
        Auth::requireRole(Auth::ROLE_SCHOOL);
        Csrf::requireValidPost();

        $schoolId = (int)Auth::schoolId();
        QuizAttempt::forceSubmitExpired();

        $attempt = QuizAttempt::currentAttemptForSchool($schoolId);
        if (!$attempt) {
            flash_set('error', 'You have no slot assigned, or it has already passed.');
            redirect('/school/dashboard');
        }

        if ($attempt['attempt_status'] === 'in_progress') {
            redirect('/school/quiz');
        }

        if (!QuizAttempt::isWithinSlotWindow($attempt)) {
            flash_set('error', 'Your slot is not open yet, or its window has closed.');
            redirect('/school/dashboard');
        }

        QuizAttempt::start((int)$attempt['slot_school_id']);
        redirect('/school/quiz');
    }

    /* ---------------- Quiz page ---------------- */

    public function show(): void
    {
        Auth::requireRole(Auth::ROLE_SCHOOL);
        $schoolId = (int)Auth::schoolId();
        QuizAttempt::forceSubmitExpired();

        $attempt = QuizAttempt::currentAttemptForSchool($schoolId);
        if (!$attempt) {
            // Maybe already submitted — send straight to the result page.
            if ($this->hasAnySubmittedAttempt($schoolId)) {
                redirect('/school/result');
            }
            flash_set('error', 'No active quiz attempt found.');
            redirect('/school/dashboard');
        }
        if ($attempt['attempt_status'] !== 'in_progress' || empty($attempt['started_at'])) {
            redirect('/school/dashboard');
        }

        $remaining = QuizAttempt::remainingSeconds($attempt);
        if ($remaining <= 0) {
            // Server truth says time is up — submit now, then send to result.
            QuizAttempt::submit((int)$attempt['slot_school_id']);
            redirect('/school/result');
        }

        $questions = QuizAttempt::questionsForSlot((int)$attempt['slot_id']);
        $responses = QuizAttempt::responsesByQuestion((int)$attempt['slot_school_id']);

        render('school/quiz', [
            'title'     => 'Quiz — ' . ($attempt['slot_label'] ?? 'Round ' . $attempt['round_number']),
            'attempt'   => $attempt,
            'questions' => $questions,
            'responses' => $responses,
            'remaining' => $remaining,
        ]);
    }

    /* ---------------- AJAX: state ---------------- */

    public function apiState(): void
    {
        Auth::requireRole(Auth::ROLE_SCHOOL);

        $schoolId = (int)Auth::schoolId();
        $attempt  = QuizAttempt::currentAttemptForSchool($schoolId);

        if (!$attempt) {
            $redirect = $this->hasAnySubmittedAttempt($schoolId) ? '/school/result' : '/school/dashboard';
            $this->json([
                'ok'                => true,
                'attempt_status'    => 'none',
                'remaining_seconds' => 0,
                'server_now'        => time(),
                'redirect'          => $redirect,
            ]);
        }

        $remaining = QuizAttempt::remainingSeconds($attempt);

        // The defining feature: timer hits zero → server force-submits.
        if ($remaining <= 0 && $attempt['attempt_status'] === 'in_progress') {
            QuizAttempt::submit((int)$attempt['slot_school_id']);
            $this->json([
                'ok'                => true,
                'attempt_status'    => 'submitted',
                'remaining_seconds' => 0,
                'server_now'        => time(),
                'redirect'          => '/school/result',
            ]);
        }

        $this->json([
            'ok'                => true,
            'attempt_status'    => $attempt['attempt_status'],
            'remaining_seconds' => $remaining,
            'server_now'        => time(),
        ]);
    }

    /* ---------------- AJAX: answer ---------------- */

    public function apiAnswer(): void
    {
        Auth::requireRole(Auth::ROLE_SCHOOL);
        Csrf::requireValidRequest();

        $schoolId = (int)Auth::schoolId();
        $attempt  = QuizAttempt::currentAttemptForSchool($schoolId);

        if (!$attempt || $attempt['attempt_status'] !== 'in_progress') {
            $this->json([
                'ok'       => false,
                'error'    => 'attempt_not_active',
                'redirect' => '/school/result',
            ], 403);
        }

        // Force-submit if the timer is already up at the moment of this call.
        if (QuizAttempt::remainingSeconds($attempt) <= 0) {
            QuizAttempt::submit((int)$attempt['slot_school_id']);
            $this->json([
                'ok'       => false,
                'error'    => 'time_up',
                'redirect' => '/school/result',
            ], 403);
        }

        $slotQuestionId = (int)($_POST['slot_question_id'] ?? 0);
        $chosen         = $_POST['chosen_option'] ?? null;
        if ($chosen === '' || $chosen === '_clear') $chosen = null;

        try {
            QuizAttempt::saveAnswer(
                (int)$attempt['slot_school_id'],
                $slotQuestionId,
                $chosen
            );
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'error' => 'save_failed: ' . $e->getMessage()], 422);
        }

        $this->json([
            'ok'                => true,
            'slot_question_id'  => $slotQuestionId,
            'chosen_option'     => $chosen,
            'remaining_seconds' => QuizAttempt::remainingSeconds($attempt),
        ]);
    }

    /* ---------------- AJAX: submit ---------------- */

    public function apiSubmit(): void
    {
        Auth::requireRole(Auth::ROLE_SCHOOL);
        Csrf::requireValidRequest();

        $schoolId = (int)Auth::schoolId();
        $attempt  = QuizAttempt::currentAttemptForSchool($schoolId);

        if (!$attempt) {
            // Either never started or already submitted — either way the
            // result page is where they need to go next.
            $this->json([
                'ok'       => true,
                'redirect' => $this->hasAnySubmittedAttempt($schoolId)
                                ? '/school/result'
                                : '/school/dashboard',
            ]);
        }

        if ($attempt['attempt_status'] === 'submitted') {
            $this->json(['ok' => true, 'redirect' => '/school/result']);
        }
        if ($attempt['attempt_status'] !== 'in_progress') {
            $this->json(['ok' => false, 'error' => 'not_in_progress'], 403);
        }

        try {
            QuizAttempt::submit((int)$attempt['slot_school_id']);
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'error' => 'submit_failed: ' . $e->getMessage()], 500);
        }

        $this->json(['ok' => true, 'redirect' => '/school/result']);
    }

    /* ---------------- Result page ---------------- */

    public function result(): void
    {
        Auth::requireRole(Auth::ROLE_SCHOOL);
        $schoolId = (int)Auth::schoolId();
        QuizAttempt::forceSubmitExpired();

        render('school/result', [
            'title'    => 'My Results — Olympics Run 2026',
            'results'  => QuizAttempt::submittedResultsForSchool($schoolId),
        ]);
    }

    /* ---------------- helpers ---------------- */

    private function hasAnySubmittedAttempt(int $schoolId): bool
    {
        return (bool)Database::fetch(
            'SELECT slot_school_id FROM slot_schools
              WHERE school_id = ? AND attempt_status = "submitted"
              LIMIT 1',
            [$schoolId]
        );
    }

    private function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
