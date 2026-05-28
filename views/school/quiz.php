<?php
/** @var array $attempt */
/** @var array $questions */
/** @var array $responses */     // keyed by slot_question_id
/** @var int   $remaining */

/* Decide each question's initial side-panel state. */
$stateOf = static function (int $sqid) use ($responses): string {
    if (!isset($responses[$sqid])) return 'unanswered';
    $row = $responses[$sqid];
    if ($row['status'] === 'submitted') return 'submitted';
    if (!empty($row['chosen_option']))  return 'answered';
    return 'draft';
};
?>
<div class="quiz-shell">

    <!-- Header bar (sticky) -->
    <div class="quiz-bar d-flex align-items-center gap-2 px-2 py-2">
        <button class="btn btn-sm btn-outline-light d-lg-none" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#qNav"
                aria-controls="qNav" aria-label="Open question list">
            <i class="bi bi-list-task"></i>
        </button>
        <div class="me-auto small">
            <div class="fw-semibold"><?= e($attempt['slot_label'] ?? '') ?></div>
            <div class="opacity-75">
                R<?= (int)$attempt['round_number'] ?> · Q<span id="qCurr">1</span>
                / <span id="qTotal"><?= count($questions) ?></span>
            </div>
        </div>
        <div class="text-end">
            <div class="small opacity-75">Time left</div>
            <div class="quiz-timer fw-bold fs-4" id="qTimer">--:--</div>
        </div>
    </div>

    <div class="row g-0 quiz-body">

        <!-- Side panel: visible on lg+, offcanvas on mobile -->
        <aside class="col-lg-3 d-none d-lg-block quiz-side">
            <?php include __DIR__ . '/_quiz_nav.php'; ?>
        </aside>
        <div class="offcanvas offcanvas-start d-lg-none quiz-side" tabindex="-1" id="qNav">
            <div class="offcanvas-header py-2">
                <h5 class="offcanvas-title text-navy mb-0">Questions</h5>
                <button class="btn-close" data-bs-dismiss="offcanvas"></button>
            </div>
            <div class="offcanvas-body p-2">
                <?php include __DIR__ . '/_quiz_nav.php'; ?>
            </div>
        </div>

        <!-- Question pane -->
        <main class="col-lg-9 quiz-main">
            <?php foreach ($questions as $i => $q):
                $sqid = (int)$q['slot_question_id'];
                $chosen = $responses[$sqid]['chosen_option'] ?? null; ?>
                <section class="quiz-question <?= $i === 0 ? '' : 'd-none' ?>"
                         data-q-idx="<?= $i ?>"
                         data-sqid="<?= $sqid ?>">
                    <div class="small text-muted mb-2">Question <?= $i + 1 ?> of <?= count($questions) ?></div>
                    <h2 class="h5 text-navy mb-3"><?= e($q['question_text']) ?></h2>

                    <div class="quiz-options d-grid gap-2">
                        <?php foreach (['A', 'B', 'C', 'D'] as $L):
                            $opt = $q['option_' . strtolower($L)];
                            $checked = $chosen === $L; ?>
                            <label class="quiz-option <?= $checked ? 'active' : '' ?>">
                                <input type="radio"
                                       name="ans-<?= $sqid ?>"
                                       value="<?= $L ?>"
                                       class="quiz-radio"
                                       data-sqid="<?= $sqid ?>"
                                       <?= $checked ? 'checked' : '' ?>>
                                <span class="quiz-option-letter"><?= $L ?></span>
                                <span class="quiz-option-text"><?= e($opt) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <!-- Quiz nav buttons -->
            <div class="quiz-actions d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                <button type="button" class="btn btn-outline-secondary" id="btnBack">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnSaveDraft">
                    <i class="bi bi-bookmark me-1"></i> Save draft
                </button>
                <button type="button" class="btn btn-primary ms-auto" id="btnNext">
                    Next <i class="bi bi-arrow-right ms-1"></i>
                </button>
            </div>

            <div class="text-end mt-4">
                <button type="button" class="btn btn-accent text-white" id="btnFinish" disabled
                        title="Submission goes live in the next phase">
                    <i class="bi bi-check2-circle me-1"></i> Finish quiz
                </button>
            </div>
        </main>
    </div>

    <!-- Time's up overlay -->
    <div id="timeUpOverlay" class="quiz-overlay d-none">
        <div class="card panel border-0 shadow-lg text-center p-4 mx-3">
            <h3 class="text-danger mb-2"><i class="bi bi-hourglass-bottom"></i> Time's up</h3>
            <p class="text-muted mb-1">Your answers have been saved.</p>
            <p class="small text-muted mb-0">Auto-submission lands in the next phase.</p>
        </div>
    </div>
