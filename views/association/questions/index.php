<?php
/** @var array $questions */
/** @var array $counts */
/** @var string $filter */

$filters = [
    'all'            => 'All',
    'draft'          => 'Draft',
    'pending'        => 'Pending',
    'approved'       => 'Approved',
    'needs_revision' => 'Needs revision',
    'rejected'       => 'Rejected',
];
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">My Question Bank</h1>
    <a href="/association/questions/new" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New question
    </a>
</div>

<!-- Filter pills -->
<ul class="nav nav-pills flex-wrap mb-3 gap-1">
    <?php foreach ($filters as $key => $label):
        $count = $key === 'all' ? ($counts['total'] ?? 0) : ($counts[$key] ?? 0);
        $active = $filter === $key; ?>
        <li class="nav-item">
            <a class="nav-link <?= $active ? 'active' : '' ?>"
               href="/association/questions?status=<?= e($key) ?>">
                <?= e($label) ?>
                <span class="badge <?= $active ? 'bg-white text-navy' : 'bg-light text-muted' ?> ms-1">
                    <?= (int)$count ?>
                </span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<form method="post" action="/association/questions/submit" id="bulkForm">
    <?= csrf_field() ?>

    <!-- Bulk action bar -->
    <div class="card panel border-0 shadow-sm mb-3" id="bulkBar">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-2">
            <span class="text-muted small me-2">
                <span id="bulkCount">0</span> selected
            </span>
            <button type="submit" class="btn btn-accent btn-sm text-white" id="bulkSubmitBtn" disabled>
                <i class="bi bi-send me-1"></i> Submit to Expert Panel
            </button>
            <span class="small text-muted ms-auto d-none d-md-inline">
                Only draft &amp; needs-revision questions will be submitted.
            </span>
        </div>
    </div>

    <div class="card panel border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:32px">
                            <input type="checkbox" class="form-check-input" id="selectAll" aria-label="Select all">
                        </th>
                        <th>Question</th>
                        <th class="d-none d-md-table-cell">Sport</th>
                        <th class="d-none d-lg-table-cell">Difficulty</th>
                        <th>Status</th>
                        <th class="d-none d-lg-table-cell">Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($questions)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">
                            No questions yet. <a href="/association/questions/new" class="link-accent">Create your first one</a>.
                        </td></tr>
                    <?php else: foreach ($questions as $q):
                        $editable = AssociationQuestion::isEditable($q['status']); ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="question_ids[]" value="<?= (int)$q['question_id'] ?>"
                                       class="form-check-input row-check"
                                       <?= $editable ? '' : 'disabled aria-label="locked"' ?>>
                            </td>
                            <td>
                                <div class="fw-semibold text-navy">
                                    <?= e(mb_strimwidth($q['question_text'], 0, 140, '…')) ?>
                                </div>
                                <div class="small text-muted">
                                    A: <?= e(mb_strimwidth($q['option_a'], 0, 40, '…')) ?> ·
                                    B: <?= e(mb_strimwidth($q['option_b'], 0, 40, '…')) ?> ·
                                    <span class="text-accent">✓ Option <?= e($q['correct_option']) ?></span>
                                </div>
                                <?php if (!empty($q['reject_reason']) && in_array($q['status'], ['rejected', 'needs_revision'], true)): ?>
                                    <div class="small text-danger mt-1">
                                        <i class="bi bi-exclamation-circle me-1"></i>
                                        <?= e($q['reject_reason']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="d-none d-md-table-cell small text-muted"><?= e($q['sport'] ?? '—') ?></td>
                            <td class="d-none d-lg-table-cell">
                                <span class="badge bg-light text-dark border"><?= e(status_label($q['difficulty'])) ?></span>
                            </td>
                            <td>
                                <span class="<?= status_badge($q['status']) ?>"><?= e(status_label($q['status'])) ?></span>
                            </td>
                            <td class="d-none d-lg-table-cell small text-muted">
                                <?= e(dt_display($q['updated_at'], 'd M, H:i')) ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if ($editable): ?>
                                    <a href="/association/questions/<?= (int)$q['question_id'] ?>/edit"
                                       class="btn btn-sm btn-outline-navy" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="submit" formaction="/association/questions/<?= (int)$q['question_id'] ?>/delete"
                                            formmethod="post"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Delete this question?');"
                                            title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small"><i class="bi bi-lock"></i> locked</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
(function () {
    var $form  = document.getElementById('bulkForm');
    var $all   = document.getElementById('selectAll');
    var $count = document.getElementById('bulkCount');
    var $btn   = document.getElementById('bulkSubmitBtn');

    function rowChecks() {
        return Array.from($form.querySelectorAll('.row-check:not(:disabled)'));
    }
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
    $form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.id === 'bulkSubmitBtn') {
            if (!confirm('Submit selected questions to the expert panel? You will not be able to edit them while they are in review.')) {
                e.preventDefault();
            }
        }
    });
    refresh();
})();
</script>
