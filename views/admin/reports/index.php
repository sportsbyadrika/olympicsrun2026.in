<?php
/** @var array $groups */  // [ ['association'=>..., 'rounds'=>[...]], ... ]
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-0">Reports</h1>
        <p class="text-muted small mb-0">Printable leaderboards and consolidated results, per association.</p>
    </div>
</div>

<?php if (empty($groups)): ?>
    <div class="card panel border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-bar-chart display-5"></i>
            <p class="mb-0 mt-2">No associations configured yet.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($groups as $g):
            $a      = $g['association'];
            $rounds = $g['rounds'];
            $assocId = (int)$a['association_id'];
        ?>
            <div class="col-12 col-lg-6">
                <div class="card panel border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="h5 text-navy mb-0"><?= e($a['name']) ?></h2>
                                <span class="small text-muted"><?= e($a['short_code']) ?></span>
                            </div>
                            <a href="/reports/print/final/<?= $assocId ?>" target="_blank" rel="noopener"
                               class="btn btn-sm btn-accent text-white">
                                <i class="bi bi-trophy me-1"></i> Final
                            </a>
                        </div>

                        <?php if (empty($rounds)): ?>
                            <p class="small text-muted mb-0">No rounds configured.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($rounds as $r):
                                    $submitted = (int)$r['submitted_count']; ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                        <div>
                                            <span class="fw-semibold text-navy">
                                                Round <?= (int)$r['round_number'] ?>
                                            </span>
                                            <span class="text-muted small ms-1"><?= e($r['name']) ?></span>
                                            <div class="small text-muted">
                                                <?= $submitted ?> submitted ·
                                                <span class="<?= status_badge($r['status']) ?>"><?= e(status_label($r['status'])) ?></span>
                                            </div>
                                        </div>
                                        <a href="/reports/print/round/<?= (int)$r['round_id'] ?>" target="_blank"
                                           rel="noopener" class="btn btn-sm btn-outline-navy">
                                            <i class="bi bi-printer me-1"></i> Print
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