</div>

<style>
.quiz-shell { background: var(--or-bg); min-height: calc(100vh - 56px); }
.quiz-bar   { background: var(--or-navy); color: #fff; position: sticky; top: 0; z-index: 1020; }
.quiz-timer.warning { color: #ffd54f; }
.quiz-timer.danger  { color: #ff8a80; animation: pulse 1s infinite; }
@keyframes pulse { 50% { opacity: 0.55; } }

.quiz-body  { min-height: calc(100vh - 56px - 56px); }
.quiz-side  { background: var(--or-panel); border-right: 1px solid var(--or-panel-dark); }
.quiz-side .qnav-grid { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 0.35rem; }
.quiz-side .qnav-btn {
    border: 1px solid var(--or-panel-dark);
    background: #fff;
    border-radius: 0.5rem;
    padding: 0.5rem 0;
    font-weight: 600;
    color: var(--or-text);
    transition: background 100ms, color 100ms, border-color 100ms;
    cursor: pointer;
}
.quiz-side .qnav-btn[data-state="answered"]  { background: var(--or-teal);    color: #fff; border-color: var(--or-teal); }
.quiz-side .qnav-btn[data-state="draft"]     { background: #fff3cd;            color: #856404; border-color: #ffeeba; }
.quiz-side .qnav-btn[data-state="submitted"] { background: var(--or-navy);    color: #fff; border-color: var(--or-navy); }
.quiz-side .qnav-btn.current                  { outline: 3px solid rgba(0,137,123,.4); }
.quiz-side .qnav-legend { font-size: 0.75rem; color: var(--or-muted); }
.quiz-side .qnav-legend span.chip { display: inline-block; width: 0.85rem; height: 0.85rem; border-radius: 0.2rem; vertical-align: -0.05rem; margin-right: 0.25rem; }

.quiz-main { padding: 1rem; }
@media (min-width: 992px) { .quiz-main { padding: 1.5rem 2rem; } }

.quiz-option {
    display: flex; align-items: flex-start; gap: 0.75rem;
    padding: 0.75rem 1rem;
    border: 1px solid var(--or-panel-dark);
    border-radius: 0.6rem;
    background: #fff;
    cursor: pointer;
    transition: border-color 80ms, background 80ms;
}
.quiz-option:hover  { border-color: var(--or-teal-300); }
.quiz-option.active { border-color: var(--or-teal); background: rgba(0,137,123,.07); }
.quiz-option input[type="radio"] { display: none; }
.quiz-option-letter {
    flex-shrink: 0; width: 1.6rem; height: 1.6rem;
    border-radius: 50%; background: var(--or-panel);
    color: var(--or-navy); font-weight: 700;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
}
.quiz-option.active .quiz-option-letter { background: var(--or-teal); color: #fff; }

.quiz-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.45);
    display: flex; align-items: center; justify-content: center;
    z-index: 2000;
}
</style>

<script>
(function () {
    var ATTEMPT_ID = <?= (int)$attempt['slot_school_id'] ?>;
    var TOTAL = <?= count($questions) ?>;
    var csrf  = document.querySelector('meta[name="csrf-token"]').content;

    // Server-truth remaining seconds; clock ticks locally between syncs.
    var remaining = <?= (int)$remaining ?>;
    var syncing   = false;
    var locked    = false;

    var $timer    = document.getElementById('qTimer');
    var $qCurr    = document.getElementById('qCurr');
    var $overlay  = document.getElementById('timeUpOverlay');

    // ---------- Question navigation ----------
    var current = 0;
    var sections = Array.from(document.querySelectorAll('.quiz-question'));

    function showQuestion(idx) {
        if (idx < 0) idx = 0;
        if (idx >= TOTAL) idx = TOTAL - 1;
        sections.forEach(function (s, i) { s.classList.toggle('d-none', i !== idx); });
        current = idx;
        $qCurr.textContent = idx + 1;
        document.querySelectorAll('.qnav-btn').forEach(function (b) {
            b.classList.toggle('current', parseInt(b.dataset.qIdx, 10) === idx);
        });
        // Close drawer on mobile after tap.
        var oc = document.getElementById('qNav');
        if (oc && oc.classList.contains('show') && window.bootstrap) {
            var inst = bootstrap.Offcanvas.getInstance(oc);
            inst && inst.hide();
        }
        document.getElementById('btnBack').disabled = (idx === 0);
        var $next = document.getElementById('btnNext');
        $next.disabled = (idx === TOTAL - 1);
    }

    document.getElementById('btnBack').addEventListener('click', function () { showQuestion(current - 1); });
    document.getElementById('btnNext').addEventListener('click', function () { showQuestion(current + 1); });
    document.getElementById('btnSaveDraft').addEventListener('click', function () {
        // If nothing selected, save explicit null draft for the current question.
        var sec = sections[current];
        var sqid = parseInt(sec.dataset.sqid, 10);
        var picked = sec.querySelector('.quiz-radio:checked');
        saveAnswer(sqid, picked ? picked.value : null, true);
    });

    document.querySelectorAll('.qnav-btn').forEach(function (b) {
        b.addEventListener('click', function () {
            showQuestion(parseInt(b.dataset.qIdx, 10));
        });
    });

    // ---------- Auto-save on selection ----------
    document.querySelectorAll('.quiz-radio').forEach(function (r) {
        r.addEventListener('change', function () {
            // Visual: highlight selected option, clear others in the same group.
            var name = r.name;
            document.querySelectorAll('input[name="' + name + '"]')
                .forEach(function (rr) { rr.closest('.quiz-option').classList.toggle('active', rr.checked); });
            saveAnswer(parseInt(r.dataset.sqid, 10), r.value, false);
        });
    });

    function setNavState(sqid, state) {
        var btn = document.querySelector('.qnav-btn[data-sqid="' + sqid + '"]');
        if (btn) btn.dataset.state = state;
    }

    function saveAnswer(sqid, chosen, isExplicitDraft) {
        if (locked) return;
        var body = new URLSearchParams();
        body.append('slot_question_id', sqid);
        if (chosen)             body.append('chosen_option', chosen);
        else                    body.append('chosen_option', '_clear');

        return fetch('/api/school/quiz/answer', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': csrf,
                       'Content-Type': 'application/x-www-form-urlencoded',
                       'Accept': 'application/json' },
            body: body.toString()
        }).then(function (r) { return r.json(); })
          .then(function (json) {
              if (!json.ok) {
                  if (json.error === 'time_up' || json.error === 'attempt_not_active') {
                      lockUI();
                  }
                  return;
              }
              if (typeof json.remaining_seconds === 'number') {
                  remaining = json.remaining_seconds;
                  renderTimer();
              }
              setNavState(sqid, chosen ? 'answered' : 'draft');
          })
          .catch(function () { /* swallow, will retry on next interaction */ });
    }

    // ---------- Timer ----------
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function renderTimer() {
        var mm = Math.floor(remaining / 60);
        var ss = remaining % 60;
        $timer.textContent = pad(mm) + ':' + pad(ss);
        $timer.classList.toggle('danger',  remaining <= 60);
        $timer.classList.toggle('warning', remaining > 60 && remaining <= 180);
    }

    function lockUI() {
        if (locked) return;
        locked = true;
        document.querySelectorAll('.quiz-radio').forEach(function (r) { r.disabled = true; });
        document.querySelectorAll('.quiz-actions button').forEach(function (b) { b.disabled = true; });
        $overlay.classList.remove('d-none');
    }

    function tick() {
        if (locked) return;
        remaining = Math.max(0, remaining - 1);
        renderTimer();
        if (remaining === 0) lockUI();
    }
    setInterval(tick, 1000);

    function sync() {
        if (syncing || locked) return;
        syncing = true;
        fetch('/api/school/quiz/state', {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (r) { return r.json(); })
          .then(function (json) {
              if (!json.ok) return;
              if (json.attempt_status === 'submitted') {
                  window.location.href = '/school/dashboard';
                  return;
              }
              if (typeof json.remaining_seconds === 'number') {
                  remaining = json.remaining_seconds;
                  renderTimer();
                  if (remaining === 0) lockUI();
              }
          })
          .catch(function () { /* ignore */ })
          .finally(function () { syncing = false; });
    }
    setInterval(sync, 10000);

    renderTimer();
    showQuestion(0);
    if (remaining === 0) lockUI();
})();
</script>
