<?php
/** @var array $round */
/** @var array $results */
$published = array_filter($results, static fn($r) => (int)$r['published'] === 1);
$allPublished = !empty($results) && count($published) === count($results);
$isR1 = (int)$round['round_number'] === 1;
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-1">
            Round <?= (int)$round['round_number'] ?> — <?= e($round['name']) ?>
        </h1>
        <p class="text-muted small mb-0">Leaderboard · <?= count($results) ?> submission(s)</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0 flex-wrap">
        <a href="/reports/print/round/<?= (int)$round['round_id'] ?>" target="_blank"
           class="btn btn-outline-navy" rel="noopener">
            <i class="bi bi-printer me-1"></i> Print report
        </a>
        <?php if ($isR1): ?>
            <a href="/panelist/results/round/<?= (int)$round['round_id'] ?>/qualify"
               class="btn btn-accent text-white">
                <i class="bi bi-arrow-right-circle me-1"></i> Mark qualifiers
            </a>
        <?php endif; ?>
        <?php if ($allPublished && !empty($results)): ?>
            <form method="post" action="/panelist/results/round/<?= (int)$round['round_id'] ?>/unpublish"
                  onsubmit="return confirm('Un-publish this round? Scores will be hidden from schools.');">
                <?= csrf_field() ?>
                <button class="btn btn-outline-secondary">
                    <i class="bi bi-eye-slash me-1"></i> Un-publish
                </button>
            </form>
        <?php elseif (!empty($results)): ?>
            <form method="post" action="/panelist/results/round/<?= (int)$round['round_id'] ?>/publish"
                  onsubmit="return confirm('Publish results? Schools will see their scores.');">
                <?= csrf_field() ?>
                <button class="btn btn-primary">
                    <i class="bi bi-megaphone me-1"></i> Publish to schools
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Rank</th>
                    <th>School</th>
                    <th class="d-none d-md-table-cell">Slot</th>
                    <th>Score</th>
                    <th class="d-none d-md-table-cell">Correct</th>
                    <th class="d-none d-lg-table-cell">Time</th>
                    <?php if ($isR1): ?><th>Qualified</th><?php endif; ?>
                    <th class="d-none d-md-table-cell">Published</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="<?= $isR1 ? 8 : 7 ?>" class="text-center text-muted py-4">
                        No submissions for this round yet.
                    </td></tr>
                <?php else: foreach ($results as $r):
                    $time = (int)($r['attempt_time'] ?? 0);
                    $tStr = sprintf('%d:%02d', floor($time / 60), $time % 60); ?>
                    <tr>
                        <td>
                            <span class="fw-bold text-navy">#<?= (int)$r['rank_in_round'] ?></span>
                        </td>
                        <td>
                            <div class="fw-semibold text-navy"><?= e($r['school_name']) ?></div>
                            <div class="small text-muted">
                                <?= e($r['school_code'] ?? '') ?>
                                <?= $r['region'] ? ' · ' . e($r['region']) : '' ?>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell small text-muted">
                            <?= e($r['slot_label'] ?? '—') ?>
                        </td>
                        <td>
                            <span class="fw-semibold text-navy">
                                <?= rtrim(rtrim(number_format((float)$r['total_score'], 2), '0'), '.') ?>
                            </span>
                            <span class="small text-muted">
                                / <?= (int)$r['total_questions'] ?>
                            </span>
                        </td>
                        <td class="d-none d-md-table-cell small">
                            <span class="text-success-emphasis"><?= (int)$r['correct_count'] ?></span>
                            <span class="text-muted">·</span>
                            <span class="text-danger-emphasis"><?= (int)$r['wrong_count'] ?></span>
                            <span class="text-muted">·</span>
                            <span class="text-muted"><?= (int)$r['unanswered_count'] ?></span>
                        </td>
                        <td class="d-none d-lg-table-cell small text-muted"><?= $tStr ?></td>
                        <?php if ($isR1): ?>
                            <td>
                                <?php if ((int)$r['qualified_next_round'] === 1): ?>
                                    <span class="badge bg-accent text-white">
                                        <i class="bi bi-trophy me-1"></i> Yes
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td class="d-none d-md-table-cell">
                            <?php if ((int)$r['published'] === 1): ?>
                                <span class="<?= status_badge('published') ?>">Published</span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>.bg-accent{background-color:var(--or-teal)!important;}</style>
