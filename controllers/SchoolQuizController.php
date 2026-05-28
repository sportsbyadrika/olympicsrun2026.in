<?php
final class SchoolQuizController
{
    /* ---------------- Start ---------------- */

    public function start(): void
    {
        Auth::requireRole(Auth::ROLE_SCHOOL);
        Csrf::requireValidPost();

        $schoolId = (int)Auth::schoolId();
        $attempt = QuizAttempt::currentAttemptForSchool($schoolId);

        if (!$attempt) {
            flash_set('error', 'You have no slot assigned, or it has already passed.');
            redirect('/school/dashboard');
        }

        // Already in progress → just resume.
        if ($attempt['attempt_status'] === 'in_progress') {
            redirect('/school/quiz');
        }

        // Server-side window check.
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
        $attempt  = QuizAttempt::currentAttemptForSchool($schoolId);

        if (!$attempt) {
            flash_set('error', 'No active quiz attempt found.');
            redirect('/school/dashboard');
        }
        if ($attempt['attempt_status'] !== 'in_progress' || empty($attempt['started_at'])) {
            // Either not started yet or already submitted — go back to dashboard.
            redirect('/school/dashboard');
        }

        $remaining = QuizAttempt::remainingSeconds($attempt);
        if ($remaining <= 0) {
            // Timer already expired by the time we got here. (Submission /
            // force-submit lands in the next phase — for now we lock the
            // session by keeping attempt_status as-is and showing time-up.)
            $remaining = 0;
        }

        $questions = QuizAttempt::questionsForSlot((int)$attempt['slot_id']);
        $responses = QuizAttempt::responsesByQuestion((int)$attempt['slot_school_id']);

        render('school/quiz', [
            'title'     => 'Quiz — ' . ($attempt['slot_label'] ?? 'Round ' . $attempt['round_number']),
            'layout'    => true,
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
        // GET is read-only — no CSRF.

        $schoolId = (int)Auth::schoolId();
        $attempt  = QuizAttempt::currentAttemptForSchool($schoolId);

        if (!$attempt) {
            $this->json([
                'ok'                => true,
                'attempt_status'    => 'none',
                'remaining_seconds' => 0,
                'server_now'        => time(),
            ]);
        }

        $remaining = QuizAttempt::remainingSeconds($attempt);
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

        if (!$attempt) {
            $this->json(['ok' => false, 'error' => 'no_attempt'], 403);
        }
        if ($attempt['attempt_status'] !== 'in_progress') {
            $this->json(['ok' => false, 'error' => 'attempt_not_active'], 403);
        }
        if (QuizAttempt::remainingSeconds($attempt) <= 0) {
            $this->json(['ok' => false, 'error' => 'time_up'], 403);
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

    /* ---------------- helpers ---------------- */

    private function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
