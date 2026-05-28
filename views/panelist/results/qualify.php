<?php
/** @var array $round */
/** @var array $results */
/** @var ?array $r2 */
/** @var array $r2Slots */
/** @var array<int,int> $r2Existing */    // school_id => slot_id (already in R2)
$targetCount = (int)($round['qualifiers_count'] ?? 0);
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-1">Select Round 2 Qualifiers</h1>
        <p class="text-muted small mb-0">
            From <?= count($results) ?> Round 1 submission(s).
            <?php if ($targetCount): ?>
                Target: top <strong class="text-navy"><?= $targetCount ?></strong>.
            <?php endif; ?>
        </p>
    </div>
    <a href="/panelist/results/round/<?= (int)$round['round_id'] ?>"
       class="btn btn-outline-secondary mt-2 mt-md-0">
        <i class="bi bi-arrow-left me-1"></i> Back to leaderboard
    </a>
</div>

<?php if (empty($r2)): ?>
    <div class="alert alert-warning small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        No Round 2 exists for this association yet. Mark qualifiers below;
        slot assignment will be skipped until R2 is created.
    </div>
<?php elseif (empty($r2Slots)): ?>
    <div class="alert alert-warning small">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Round 2 has no slots yet. Qualifiers can still be marked — admin must
        create R2 slots before they can be assigned.
    </div>
<?php endif; ?>

<form method="post" action="/panelist/results/round/<?= (int)$round['round_id'] ?>/qualify"
      id="qForm" novalidate>
    <?= csrf_field() ?>

    <div class="card panel border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="small text-muted me-2">
                    <span id="selCount">0</span> selected
                </span>
                <?php if ($targetCount && !empty($results)): ?>
                    <button type="button" class="btn btn-sm btn-outline-navy" id="btnAutoTop">
                        <i class="bi bi-magic me-1"></i> Auto-select top <?= $targetCount ?>
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnClear">
                    Clear
                </button>
                <button class="btn btn-sm btn-primary ms-auto">
                    <i class="bi bi-save me-1"></i> Save qualifiers
                </button>
            </div>
        </div>
    </div>

    <div class="card panel border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:32px"></th>
                        <th>Rank</th>
                        <th>School</th>
                        <th>Score</th>
                        <th class="d-none d-md-table-cell">Time</th>
                        <th>Assign to R2 slot</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($results as $i => $r):
                    $sid     = (int)$r['school_id'];
                    $checked = (int)$r['qualified_next_round'] === 1;
                    $time    = (int)($r['attempt_time'] ?? 0);
                    $tStr    = sprintf('%d:%02d', floor($time / 60), $time % 60);
                    $currentSlot = $r2Existing[$sid] ?? null;
                ?>
                    <tr data-rank="<?= (int)$r['rank_in_round'] ?>">
                        <td>
                            <input type="checkbox" class="form-check-input qual-check"
                                   name="qualifier_school[]" value="<?= $sid ?>"
                                   <?= $checked ? 'checked' : '' ?>>
                        </td>
                        <td><span class="fw-bold text-navy">#<?= (int)$r['rank_in_round'] ?></span></td>
                        <td>
                            <div class="fw-semibold text-navy"><?= e($r['school_name']) ?></div>
                            <div class="small text-muted">
                                <?= e($r['school_code'] ?? '') ?>
                                <?= $r['region'] ? ' · ' . e($r['region']) : '' ?>
                            </div>
                        </td>
                        <td class="fw-semibold text-navy">
                            <?= rtrim(rtrim(number_format((float)$r['total_score'], 2), '0'), '.') ?>
                        </td>
                        <td class="d-none d-md-table-cell small text-muted"><?= $tStr ?></td>
                        <td>
                            <?php if (!empty($r2Slots)): ?>
                                <select name="slot_assignment[<?= $sid ?>]"
                                        class="form-select form-select-sm slot-pick"
                                        <?= $checked ? '' : 'disabled' ?>>
                                    <option value="">— pick slot —</option>
                                    <?php foreach ($r2Slots as $sl):
                                        $sel = $currentSlot === (int)$sl['slot_id']; ?>
                                        <option value="<?= (int)$sl['slot_id'] ?>" <?= $sel ? 'selected' : '' ?>>
                                            <?= e($sl['slot_label'] ?? '#' . $sl['slot_id']) ?>
                                            (<?= (int)$sl['assigned_count'] ?>/<?= (int)$sl['capacity'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <span class="small text-muted">No R2 slots</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
(function () {
    var TARGET = <?= (int)$targetCount ?>;
    var $form  = document.getElementById('qForm');
    var $count = document.getElementById('selCount');

    function refresh() {
        var n = $form.querySelectorAll('.qual-check:checked').length;
        $count.textContent = n;
        $form.querySelectorAll('tbody tr').forEach(function (row) {
            var cb = row.querySelector('.qual-check');
            var sel = row.querySelector('.slot-pick');
            if (sel) sel.disabled = !cb.checked;
            if (sel && !cb.checked) sel.value = '';
        });
    }

    $form.addEventListener('change', function (e) {
        if (e.target.classList.contains('qual-check')) refresh();
    });

    var $auto = document.getElementById('btnAutoTop');
    if ($auto) {
        $auto.addEventListener('click', function () {
            var rows = Array.from($form.querySelectorAll('tbody tr'));
            rows.sort(function (a, b) {
                return parseInt(a.dataset.rank, 10) - parseInt(b.dataset.rank, 10);
            });
            rows.forEach(function (row, idx) {
                var cb = row.querySelector('.qual-check');
                cb.checked = (idx < TARGET);
            });
            refresh();
        });
    }
    document.getElementById('btnClear').addEventListener('click', function () {
        $form.querySelectorAll('.qual-check').forEach(function (cb) { cb.checked = false; });
        refresh();
    });

    refresh();
})();
</script>
