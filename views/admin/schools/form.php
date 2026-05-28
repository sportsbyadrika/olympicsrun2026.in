<?php
/** @var ?array $school */
/** @var array $associations */
/** @var array $statuses */
/** @var array $logins */
$old    = pull_old();
$errors = pull_errors();
$isEdit = !empty($school);
$action = $isEdit ? '/admin/schools/' . (int)$school['school_id'] : '/admin/schools';
$logins = $logins ?? [];
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-0"><?= $isEdit ? 'Edit' : 'Add' ?> School</h1>
    <a href="/admin/schools" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
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
                            $sel = (string)field($old, $school, 'association_id') === (string)$a['association_id']; ?>
                            <option value="<?= (int)$a['association_id'] ?>" <?= $sel ? 'selected' : '' ?>>
                                <?= e($a['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?= err($errors, 'association_id') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select<?= invalid_class($errors, 'status') ?>">
                        <?php $cur = field($old, $school, 'status', 'pending'); foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= $cur === $s ? 'selected' : '' ?>>
                                <?= e(status_label($s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label" for="school_name">School name *</label>
                    <input type="text" name="school_name" id="school_name"
                           class="form-control<?= invalid_class($errors, 'school_name') ?>"
                           value="<?= e(field($old, $school, 'school_name')) ?>" required maxlength="200">
                    <?= err($errors, 'school_name') ?>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="school_code">School code</label>
                    <input type="text" name="school_code" id="school_code"
                           class="form-control<?= invalid_class($errors, 'school_code') ?>"
                           value="<?= e(field($old, $school, 'school_code')) ?>" maxlength="50">
                    <?= err($errors, 'school_code') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="region">Region</label>
                    <input type="text" name="region" id="region" class="form-control"
                           value="<?= e(field($old, $school, 'region')) ?>" maxlength="100">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="principal_name">Principal</label>
                    <input type="text" name="principal_name" id="principal_name" class="form-control"
                           value="<?= e(field($old, $school, 'principal_name')) ?>" maxlength="150">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="coach_name">Coach</label>
                    <input type="text" name="coach_name" id="coach_name" class="form-control"
                           value="<?= e(field($old, $school, 'coach_name')) ?>" maxlength="150">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="contact_email">Contact email</label>
                    <input type="email" name="contact_email" id="contact_email"
                           class="form-control<?= invalid_class($errors, 'contact_email') ?>"
                           value="<?= e(field($old, $school, 'contact_email')) ?>" maxlength="190">
                    <?= err($errors, 'contact_email') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="contact_phone">Contact phone</label>
                    <input type="text" name="contact_phone" id="contact_phone" class="form-control"
                           value="<?= e(field($old, $school, 'contact_phone')) ?>" maxlength="20">
                </div>
                <div class="col-12">
                    <label class="form-label" for="address">Address</label>
                    <textarea name="address" id="address" rows="2" class="form-control"><?= e(field($old, $school, 'address')) ?></textarea>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create school' ?></button>
                <a href="/admin/schools" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php if ($isEdit && !empty($logins)): ?>
    <div class="card panel border-0 shadow-sm mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 text-navy mb-0">Team logins</h2>
                <a href="/admin/school-logins/new?school_id=<?= (int)$school['school_id'] ?>&generate=1"
                   class="btn btn-sm btn-accent text-white">
                    <i class="bi bi-key me-1"></i> Generate login
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Username</th><th>Team</th><th>Status</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logins as $l): ?>
                            <tr>
                                <td><code><?= e($l['username']) ?></code></td>
                                <td><?= e($l['team_label'] ?? '—') ?></td>
                                <td><span class="<?= status_badge($l['status']) ?>"><?= e(status_label($l['status'])) ?></span></td>
                                <td class="text-end">
                                    <a href="/admin/school-logins/<?= (int)$l['school_login_id'] ?>/edit"
                                       class="btn btn-sm btn-outline-navy"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
