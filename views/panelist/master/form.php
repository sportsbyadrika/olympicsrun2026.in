<?php
/** @var ?array $question */
$old    = pull_old();
$errors = pull_errors();
$isEdit = !empty($question);
$action = $isEdit ? '/panelist/master/' . (int)$question['master_question_id'] : '/panelist/master';
$correct = field($old, $question, 'correct_option');
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-0"><?= $isEdit ? 'Edit' : 'Add' ?> Master Question</h1>
    <a href="/panelist/master" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

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
                                <label for="correct_<?= $letter ?>" class="ms-2 mb-0 fw-semibold text-navy"><?= $letter ?></label>
                            </div>
                            <input type="text" name="<?= $key ?>" id="<?= $key ?>"
                                   class="form-control<?= invalid_class($errors, $key) ?>"
                                   value="<?= e(field($old, $question, $key)) ?>"
                                   required maxlength="500" placeholder="Option <?= $letter ?>">
                        </div>
                        <?= err($errors, $key) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card panel border-0 shadow-sm mt-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label class="form-label" for="difficulty">Difficulty *</label>
                    <select name="difficulty" id="difficulty" class="form-select" required>
                        <?php $cur = field($old, $question, 'difficulty', 'medium');
                        foreach (MasterQuestion::DIFFICULTIES as $d): ?>
                            <option value="<?= e($d) ?>" <?= $cur === $d ? 'selected' : '' ?>>
                                <?= e(ucfirst($d)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="intended_round">Intended round</label>
                    <select name="intended_round" id="intended_round" class="form-select">
                        <?php $cur = field($old, $question, 'intended_round'); ?>
                        <option value="" <?= $cur === '' ? 'selected' : '' ?>>—</option>
                        <option value="1" <?= $cur === '1' ? 'selected' : '' ?>>Round 1</option>
                        <option value="2" <?= $cur === '2' ? 'selected' : '' ?>>Round 2</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="sport">Sport</label>
                    <input type="text" name="sport" id="sport" class="form-control"
                           value="<?= e(field($old, $question, 'sport')) ?>" maxlength="100">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="category">Category</label>
                    <input type="text" name="category" id="category" class="form-control"
                           value="<?= e(field($old, $question, 'category')) ?>" maxlength="100">
                </div>
                <?php if ($isEdit): ?>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select">
                            <?php $cur = field($old, $question, 'status', 'active');
                            foreach (MasterQuestion::STATUSES as $s): ?>
                                <option value="<?= e($s) ?>" <?= $cur === $s ? 'selected' : '' ?>>
                                    <?= e(ucfirst($s)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div class="col-12">
                    <label class="form-label" for="explanation">Explanation</label>
                    <textarea name="explanation" id="explanation" rows="2" class="form-control"><?= e(field($old, $question, 'explanation')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary">
            <i class="bi bi-save me-1"></i>
            <?= $isEdit ? 'Save changes' : 'Add to master bank' ?>
        </button>
        <a href="/panelist/master" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
