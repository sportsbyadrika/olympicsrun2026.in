<?php
/** @var ?array $round */
/** @var array $associations */
/** @var array $statuses */
$old    = pull_old();
$errors = pull_errors();
$isEdit = !empty($round);
$action = $isEdit ? '/admin/rounds/' . (int)$round['round_id'] : '/admin/rounds';
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-0"><?= $isEdit ? 'Edit' : 'Add' ?> Round</h1>
    <a href="/admin/rounds" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e($action) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="association_id">Association *</label>
                    <select name="association_id" id="association_id"
                            class="form-select<?= invalid_class($errors, 'association_id') ?>" required>
                        <option value="">— Select —</option>
                        <?php foreach ($associations as $a):
                            $sel = (string)field($old, $round, 'association_id') === (string)$a['association_id']; ?>
                            <option value="<?= (int)$a['association_id'] ?>" <?= $sel ? 'selected' : '' ?>>
                                <?= e($a['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= err($errors, 'association_id') ?>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="round_number">Round # *</label>
                    <input type="number" min="1" max="9" name="round_number" id="round_number"
                           class="form-control<?= invalid_class($errors, 'round_number') ?>"
                           value="<?= e(field($old, $round, 'round_number', '1')) ?>" required>
                    <?= err($errors, 'round_number') ?>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select">
                        <?php $cur = field($old, $round, 'status', 'draft'); foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= $cur === $s ? 'selected' : '' ?>>
                                <?= e(status_label($s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="name">Name *</label>
                    <input type="text" name="name" id="name"
                           class="form-control<?= invalid_class($errors, 'name') ?>"
                           value="<?= e(field($old, $round, 'name')) ?>" required maxlength="100"
                           placeholder="e.g. Round 1 — Qualifier">
                    <?= err($errors, 'name') ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea name="description" id="description" rows="2" class="form-control"><?= e(field($old, $round, 'description')) ?></textarea>
                </div>

                <div class="col-12"><hr><h2 class="h6 text-uppercase text-muted">Timing & scoring</h2></div>

                <div class="col-6 col-md-3">
                    <label class="form-label" for="slot_duration_minutes">Slot (min) *</label>
                    <input type="number" min="5" max="240" name="slot_duration_minutes" id="slot_duration_minutes"
                           class="form-control<?= invalid_class($errors, 'slot_duration_minutes') ?>"
                           value="<?= e(field($old, $round, 'slot_duration_minutes', '30')) ?>" required>
                    <?= err($errors, 'slot_duration_minutes') ?>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="quiz_duration_minutes">Quiz (min) *</label>
                    <input type="number" min="1" max="240" name="quiz_duration_minutes" id="quiz_duration_minutes"
                           class="form-control<?= invalid_class($errors, 'quiz_duration_minutes') ?>"
                           value="<?= e(field($old, $round, 'quiz_duration_minutes', '15')) ?>" required>
                    <?= err($errors, 'quiz_duration_minutes') ?>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="questions_per_quiz">Questions *</label>
                    <input type="number" min="1" max="500" name="questions_per_quiz" id="questions_per_quiz"
                           class="form-control<?= invalid_class($errors, 'questions_per_quiz') ?>"
                           value="<?= e(field($old, $round, 'questions_per_quiz', '30')) ?>" required>
                    <?= err($errors, 'questions_per_quiz') ?>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="qualifiers_count">Qualifiers</label>
                    <input type="number" min="0" name="qualifiers_count" id="qualifiers_count" class="form-control"
                           value="<?= e(field($old, $round, 'qualifiers_count')) ?>"
                           placeholder="(blank = no cut)">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label" for="marks_correct">+ Correct</label>
                    <input type="number" step="0.25" name="marks_correct" id="marks_correct" class="form-control"
                           value="<?= e(field($old, $round, 'marks_correct', '1')) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="marks_wrong">- Wrong</label>
                    <input type="number" step="0.25" name="marks_wrong" id="marks_wrong" class="form-control"
                           value="<?= e(field($old, $round, 'marks_wrong', '0')) ?>">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="marks_unanswered">Unanswered</label>
                    <input type="number" step="0.25" name="marks_unanswered" id="marks_unanswered" class="form-control"
                           value="<?= e(field($old, $round, 'marks_unanswered', '0')) ?>">
                </div>

                <div class="col-12"><hr><h2 class="h6 text-uppercase text-muted">Window</h2></div>

                <div class="col-12 col-md-6">
                    <label class="form-label" for="starts_at">Starts at</label>
                    <input type="datetime-local" name="starts_at" id="starts_at" class="form-control"
                           value="<?= e(dt_for_input(field($old, $round, 'starts_at'))) ?>">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="ends_at">Ends at</label>
                    <input type="datetime-local" name="ends_at" id="ends_at" class="form-control"
                           value="<?= e(dt_for_input(field($old, $round, 'ends_at'))) ?>">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create round' ?></button>
                <a href="/admin/rounds" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
