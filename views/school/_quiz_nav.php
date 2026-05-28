<?php
/** @var array $questions */
/** @var array $responses */
/** @var callable $stateOf */
?>
<div class="p-2">
    <div class="qnav-grid mb-3">
        <?php foreach ($questions as $i => $q):
            $sqid = (int)$q['slot_question_id'];
            $state = $stateOf($sqid); ?>
            <button type="button" class="qnav-btn"
                    data-q-idx="<?= $i ?>"
                    data-sqid="<?= $sqid ?>"
                    data-state="<?= e($state) ?>"
                    title="Question <?= $i + 1 ?>: <?= e(ucfirst($state)) ?>">
                <?= $i + 1 ?>
            </button>
        <?php endforeach; ?>
    </div>
    <div class="qnav-legend">
        <div class="mb-1"><span class="chip" style="background:#fff;border:1px solid var(--or-panel-dark)"></span> Unanswered</div>
        <div class="mb-1"><span class="chip" style="background:#fff3cd;border:1px solid #ffeeba"></span> Draft</div>
        <div class="mb-1"><span class="chip" style="background:var(--or-teal)"></span> Answered</div>
    </div>
</div>
