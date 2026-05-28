<?php
/** @var ?array $login */
/** @var array $schools */
/** @var array $statuses */
$preselectSchoolId = $preselectSchoolId ?? 0;
$prefilledUsername = $prefilledUsername ?? '';
$prefilledPassword = $prefilledPassword ?? '';
$old    = pull_old();
$errors = pull_errors();
$isEdit = !empty($login);
$action = $isEdit ? '/admin/school-logins/' . (int)$login['school_login_id'] : '/admin/school-logins';
$selectedSchool = (int)field($old, $login, 'school_id', (string)$preselectSchoolId);
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-0"><?= $isEdit ? 'Edit' : 'Add' ?> School Login</h1>
    <a href="/admin/school-logins" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<?php if (!$isEdit && $prefilledPassword !== ''): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-1"></i>
        Username and password pre-filled. <strong>Copy the password before saving</strong> —
        it is hashed on save and cannot be recovered.
    </div>
<?php endif; ?>

<div class="card panel border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e($action) ?>" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="_show_password" value="<?= $prefilledPassword !== '' ? '1' : '0' ?>">
            <div class="row g-3">
                <div class="col-12 col-md-8">
                    <label class="form-label" for="school_id">School *</label>
                    <select name="school_id" id="school_id"
                            class="form-select<?= invalid_class($errors, 'school_id') ?>" required>
                        <option value="">— Select —</option>
                        <?php foreach ($schools as $s):
                            $sel = $selectedSchool === (int)$s['school_id']; ?>
                            <option value="<?= (int)$s['school_id'] ?>" <?= $sel ? 'selected' : '' ?>>
                                <?= e($s['school_name']) ?> <?= $s['school_code'] ? '(' . e($s['school_code']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= err($errors, 'school_id') ?>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select">
                        <?php $cur = field($old, $login, 'status', 'active'); foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= $cur === $s ? 'selected' : '' ?>>
                                <?= e(status_label($s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="username">Username *</label>
                    <input type="text" name="username" id="username" autocomplete="off"
                           class="form-control<?= invalid_class($errors, 'username') ?>"
                           value="<?= e(field($old, $login, 'username', $prefilledUsername)) ?>" required maxlength="64">
                    <?= err($errors, 'username') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="team_label">Team label</label>
                    <input type="text" name="team_label" id="team_label" class="form-control"
                           value="<?= e(field($old, $login, 'team_label')) ?>" maxlength="100"
                           placeholder="e.g. Senior Team">
                </div>
                <div class="col-12"><hr class="text-muted"></div>
                <div class="col-12">
                    <label class="form-label" for="password">Password <?= $isEdit ? '<small class="text-muted">(leave blank to keep)</small>' : '*' ?></label>
                    <input type="text" name="password" id="password" autocomplete="off"
                           class="form-control font-monospace<?= invalid_class($errors, 'password') ?>"
                           value="<?= e($prefilledPassword) ?>" <?= $isEdit ? '' : 'required' ?>>
                    <div class="form-text">For school logins we show the password in clear so the operator can write it down. It is hashed on save.</div>
                    <?= err($errors, 'password') ?>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create login' ?></button>
                <a href="/admin/school-logins" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
