<?php /** @var array $rounds */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Results</h1>
    <a href="/panelist/results/final" class="btn btn-accent text-white">
        <i class="bi bi-trophy me-1"></i> Final consolidated
    </a>
</div>

<?php if (empty($rounds)): ?>
    <div class="card panel border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox display-5"></i>
            <p class="mb-0 mt-2">No rounds configured for your association yet.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($rounds as $r):
            $assigned   = (int)$r['assigned_count'];
            $submitted  = (int)$r['submitted_count'];
            $published  = (int)$r['published_count'];
            $qualifiers = (int)$r['qualifiers_marked'];
            $isR1 = (int)$r['round_number'] === 1; ?>
            <div class="col-12 col-lg-6">
                <div class="card panel border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h2 class="h5 text-navy mb-1">
                                    Round <?= (int)$r['round_number'] ?> — <?= e($r['name']) ?>
                                </h2>
                                <span class="<?= status_badge($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                            </div>
                            <span class="badge bg-light text-dark border">
                                <?= $submitted ?> / <?= $assigned ?> submitted
                            </span>
                        </div>

                        <div class="row g-2 mt-2 small">
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Top score</div>
                                <div class="fw-semibold text-navy">
                                    <?= $r['top_score'] !== null
                                        ? rtrim(rtrim(number_format((float)$r['top_score'], 2), '0'), '.')
                                        : '—' ?>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Avg score</div>
                                <div class="fw-semibold text-navy">
                                    <?= $r['avg_score'] !== null
                                        ? rtrim(rtrim(number_format((float)$r['avg_score'], 2), '0'), '.')
                                        : '—' ?>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="text-muted">Published</div>
                                <div class="fw-semibold text-navy"><?= $published ?> / <?= $submitted ?></div>
                            </div>
                            <?php if ($isR1): ?>
                                <div class="col-6 col-md-3">
                                    <div class="text-muted">Qualifiers</div>
                                    <div class="fw-semibold text-accent">
                                        <?= $qualifiers ?>
                                        <?php if ($r['qualifiers_count']): ?>
                                            <span class="text-muted small">/ <?= (int)$r['qualifiers_count'] ?> target</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <a href="/panelist/results/round/<?= (int)$r['round_id'] ?>"
                               class="btn btn-sm btn-outline-navy">
                                <i class="bi bi-list-ol me-1"></i> Leaderboard
                            </a>
                            <?php if ($isR1 && $submitted > 0): ?>
                                <a href="/panelist/results/round/<?= (int)$r['round_id'] ?>/qualify"
                                   class="btn btn-sm btn-accent text-white">
                                    <i class="bi bi-arrow-right-circle me-1"></i> Mark qualifiers
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
