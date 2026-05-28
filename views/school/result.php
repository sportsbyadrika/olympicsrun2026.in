<?php /** @var array $results */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">My Results</h1>
    <a href="/school/dashboard" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Dashboard
    </a>
</div>

<?php if (empty($results)): ?>
    <div class="card panel border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox display-5"></i>
            <p class="mb-0 mt-2">No submitted attempts yet.</p>
        </div>
    </div>
<?php else: foreach ($results as $r):
    $published = !empty($r['published']);
    $total     = (int)($r['total_questions'] ?? 0);
    $correct   = (int)($r['correct_count']   ?? 0);
    $wrong     = (int)($r['wrong_count']     ?? 0);
    $skipped   = (int)($r['unanswered_count'] ?? 0);
    $score     = $r['total_score'] !== null ? (float)$r['total_score'] : null;
    $rank      = $r['rank_in_round'];
    $timeTaken = (int)($r['attempt_time'] ?? 0);
    $mm        = floor($timeTaken / 60);
    $ss        = $timeTaken % 60;
?>
    <div class="card panel border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-md-flex justify-content-between align-items-start gap-3">
                <div class="flex-grow-1">
                    <h2 class="h5 text-navy mb-1">
                        Round <?= (int)$r['round_number'] ?> — <?= e($r['round_name']) ?>
                    </h2>
                    <p class="text-muted small mb-2">
                        Slot: <?= e($r['slot_label'] ?? '#' . (int)$r['slot_id']) ?> ·
                        Submitted <?= e(dt_display($r['submitted_at'])) ?> ·
                        Time taken <?= sprintf('%d:%02d', $mm, $ss) ?>
                    </p>
                    <?php if (!$published): ?>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                            <i class="bi bi-clock me-1"></i> Submitted — results awaiting publication
                        </span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">
                            <i class="bi bi-check2-circle me-1"></i> Results published
                        </span>
                        <?php if (!empty($r['qualified_next_round'])): ?>
                            <span class="badge bg-accent text-white ms-1">
                                <i class="bi bi-trophy me-1"></i> Qualified for next round
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-2 mt-3">
                <div class="col-6 col-md-3">
                    <div class="result-tile">
                        <div class="small text-muted">Score</div>
                        <div class="h4 text-navy mb-0">
                            <?= $published && $score !== null ? rtrim(rtrim(number_format($score, 2), '0'), '.') : '—' ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="result-tile">
                        <div class="small text-muted">Rank</div>
                        <div class="h4 text-navy mb-0">
                            <?= $published && $rank !== null ? '#' . (int)$rank : '—' ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="result-tile">
                        <div class="small text-muted">Correct / Total</div>
                        <div class="h4 text-accent mb-0">
                            <?= $published ? ($correct . ' / ' . $total) : ('— / ' . $total) ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="result-tile">
                        <div class="small text-muted">Skipped</div>
                        <div class="h4 text-navy mb-0">
                            <?= $published ? $skipped : '—' ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$published): ?>
                <p class="text-muted small mb-0 mt-3">
                    Your answers are locked. Scores will appear here once your
                    association publishes results for this round.
                </p>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; endif; ?>

<style>
.result-tile { background: #fff; border: 1px solid var(--or-panel-dark);
               border-radius: 0.5rem; padding: 0.75rem; height: 100%; }
.bg-accent   { background-color: var(--or-teal) !important; }
</style>
