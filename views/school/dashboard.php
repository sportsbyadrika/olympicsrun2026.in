<?php
/** @var array $attempts */
/** @var int   $now */

// Group by round_number for tidy display.
$byRound = [];
foreach ($attempts as $a) {
    $byRound[(int)$a['round_number']] = $a;
}

$slotStateLabel = static function (array $a, int $now): array {
    $start = strtotime($a['slot_starts']);
    $end   = strtotime($a['slot_ends']);
    if ($a['attempt_status'] === 'submitted')     return ['Submitted', 'submitted'];
    if ($a['attempt_status'] === 'in_progress')   return ['In progress', 'in_progress'];
    if ($a['attempt_status'] === 'no_show')       return ['Missed', 'no_show'];
    if ($a['attempt_status'] === 'disqualified')  return ['Disqualified', 'disqualified'];
    if ($now < $start) return ['Opens ' . date('d M, H:i', $start), 'scheduled'];
    if ($now > $end)   return ['Window closed', 'closed'];
    return ['Open now', 'open'];
};
?>
<div class="d-md-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 text-navy mb-1">School Dashboard</h1>
        <p class="text-muted mb-0">Welcome, <?= e(Auth::user()['name']) ?>.</p>
    </div>
</div>

<?php if (empty($attempts)): ?>
    <div class="card panel border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-calendar2-x display-5"></i>
            <p class="mt-2 mb-0">No slot has been assigned to your team yet.</p>
            <p class="small">Once your association assigns you a slot, you'll see the start button here.</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($byRound as $a):
        [$stateLabel, $stateKey] = $slotStateLabel($a, $now);
        $canStart = in_array($a['attempt_status'], ['assigned'], true)
                    && $now >= strtotime($a['slot_starts'])
                    && $now <= strtotime($a['slot_ends']);
        $canResume = $a['attempt_status'] === 'in_progress';
    ?>
        <div class="card panel border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-md-flex justify-content-between align-items-start gap-3">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h2 class="h5 text-navy mb-0">
                                Round <?= (int)$a['round_number'] ?> — <?= e($a['round_name']) ?>
                            </h2>
                            <span class="<?= status_badge($stateKey) ?>"><?= e($stateLabel) ?></span>
                        </div>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-clock me-1"></i>
                            <?= e(dt_display($a['slot_starts'], 'd M, h:i A')) ?>
                            – <?= e(dt_display($a['slot_ends'], 'h:i A')) ?>
                            · <?= (int)$a['quiz_duration_minutes'] ?> min quiz
                            · <?= (int)$a['questions_per_quiz'] ?> questions
                        </p>
                        <p class="small text-muted mb-0">
                            Slot: <?= e($a['slot_label'] ?? '#' . (int)$a['slot_id']) ?>
                        </p>
                    </div>

                    <div class="mt-3 mt-md-0 text-md-end">
                        <?php if ($canResume): ?>
                            <a href="/school/quiz" class="btn btn-accent text-white">
                                <i class="bi bi-play-fill me-1"></i> Resume Quiz
                            </a>
                        <?php elseif ($canStart): ?>
                            <form method="post" action="/school/quiz/start" class="d-inline"
                                  onsubmit="return confirm('Start the quiz now? Your <?= (int)$a['quiz_duration_minutes'] ?>-minute timer begins immediately.');">
                                <?= csrf_field() ?>
                                <button class="btn btn-primary">
                                    <i class="bi bi-play-fill me-1"></i> Start Quiz
                                </button>
                            </form>
                        <?php elseif ($a['attempt_status'] === 'submitted'): ?>
                            <a href="/school/result" class="btn btn-outline-navy">
                                <i class="bi bi-check2-circle me-1"></i> View result
                            </a>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary" disabled>
                                <i class="bi bi-clock me-1"></i>
                                <?= $now < strtotime($a['slot_starts']) ? 'Not yet open' : 'Window closed' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="alert alert-info mb-0 small">
    <i class="bi bi-info-circle me-1"></i>
    Your timer runs on the server. If your browser disconnects, log back in
    within the slot window to resume — your answers are saved as you go.
</div>
