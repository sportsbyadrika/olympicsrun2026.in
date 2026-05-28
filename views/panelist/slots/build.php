<?php
/** @var array $slot */
/** @var array $bank */
/** @var array $assigned */
/** @var array $filters */
/** @var array $sports */

$target = (int)$slot['questions_per_quiz'];
$count  = count($assigned);

// Reusable card template, rendered server-side for both lists.
$cardHtml = static function (array $q): string {
    $diff   = htmlspecialchars(ucfirst($q['difficulty']), ENT_QUOTES);
    $sport  = $q['sport'] ? htmlspecialchars($q['sport'], ENT_QUOTES) : '';
    $text   = htmlspecialchars(mb_strimwidth($q['question_text'], 0, 160, '…'), ENT_QUOTES);
    $correct= htmlspecialchars($q['correct_option'], ENT_QUOTES);
    return <<<HTML
<div class="q-card card panel border-0 shadow-sm mb-2"
     data-qid="{$q['master_question_id']}">
    <div class="card-body p-2 d-flex align-items-start gap-2">
        <span class="drag-handle text-muted" aria-hidden="true" title="Drag">
            <i class="bi bi-grip-vertical"></i>
        </span>
        <div class="flex-grow-1 small">
            <div class="text-navy fw-semibold lh-sm">{$text}</div>
            <div class="text-muted mt-1">
                <span class="badge bg-light text-dark border">{$diff}</span>
HTML
    . ($sport ? '<span class="badge bg-light text-dark border ms-1">' . $sport . '</span>' : '')
    . "<span class=\"badge bg-light text-dark border ms-1\">✓ {$correct}</span>"
    . '</div></div></div></div>';
};
?>
<a href="/panelist/slots" class="btn btn-sm btn-outline-secondary mb-2">
    <i class="bi bi-arrow-left me-1"></i> All slots
</a>

<div class="d-md-flex justify-content-between align-items-end mb-3">
    <div>
        <h1 class="h4 text-navy mb-1"><?= e($slot['slot_label'] ?? '#' . (int)$slot['slot_id']) ?></h1>
        <p class="text-muted small mb-0">
            <?= e($slot['association_name']) ?> ·
            R<?= (int)$slot['round_number'] ?> <?= e($slot['round_name']) ?> ·
            <?= e(dt_display($slot['starts_at'], 'd M, H:i')) ?>
        </p>
    </div>
    <div class="mt-2 mt-md-0 text-md-end">
        <div class="small text-muted">Question target</div>
        <div class="fs-3 fw-bold <?= $count > $target ? 'text-danger' : 'text-navy' ?>"
             id="qCount"><?= $count ?> / <?= $target ?></div>
        <div class="small text-muted" id="qSavedState">
            <i class="bi bi-cloud-check me-1"></i> Saved
        </div>
    </div>
</div>

