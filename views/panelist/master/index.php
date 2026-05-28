<?php /** @var array $questions */ /** @var array $filters */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Master Bank</h1>
    <a href="/panelist/master/new" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add question
    </a>
</div>

<!-- Filters -->
<form method="get" action="/panelist/master" class="card panel border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Difficulty</label>
                <select name="difficulty" class="form-select form-select-sm">
                    <option value="">Any</option>
                    <?php foreach (MasterQuestion::DIFFICULTIES as $d): ?>
                        <option value="<?= e($d) ?>" <?= ($filters['difficulty'] ?? '') === $d ? 'selected' : '' ?>>
                            <?= e(ucfirst($d)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Any</option>
                    <?php foreach (MasterQuestion::STATUSES as $s): ?>
                        <option value="<?= e($s) ?>" <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
                            <?= e(ucfirst($s)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-auto align-self-end">
                <button class="btn btn-sm btn-outline-navy">Apply</button>
                <a href="/panelist/master" class="btn btn-sm btn-link">Clear</a>
            </div>
        </div>
    </div>
</form>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Question</th>
                    <th class="d-none d-md-table-cell">Sport</th>
                    <th class="d-none d-lg-table-cell">Round</th>
                    <th>Level</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No master questions match these filters.</td></tr>
                <?php else: foreach ($questions as $q): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-navy">
                                <?= e(mb_strimwidth($q['question_text'], 0, 140, '…')) ?>
                            </div>
                            <div class="small text-muted">
                                ✓ Option <?= e($q['correct_option']) ?>
                                <?php if ($q['source_question_id']): ?>
                                    · <span class="text-muted">migrated from bank #<?= (int)$q['source_question_id'] ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell small text-muted"><?= e($q['sport'] ?? '—') ?></td>
                        <td class="d-none d-lg-table-cell">
                            <?= $q['intended_round'] ? 'R' . (int)$q['intended_round'] : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= e(ucfirst($q['difficulty'])) ?></span></td>
                        <td><span class="<?= status_badge($q['status']) ?>"><?= e(status_label($q['status'])) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="/panelist/master/<?= (int)$q['master_question_id'] ?>/edit"
                               class="btn btn-sm btn-outline-navy"><i class="bi bi-pencil"></i></a>
                            <form action="/panelist/master/<?= (int)$q['master_question_id'] ?>/delete"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this master question? Used in any slot will block delete.');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
