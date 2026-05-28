<?php /** @var array $pending */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-0">Review Queue</h1>
    <span class="badge bg-navy text-white fs-6"><?= count($pending) ?> pending</span>
</div>

<?php if (empty($pending)): ?>
    <div class="card panel border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-check2-circle display-5 text-success-emphasis"></i>
            <p class="mb-0 mt-2">Nothing in the queue — the panel is clear.</p>
        </div>
    </div>
<?php else: ?>

<form method="post" action="/panelist/review/approve-bulk" id="bulkForm">
    <?= csrf_field() ?>

    <!-- Bulk approve bar -->
    <div class="card panel border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-auto">
                    <span class="text-muted small">
                        <span id="bulkCount">0</span> selected
                    </span>
                </div>
                <div class="col-6 col-md-3">
                    <select name="difficulty" class="form-select form-select-sm" required>
                        <?php foreach (MasterQuestion::DIFFICULTIES as $d): ?>
                            <option value="<?= e($d) ?>" <?= $d === 'medium' ? 'selected' : '' ?>>
                                Difficulty: <?= e(ucfirst($d)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-auto">
                    <button type="submit" class="btn btn-sm btn-accent text-white" id="bulkBtn" disabled>
                        <i class="bi bi-arrow-right-circle me-1"></i> Approve &amp; migrate
                    </button>
                </div>
                <div class="col-12 col-md ms-auto text-md-end">
                    <label class="form-check small text-muted mb-0">
                        <input type="checkbox" class="form-check-input me-1" id="selectAll">
                        Select all
                    </label>
                </div>
            </div>
        </div>
    </div>

    <!-- Card list -->
    <div class="row g-3">
        <?php foreach ($pending as $q): ?>
            <div class="col-12">
                <div class="card panel border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex gap-2 mb-2">
                            <input type="checkbox" name="question_ids[]" value="<?= (int)$q['question_id'] ?>"
                                   class="form-check-input row-check mt-1">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap gap-2 small text-muted mb-1">
                                    <?php if ($q['sport']):    ?><span class="badge bg-light text-dark border"><?= e($q['sport']) ?></span><?php endif; ?>
                                    <?php if ($q['category']): ?><span class="badge bg-light text-dark border"><?= e($q['category']) ?></span><?php endif; ?>
                                    <span class="badge bg-light text-dark border">Author difficulty: <?= e(ucfirst($q['difficulty'])) ?></span>
                                    <?php if ($q['author_name']): ?>
                                        <span class="text-muted">by <?= e($q['author_name']) ?></span>
                                    <?php endif; ?>
                                    <span class="ms-auto"><?= e(dt_display($q['submitted_at'], 'd M, H:i')) ?></span>
                                </div>
                                <p class="fw-semibold text-navy mb-2"><?= e($q['question_text']) ?></p>
                                <div class="row g-2 small mb-2">
                                    <?php foreach (['A', 'B', 'C', 'D'] as $L):
                                        $opt = $q['option_' . strtolower($L)];
                                        $isCorrect = $q['correct_option'] === $L; ?>
                                        <div class="col-12 col-md-6">
                                            <span class="<?= $isCorrect ? 'text-accent fw-semibold' : 'text-muted' ?>">
                                                <?= $isCorrect ? '<i class="bi bi-check-circle-fill me-1"></i>' : '<span class="me-1">·</span>' ?>
                                                <strong><?= $L ?>.</strong> <?= e($opt) ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (!empty($q['explanation'])): ?>
                                    <p class="small text-muted mb-2"><em>Note:</em> <?= e($q['explanation']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Per-question actions -->
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <!-- Inline approve form (separate from bulk) -->
                            <form action="/panelist/review/<?= (int)$q['question_id'] ?>/approve"
                                  method="post" class="d-flex gap-1 align-items-center">
                                <?= csrf_field() ?>
                                <select name="difficulty" class="form-select form-select-sm" style="width:auto">
                                    <?php foreach (MasterQuestion::DIFFICULTIES as $d): ?>
                                        <option value="<?= e($d) ?>" <?= $q['difficulty'] === $d ? 'selected' : '' ?>>
                                            <?= e(ucfirst($d)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-accent text-white">
                                    <i class="bi bi-check2 me-1"></i> Approve
                                </button>
                            </form>

                            <button type="button" class="btn btn-sm btn-outline-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#reviseModal-<?= (int)$q['question_id'] ?>">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Send back
                            </button>

                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#rejectModal-<?= (int)$q['question_id'] ?>">
                                <i class="bi bi-x-circle me-1"></i> Reject
                            </button>
                        </div>

                        <?php
                        // Revise modal
                        $modals = [
                            'reviseModal-' . (int)$q['question_id'] => [
                                'title'  => 'Send back for revision',
                                'action' => '/panelist/review/' . (int)$q['question_id'] . '/revise',
                                'label'  => 'What needs to change?',
                                'btn'    => 'Send back',
                                'class'  => 'btn-warning',
                            ],
                            'rejectModal-' . (int)$q['question_id'] => [
                                'title'  => 'Reject question',
                                'action' => '/panelist/review/' . (int)$q['question_id'] . '/reject',
                                'label'  => 'Reason for rejection',
                                'btn'    => 'Reject',
                                'class'  => 'btn-danger',
                            ],
                        ];
                        foreach ($modals as $modalId => $m): ?>
                            <div class="modal fade" id="<?= $modalId ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="post" action="<?= e($m['action']) ?>" class="modal-content">
                                        <?= csrf_field() ?>
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?= e($m['title']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <label class="form-label small"><?= e($m['label']) ?> *</label>
                                            <textarea name="reason" rows="3" class="form-control" required></textarea>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn <?= e($m['class']) ?>"><?= e($m['btn']) ?></button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</form>

<script>
(function () {
    var $form  = document.getElementById('bulkForm');
    var $all   = document.getElementById('selectAll');
    var $count = document.getElementById('bulkCount');
    var $btn   = document.getElementById('bulkBtn');
    function rowChecks() { return Array.from($form.querySelectorAll('.row-check')); }
    function refresh() {
        var n = $form.querySelectorAll('.row-check:checked').length;
        $count.textContent = n;
        $btn.disabled = (n === 0);
    }
    $all.addEventListener('change', function () {
        rowChecks().forEach(function (cb) { cb.checked = $all.checked; });
        refresh();
    });
    $form.addEventListener('change', function (e) {
        if (e.target.classList.contains('row-check')) refresh();
    });
    refresh();
})();
</script>

<?php endif; ?>
