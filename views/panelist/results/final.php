<?php
/** @var array $rounds */
/** @var array $rows */
$hasR1 = false; $hasR2 = false;
foreach ($rounds as $r) {
    if ((int)$r['round_number'] === 1) $hasR1 = true;
    if ((int)$r['round_number'] === 2) $hasR2 = true;
}
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-1">Final Consolidated Result</h1>
        <p class="text-muted small mb-0">
            Combined Round 1 + Round 2 score, ranked by total.
        </p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-md-0 flex-wrap">
        <a href="/reports/print/final/<?= (int)Auth::associationId() ?>" target="_blank"
           class="btn btn-outline-navy" rel="noopener">
            <i class="bi bi-printer me-1"></i> Print report
        </a>
        <a href="/panelist/results" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<?php if (empty($rows)): ?>
    <div class="card panel border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox display-5"></i>
            <p class="mb-0 mt-2">No submitted results yet.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card panel border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>School</th>
                        <?php if ($hasR1): ?>
                            <th class="d-none d-md-table-cell">R1 score</th>
                            <th class="d-none d-lg-table-cell">R1 rank</th>
                        <?php endif; ?>
                        <?php if ($hasR2): ?>
                            <th class="d-none d-md-table-cell">R2 score</th>
                            <th class="d-none d-lg-table-cell">R2 rank</th>
                        <?php endif; ?>
                        <th>Combined</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $i => $row):
                    $rank = $i + 1;
                    $highlight = $rank <= 3 ? ' class="table-warning-subtle"' : '';
                    $fmt = static fn($v) => $v !== null
                        ? rtrim(rtrim(number_format((float)$v, 2), '0'), '.')
                        : '<span class="text-muted">—</span>';
                ?>
                    <tr<?= $highlight ?>>
                        <td>
                            <?php if ($rank === 1): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-trophy-fill"></i> 1</span>
                            <?php elseif ($rank === 2): ?>
                                <span class="badge bg-secondary text-white"><i class="bi bi-award-fill"></i> 2</span>
                            <?php elseif ($rank === 3): ?>
                                <span class="badge bg-warning-subtle text-warning-emphasis border"><i class="bi bi-award"></i> 3</span>
                            <?php else: ?>
                                <span class="fw-semibold text-navy"><?= $rank ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold text-navy"><?= e($row['school_name']) ?></div>
                            <div class="small text-muted">
                                <?= e($row['school_code'] ?? '') ?>
                                <?= $row['region'] ? ' · ' . e($row['region']) : '' ?>
                            </div>
                        </td>
                        <?php if ($hasR1): ?>
                            <td class="d-none d-md-table-cell">
                                <?= $fmt($row['r1_score']) ?>
                                <?php if ($row['r1_correct'] !== null): ?>
                                    <div class="small text-muted">
                                        <?= (int)$row['r1_correct'] ?>/<?= (int)$row['r1_total'] ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-lg-table-cell small">
                                <?= $row['r1_rank'] ? '#' . (int)$row['r1_rank'] : '<span class="text-muted">—</span>' ?>
                                <?php if ((int)($row['r1_qualified'] ?? 0) === 1): ?>
                                    <span class="badge bg-accent text-white ms-1" title="Qualified to R2">Q</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <?php if ($hasR2): ?>
                            <td class="d-none d-md-table-cell">
                                <?= $fmt($row['r2_score']) ?>
                                <?php if ($row['r2_correct'] !== null): ?>
                                    <div class="small text-muted">
                                        <?= (int)$row['r2_correct'] ?>/<?= (int)$row['r2_total'] ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-lg-table-cell small">
                                <?= $row['r2_rank'] ? '#' . (int)$row['r2_rank'] : '<span class="text-muted">—</span>' ?>
                            </td>
                        <?php endif; ?>
                        <td class="fw-bold text-navy">
                            <?= $fmt($row['combined_score']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<style>
.bg-accent { background-color: var(--or-teal) !important; }
.table-warning-subtle { background-color: rgba(255, 213, 79, 0.07); }
</style>