<div class="row g-3" id="sb">
    <!-- LEFT: Bank -->
    <div class="col-12 col-lg-6 order-2 order-lg-1">
        <div class="card panel border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 text-uppercase text-muted mb-0">Master bank</h2>
                    <span class="badge bg-light text-dark border"><?= count($bank) ?></span>
                </div>

                <form method="get" class="row g-2 mb-3">
                    <div class="col-6 col-md-4">
                        <select name="difficulty" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Any level</option>
                            <?php foreach (MasterQuestion::DIFFICULTIES as $d): ?>
                                <option value="<?= e($d) ?>" <?= ($filters['difficulty'] ?? '') === $d ? 'selected' : '' ?>>
                                    <?= e(ucfirst($d)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <select name="sport" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Any sport</option>
                            <?php foreach ($sports as $sp): ?>
                                <option value="<?= e($sp) ?>" <?= ($filters['sport'] ?? '') === $sp ? 'selected' : '' ?>>
                                    <?= e($sp) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <select name="round" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Any round</option>
                            <option value="1" <?= ($filters['round'] ?? '') === '1' ? 'selected' : '' ?>>R1 intended</option>
                            <option value="2" <?= ($filters['round'] ?? '') === '2' ? 'selected' : '' ?>>R2 intended</option>
                        </select>
                    </div>
                </form>

                <div id="bankList" class="sb-list">
                    <?php foreach ($bank as $q) echo $cardHtml($q); ?>
                    <?php if (empty($bank)): ?>
                        <p class="text-muted small mb-0 py-4 text-center">
                            No matching questions. Adjust filters or add to the
                            <a href="/panelist/master/new" class="link-accent">master bank</a>.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT: Slot -->
    <div class="col-12 col-lg-6 order-1 order-lg-2">
        <div class="card panel border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 text-uppercase text-muted mb-0">In this slot</h2>
                    <span class="badge bg-navy text-white" id="qBadge"><?= $count ?></span>
                </div>
                <p class="small text-muted d-lg-none mb-2">
                    <i class="bi bi-info-circle me-1"></i>
                    Tip: tap and hold the <i class="bi bi-grip-vertical"></i> handle to drag.
                </p>
                <div id="slotList" class="sb-list sb-drop">
                    <?php foreach ($assigned as $q) echo $cardHtml($q); ?>
                </div>
                <p class="text-muted small mb-0 py-3 text-center sb-empty"
                   id="slotEmpty" style="<?= $count > 0 ? 'display:none' : '' ?>">
                    <i class="bi bi-inbox me-1"></i>
                    Drop questions here.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.sb-list {
    min-height: 140px;
    max-height: 60vh;
    overflow-y: auto;
    padding: 0.25rem;
    border-radius: 0.5rem;
    background-color: rgba(0,0,0,0.02);
}
.sb-drop      { border: 2px dashed transparent; transition: border-color 120ms ease; }
.sb-drop.over { border-color: var(--or-teal); background-color: rgba(0,137,123,.04); }
.q-card { cursor: grab; touch-action: none; }
.q-card:active { cursor: grabbing; }
.drag-handle { cursor: grab; padding-top: 0.15rem; font-size: 1.1rem; }
.sortable-ghost  { opacity: 0.35; }
.sortable-chosen { box-shadow: 0 0 0 2px var(--or-teal); }
@media (max-width: 991.98px) {
    .sb-list { max-height: 50vh; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
    var SLOT_ID = <?= (int)$slot['slot_id'] ?>;
    var TARGET  = <?= (int)$slot['questions_per_quiz'] ?>;
    var bankEl  = document.getElementById('bankList');
    var slotEl  = document.getElementById('slotList');
    var $count  = document.getElementById('qCount');
    var $badge  = document.getElementById('qBadge');
    var $empty  = document.getElementById('slotEmpty');
    var $saved  = document.getElementById('qSavedState');
    var csrf    = document.querySelector('meta[name="csrf-token"]').content;

    function setSavingState(state) {
        if (state === 'saving') {
            $saved.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Saving…';
            $saved.className = 'small text-muted';
        } else if (state === 'error') {
            $saved.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Sync failed';
            $saved.className = 'small text-danger';
        } else {
            $saved.innerHTML = '<i class="bi bi-cloud-check me-1"></i> Saved';
            $saved.className = 'small text-muted';
        }
    }

    function refreshCounter(count) {
        $count.textContent = count + ' / ' + TARGET;
        $badge.textContent = count;
        $count.classList.toggle('text-danger', count > TARGET);
        $count.classList.toggle('text-navy',   count <= TARGET);
        $empty.style.display = count > 0 ? 'none' : '';
    }

    function slotOrder() {
        return Array.from(slotEl.children)
            .filter(function (n) { return n.dataset && n.dataset.qid; })
            .map(function (n) { return n.dataset.qid; });
    }

    function api(path, body) {
        setSavingState('saving');
        var form = new URLSearchParams();
        Object.keys(body).forEach(function (k) {
            var v = body[k];
            if (Array.isArray(v)) v.forEach(function (item) { form.append(k + '[]', item); });
            else form.append(k, v);
        });
        return fetch(path, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': csrf,
                       'Content-Type': 'application/x-www-form-urlencoded',
                       'Accept': 'application/json' },
            body: form.toString()
        }).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        }).then(function (json) {
            if (!json.ok) throw new Error(json.error || 'API error');
            refreshCounter(json.count);
            setSavingState('idle');
            return json;
        }).catch(function (err) {
            console.error(err);
            setSavingState('error');
            throw err;
        });
    }

    var assignUrl   = '/api/panelist/slot-questions/assign';
    var unassignUrl = '/api/panelist/slot-questions/unassign';
    var reorderUrl  = '/api/panelist/slot-questions/reorder';

    // Touch-friendly options: short delay before drag starts on touch, so a
    // quick scroll doesn't initiate a drag.
    var common = {
        group: 'slot-builder',
        animation: 150,
        delay: 120,
        delayOnTouchOnly: true,
        touchStartThreshold: 5,
        handle: '.drag-handle',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
    };

    Sortable.create(bankEl, Object.assign({}, common, {
        onAdd: function (evt) {
            // Came back from slot — unassign
            var qid = evt.item.dataset.qid;
            api(unassignUrl, { slot_id: SLOT_ID, master_question_id: qid })
                .catch(function () { evt.from.appendChild(evt.item); });
        },
    }));

    Sortable.create(slotEl, Object.assign({}, common, {
        onAdd: function (evt) {
            // Came from bank — assign then reorder
            var qid = evt.item.dataset.qid;
            api(assignUrl, { slot_id: SLOT_ID, master_question_id: qid })
                .then(function () {
                    return api(reorderUrl, { slot_id: SLOT_ID, master_question_ids: slotOrder() });
                })
                .catch(function () { evt.from.appendChild(evt.item); });
        },
        onUpdate: function () {
            api(reorderUrl, { slot_id: SLOT_ID, master_question_ids: slotOrder() });
        },
    }));

    // Visual feedback for the drop zone
    ['dragover', 'dragenter'].forEach(function (ev) {
        slotEl.addEventListener(ev, function () { slotEl.classList.add('over'); });
    });
    ['dragleave', 'drop'].forEach(function (ev) {
        slotEl.addEventListener(ev, function () { slotEl.classList.remove('over'); });
    });

    refreshCounter(<?= $count ?>);
})();
</script>
