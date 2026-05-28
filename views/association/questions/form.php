<?php
/** @var ?array $question */
$old    = pull_old();
$errors = pull_errors();
$isEdit = !empty($question);
$action = $isEdit ? '/association/questions/' . (int)$question['question_id'] : '/association/questions';
$status = $question['status'] ?? 'draft';
$correct = field($old, $question, 'correct_option');
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-0"><?= $isEdit ? 'Edit' : 'New' ?> Question</h1>
        <?php if ($isEdit): ?>
            <p class="text-muted small mb-0">
                Status: <span class="<?= status_badge($status) ?>"><?= e(status_label($status)) ?></span>
            </p>
        <?php endif; ?>
    </div>
    <a href="/association/questions" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if ($isEdit && $status === 'needs_revision' && !empty($question['reject_reason'])): ?>
    <div class="alert alert-warning small">
        <strong>Reviewer note:</strong> <?= e($question['reject_reason']) ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="card panel border-0 shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label" for="question_text">Question *</label>
                <textarea name="question_text" id="question_text" rows="3"
                          class="form-control<?= invalid_class($errors, 'question_text') ?>"
                          required maxlength="2000"><?= e(field($old, $question, 'question_text')) ?></textarea>
                <?= err($errors, 'question_text') ?>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-2">Options</h2>
            <p class="small text-muted">Tick the radio next to the correct answer.</p>

            <div class="row g-3">
                <?php foreach (['A', 'B', 'C', 'D'] as $letter):
                    $key = 'option_' . strtolower($letter);
                    $isCorrect = $correct === $letter; ?>
                    <div class="col-12 col-md-6">
                        <div class="input-group">
                            <div class="input-group-text bg-white">
                                <input type="radio" name="correct_option" id="correct_<?= $letter ?>"
                                       value="<?= $letter ?>" class="form-check-input mt-0"
                                       <?= $isCorrect ? 'checked' : '' ?>>
                                <label for="correct_<?= $letter ?>"
                                       class="ms-2 mb-0 fw-semibold text-navy">
                                    <?= $letter ?>
                                </label>
                            </div>
                            <input type="text" name="<?= $key ?>" id="<?= $key ?>"
                                   class="form-control<?= invalid_class($errors, $key) ?>"
                                   value="<?= e(field($old, $question, $key)) ?>"
                                   required maxlength="500"
                                   placeholder="Option <?= $letter ?>">
                        </div>
                        <?= err($errors, $key) ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?= err($errors, 'correct_option') ?>
        </div>
    </div>

    <div class="card panel border-0 shadow-sm mt-3">
        <div class="card-body">
            <h2 class="h6 text-uppercase text-muted mb-3">Tags &amp; reference</h2>
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="sport">Sport</label>
                    <input type="text" name="sport" id="sport" class="form-control"
                           value="<?= e(field($old, $question, 'sport')) ?>"
                           list="sport-suggestions" maxlength="100"
                           placeholder="e.g. Athletics">
                    <datalist id="sport-suggestions">
                        <option>Athletics</option><option>Aquatics</option>
                        <option>Badminton</option><option>Cricket</option>
                        <option>Football</option><option>Hockey</option>
                        <option>Boxing</option><option>Wrestling</option>
                    </datalist>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="category">Category</label>
                    <input type="text" name="category" id="category" class="form-control"
                           value="<?= e(field($old, $question, 'category')) ?>" maxlength="100"
                           placeholder="e.g. History">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="difficulty">Difficulty</label>
                    <select name="difficulty" id="difficulty" class="form-select">
                        <?php $cur = field($old, $question, 'difficulty', 'medium');
                        foreach (AssociationQuestion::DIFFICULTIES as $d): ?>
                            <option value="<?= e($d) ?>" <?= $cur === $d ? 'selected' : '' ?>>
                                <?= e(ucfirst($d)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="explanation">Explanation <small class="text-muted">(shown after submission)</small></label>
                    <textarea name="explanation" id="explanation" rows="2" class="form-control"><?= e(field($old, $question, 'explanation')) ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label" for="reference_source">Reference / source</label>
                    <input type="text" name="reference_source" id="reference_source" class="form-control"
                           value="<?= e(field($old, $question, 'reference_source')) ?>" maxlength="255"
                           placeholder="e.g. Olympics.com, FINA Handbook 2024">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>
            <?= $isEdit ? 'Save changes' : 'Save as draft' ?>
        </button>
        <?php if (!$isEdit || in_array($status, ['draft', 'needs_revision'], true)): ?>
            <button type="submit" name="_submit_now" value="1" class="btn btn-accent text-white"
                    onclick="return confirm('Submit this question to the expert panel? You will not be able to edit it while it is in review.');">
                <i class="bi bi-send me-1"></i>
                <?= $status === 'needs_revision' ? 'Save &amp; re-submit' : 'Save &amp; submit to panel' ?>
            </button>
        <?php endif; ?>
        <a href="/association/questions" class="btn btn-outline-secondary ms-auto">Cancel</a>
    </div>
</form>
