<?php
/** @var ?array $association */
/** @var array $statuses */
$old    = pull_old();
$errors = pull_errors();
$isEdit = !empty($association);
$action = $isEdit ? '/admin/associations/' . (int)$association['association_id'] : '/admin/associations';
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-0"><?= $isEdit ? 'Edit' : 'Add' ?> Association</h1>
    <a href="/admin/associations" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e($action) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12 col-md-8">
                    <label class="form-label" for="name">Name *</label>
                    <input type="text" name="name" id="name"
                           class="form-control<?= invalid_class($errors, 'name') ?>"
                           value="<?= e(field($old, $association, 'name')) ?>" required maxlength="200">
                    <?= err($errors, 'name') ?>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="short_code">Short code *</label>
                    <input type="text" name="short_code" id="short_code"
                           class="form-control<?= invalid_class($errors, 'short_code') ?>"
                           value="<?= e(field($old, $association, 'short_code')) ?>" required maxlength="20">
                    <?= err($errors, 'short_code') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="region">Region</label>
                    <input type="text" name="region" id="region"
                           class="form-control<?= invalid_class($errors, 'region') ?>"
                           value="<?= e(field($old, $association, 'region')) ?>" maxlength="100">
                    <?= err($errors, 'region') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select<?= invalid_class($errors, 'status') ?>">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= field($old, $association, 'status', 'active') === $s ? 'selected' : '' ?>>
                                <?= e(status_label($s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= err($errors, 'status') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="contact_email">Contact email</label>
                    <input type="email" name="contact_email" id="contact_email"
                           class="form-control<?= invalid_class($errors, 'contact_email') ?>"
                           value="<?= e(field($old, $association, 'contact_email')) ?>" maxlength="190">
                    <?= err($errors, 'contact_email') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="contact_phone">Contact phone</label>
                    <input type="text" name="contact_phone" id="contact_phone"
                           class="form-control<?= invalid_class($errors, 'contact_phone') ?>"
                           value="<?= e(field($old, $association, 'contact_phone')) ?>" maxlength="20">
                    <?= err($errors, 'contact_phone') ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="address">Address</label>
                    <textarea name="address" id="address" rows="2" class="form-control"><?= e(field($old, $association, 'address')) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create association' ?></button>
                <a href="/admin/associations" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
