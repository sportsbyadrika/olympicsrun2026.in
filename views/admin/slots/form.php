<?php
/** @var ?array $slot */
/** @var array $rounds */
/** @var array $statuses */
$old    = pull_old();
$errors = pull_errors();
$isEdit = !empty($slot);
$action = $isEdit ? '/admin/slots/' . (int)$slot['slot_id'] : '/admin/slots';

// Build round meta map for client-side default ends_at calc.
$roundMeta = [];
foreach ($rounds as $r) {
    $roundMeta[(int)$r['round_id']] = [
        'slot' => (int)$r['slot_duration_minutes'],
        'quiz' => (int)$r['quiz_duration_minutes'],
        'qs'   => (int)$r['questions_per_quiz'],
    ];
}
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-0"><?= $isEdit ? 'Edit' : 'Add' ?> Slot</h1>
    <a href="/admin/slots" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e($action) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="round_id">Round *</label>
                    <select name="round_id" id="round_id"
                            class="form-select<?= invalid_class($errors, 'round_id') ?>" required>
                        <option value="">— Select —</option>
                        <?php foreach ($rounds as $r):
                            $sel = (string)field($old, $slot, 'round_id') === (string)$r['round_id']; ?>
                            <option value="<?= (int)$r['round_id'] ?>" <?= $sel ? 'selected' : '' ?>>
                                <?= e($r['association_name']) ?> · R<?= (int)$r['round_number'] ?> — <?= e($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text" id="roundMeta">Slot &amp; quiz durations are inherited from the round.</div>
                    <?= err($errors, 'round_id') ?>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="slot_label">Slot label</label>
                    <input type="text" name="slot_label" id="slot_label"
                           class="form-control<?= invalid_class($errors, 'slot_label') ?>"
                           value="<?= e(field($old, $slot, 'slot_label')) ?>" maxlength="100"
                           placeholder="e.g. R1 Slot A — Morning">
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="starts_at">Starts at *</label>
                    <input type="datetime-local" name="starts_at" id="starts_at"
                           class="form-control<?= invalid_class($errors, 'starts_at') ?>"
                           value="<?= e(dt_for_input(field($old, $slot, 'starts_at'))) ?>" required>
                    <?= err($errors, 'starts_at') ?>
                </div>
                <div class="col-12 col-md-5">
                    <label class="form-label" for="ends_at">Ends at *</label>
                    <input type="datetime-local" name="ends_at" id="ends_at"
                           class="form-control<?= invalid_class($errors, 'ends_at') ?>"
                           value="<?= e(dt_for_input(field($old, $slot, 'ends_at'))) ?>" required>
                    <?= err($errors, 'ends_at') ?>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="capacity">Capacity *</label>
                    <input type="number" min="1" name="capacity" id="capacity"
                           class="form-control<?= invalid_class($errors, 'capacity') ?>"
                           value="<?= e(field($old, $slot, 'capacity', '50')) ?>" required>
                    <?= err($errors, 'capacity') ?>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select">
                        <?php $cur = field($old, $slot, 'status', 'scheduled'); foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>" <?= $cur === $s ? 'selected' : '' ?>>
                                <?= e(status_label($s)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Create slot' ?></button>
                <a href="/admin/slots" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var meta = <?= json_encode($roundMeta, JSON_UNESCAPED_SLASHES) ?>;
    var $round   = document.getElementById('round_id');
    var $starts  = document.getElementById('starts_at');
    var $ends    = document.getElementById('ends_at');
    var $metaTxt = document.getElementById('roundMeta');

    function refreshMeta() {
        var m = meta[$round.value];
        if (!m) { $metaTxt.textContent = 'Slot & quiz durations are inherited from the round.'; return; }
        $metaTxt.textContent = m.slot + ' min slot · ' + m.quiz + ' min quiz · ' + m.qs + ' questions';
    }
    function autoFillEnd() {
        var m = meta[$round.value];
        if (!m || !$starts.value) return;
        var d = new Date($starts.value);
        if (isNaN(d)) return;
        d.setMinutes(d.getMinutes() + m.slot);
        var pad = function (n) { return (n < 10 ? '0' : '') + n; };
        $ends.value = d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate())
                    + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }
    $round.addEventListener('change', function () { refreshMeta(); if (!$ends.value) autoFillEnd(); });
    $starts.addEventListener('change', function () { if (!$ends.value) autoFillEnd(); });
    refreshMeta();
})();
</script>
