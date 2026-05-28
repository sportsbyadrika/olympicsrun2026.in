<?php
/** @var ?array $user */
/** @var array $associations */
/** @var array $statuses */
$old    = pull_old();
$errors = pull_errors();
$isEdit = !empty($user);
$action = $isEdit ? '/admin/association-users/' . (int)$user['association_user_id'] : '/admin/association-users';
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-0"><?= $isEdit ? 'Edit' : 'Add' ?> Association User</h1>
    <a href="/admin/association-users" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
                            $sel = (string)field($old, $user, 'association_id') === (string)$a['association_id']; ?>
                            <option value="<?= (int)$a['association_id'] ?>" <?= $sel ? 'selected' : '' ?>>
                                <?= e($a['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= err($errors, 'association_id') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select">
                        <?php $cur = field($old, $user, 'status', 'active'); foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= $cur === $s ? 'selected' : '' ?>>
                                <?= e(status_label($s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="full_name">Full name *</label>
                    <input type="text" name="full_name" id="full_name"
                           class="form-control<?= invalid_class($errors, 'full_name') ?>"
                           value="<?= e(field($old, $user, 'full_name')) ?>" required maxlength="150">
                    <?= err($errors, 'full_name') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="role_label">Role label</label>
                    <input type="text" name="role_label" id="role_label" class="form-control"
                           value="<?= e(field($old, $user, 'role_label', 'operator')) ?>" maxlength="60">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="username">Username *</label>
                    <input type="text" name="username" id="username" autocomplete="off"
                           class="form-control<?= invalid_class($errors, 'username') ?>"
                           value="<?= e(field($old, $user, 'username')) ?>" required maxlength="64">
                    <?= err($errors, 'username') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="email">Email *</label>
                    <input type="email" name="email" id="email"
                           class="form-control<?= invalid_class($errors, 'email') ?>"
                           value="<?= e(field($old, $user, 'email')) ?>" required maxlength="190">
                    <?= err($errors, 'email') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="phone">Phone</label>
                    <input type="text" name="phone" id="phone" class="form-control"
                           value="<?= e(field($old, $user, 'phone')) ?>" maxlength="20">
                </div>
                <div class="col-12"><hr class="text-muted"></div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="password">Password <?= $isEdit ? '<small class="text-muted">(leave blank to keep)</small>' : '*' ?></label>
                    <input type="password" name="password" id="password" autocomplete="new-password"
                           class="form-control<?= invalid_class($errors, 'password') ?>"
                           <?= $isEdit ? '' : 'required' ?>>
                    <?= err($errors, 'password') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           autocomplete="new-password" class="form-control">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create user' ?></button>
                <a href="/admin/association-users" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
