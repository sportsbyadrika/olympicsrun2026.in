<?php
/** @var array $questions */
/** @var array $associations */
/** @var array $filters */
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-0">Questions</h1>
        <p class="text-muted small mb-0">Curated master pool across all associations (read-only).</p>
    </div>
</div>

<!-- Filters -->
<form method="get" action="/admin/questions" class="card panel border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <label class="form-label small mb-1">Association</label>
                <select name="association_id" class="form-select form-select-sm">
                    <option value="">Any</option>
                    <?php foreach ($associations as $a): ?>
                        <option value="<?= (int)$a['association_id'] ?>"
                            <?= (string)($filters['association_id'] ?? '') === (string)$a['association_id'] ? 'selected' : '' ?>>
                            <?= e($a['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
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
            <div class="col-6 col-md-2">
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
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Round</label>
                <select name="round" class="form-select form-select-sm">
                    <option value="">Any</option>
                    <option value="1" <?= ($filters['round'] ?? '') === '1' ? 'selected' : '' ?>>Round 1</option>
                    <option value="2" <?= ($filters['round'] ?? '') === '2' ? 'selected' : '' ?>>Round 2</option>
                </select>
            </div>
            <div class="col-12 col-md-auto align-self-end">
                <button class="btn btn-sm btn-outline-navy">Apply</button>
                <a href="/admin/questions" class="btn btn-sm btn-link">Clear</a>
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
                    <th class="d-none d-md-table-cell">Association</th>
                    <th class="d-none d-md-table-cell">Sport</th>
                    <th class="d-none d-lg-table-cell">Round</th>
                    <th>Level</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No questions match these filters.</td></tr>
                <?php else: foreach ($questions as $q): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-navy">
                                <?= e(mb_strimwidth($q['question_text'], 0, 140, '…')) ?>
                            </div>
                            <div class="small text-muted">✓ Option <?= e($q['correct_option']) ?></div>
                        </td>
                        <td class="d-none d-md-table-cell small text-muted"><?= e($q['association_name']) ?></td>
                        <td class="d-none d-md-table-cell small text-muted"><?= e($q['sport'] ?? '—') ?></td>
                        <td class="d-none d-lg-table-cell">
                            <?= $q['intended_round'] ? 'R' . (int)$q['intended_round'] : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td><span class="badge bg-light text-dark border"><?= e(ucfirst($q['difficulty'])) ?></span></td>
                        <td><span class="<?= status_badge($q['status']) ?>"><?= e(status_label($q['status'])) ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="small text-muted mt-2">
    <i class="bi bi-info-circle me-1"></i>
    Showing <?= count($questions) ?> question<?= count($questions) === 1 ? '' : 's' ?>.
    Questions are authored and edited by Expert Panelists in the panelist module.
</p>
