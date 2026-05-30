<?php
/** @var array $settings */
$errors = pull_errors();

// Friendly grouping by key prefix — purely presentational.
$groupFor = static function (string $key): string {
    if (str_starts_with($key, 'mail_'))            return 'Email / SMTP';
    if (str_starts_with($key, 'r1_')
        || str_starts_with($key, 'r2_')
        || str_starts_with($key, 'registration_')) return 'Rounds & Windows';
    if (str_starts_with($key, 'marks_'))           return 'Scoring';
    if (in_array($key, ['slot_duration_minutes', 'quiz_duration_minutes',
                        'questions_per_quiz', 'slot_grace_minutes'], true)) return 'Timing';
    if (str_starts_with($key, 'result_'))          return 'Results';
    return 'Branding & Display';
};

$grouped = [];
foreach ($settings as $row) {
    $grouped[$groupFor($row['setting_key'])][] = $row;
}
ksort($grouped);

$label = static fn(string $key): string =>
    ucwords(str_replace('_', ' ', $key));
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-0">Settings</h1>
        <p class="text-muted small mb-0">Global configuration read by the app at runtime.</p>
    </div>
</div>

<?php if (empty($settings)): ?>
    <div class="card panel border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-sliders display-5"></i>
            <p class="mb-0 mt-2">No settings found. Import the seed data to populate defaults.</p>
        </div>
    </div>
<?php else: ?>
<form method="post" action="/admin/settings" novalidate>
    <?= csrf_field() ?>

    <?php foreach ($grouped as $group => $rows): ?>
        <div class="card panel border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold text-navy"><?= e($group) ?></div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($rows as $row):
                        $key  = $row['setting_key'];
                        $type = $row['value_type'];
                        $val  = $row['setting_value'];
                        $name = 'settings[' . $key . ']';
                    ?>
                        <div class="col-12 col-md-6">
                            <label class="form-label mb-1" for="set-<?= e($key) ?>">
                                <?= e($label($key)) ?>
                                <code class="small text-muted ms-1"><?= e($key) ?></code>
                            </label>

                            <?php if ($type === 'bool'): ?>
                                <div class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input"
                                           role="switch"
                                           id="set-<?= e($key) ?>"
                                           name="<?= e($name) ?>" value="true"
                                           <?= Settings::bool($key) ? 'checked' : '' ?>>
                                    <label class="form-check-label small text-muted"
                                           for="set-<?= e($key) ?>">
                                        <?= e($row['description'] ?? '') ?>
                                    </label>
                                </div>
                            <?php else: ?>
                                <input
                                    type="<?= $type === 'int' || $type === 'float' ? 'number' : ($type === 'datetime' ? 'datetime-local' : 'text') ?>"
                                    <?= $type === 'float' ? 'step="0.01"' : '' ?>
                                    class="form-control<?= invalid_class($errors, $key) ?>"
                                    id="set-<?= e($key) ?>"
                                    name="<?= e($name) ?>"
                                    value="<?= e($type === 'datetime' ? dt_for_input($val) : (string)$val) ?>">
                                <?php if (!empty($row['description'])): ?>
                                    <div class="form-text small"><?= e($row['description']) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="d-flex gap-2 mb-4">
        <button class="btn btn-primary">Save settings</button>
        <a href="/admin/dashboard" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>
<?php endif; ?>
