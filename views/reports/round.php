<?php
/** @var array $round */
/** @var array $results */
/** @var ?array $association */
/** @var string $generated_at */
$assocName = $association['name'] ?? 'Kerala Olympic Association';
$published = array_filter($results, static fn($r) => (int)$r['published'] === 1);
$qualified = array_filter($results, static fn($r) => (int)$r['qualified_next_round'] === 1);
$isR1      = (int)$round['round_number'] === 1;
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Round Results') ?></title>
    <link rel="stylesheet" href="/css/report.css">
</head>
<body>

<div class="report-toolbar no-print">
    <span class="title">
        Round <?= (int)$round['round_number'] ?> — <?= e($round['name']) ?>
    </span>
    <button type="button" onclick="window.print()">
        Print
    </button>
    <button type="button" class="secondary" onclick="window.close()">
        Close
    </button>
</div>

<div class="report-page">

    <div class="report-header">
        <div class="brand"><?= e($assocName) ?> · Olympics Run 2026</div>
        <h1>Round <?= (int)$round['round_number'] ?> Results — <?= e($round['name']) ?></h1>
        <p class="subtitle">
            Generated <?= e($generated_at) ?> ·
            <?= count($results) ?> submission(s)
            <?php if ($isR1 && count($qualified)): ?>
                · <?= count($qualified) ?> qualifier(s) for Round 2
            <?php endif; ?>
        </p>
    </div>

    <dl class="report-meta">
        <div>
            <dt>Slot duration</dt>
            <dd><?= (int)$round['slot_duration_minutes'] ?> min</dd>
        </div>
        <div>
            <dt>Quiz duration</dt>
            <dd><?= (int)$round['quiz_duration_minutes'] ?> min</dd>
        </div>
        <div>
            <dt>Questions</dt>
            <dd><?= (int)$round['questions_per_quiz'] ?></dd>
        </div>
        <div>
            <dt>Scoring</dt>
            <dd>
                +<?= rtrim(rtrim(number_format((float)$round['marks_correct'], 2), '0'), '.') ?> /
                <?= rtrim(rtrim(number_format((float)$round['marks_wrong'], 2), '0'), '.') ?> /
                <?= rtrim(rtrim(number_format((float)$round['marks_unanswered'], 2), '0'), '.') ?>
            </dd>
        </div>
        <div>
            <dt>Published</dt>
            <dd><?= count($published) ?> of <?= count($results) ?></dd>
        </div>
    </dl>

    <?php if (empty($results)): ?>
        <p class="muted">No submissions for this round yet.</p>
    <?php else: ?>
    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th class="num">Rank</th>
                    <th>School</th>
                    <th>Region</th>
                    <th>Slot</th>
                    <th class="num">Score</th>
                    <th class="num">Correct</th>
                    <th class="num">Wrong</th>
                    <th class="num">Skip</th>
                    <th class="num">Time</th>
                    <?php if ($isR1): ?><th class="center">R2</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $r):
                    $rank = (int)$r['rank_in_round'];
                    $rowClass = $rank >= 1 && $rank <= 3 ? 'rank-' . $rank : '';
                    $time = (int)($r['attempt_time'] ?? 0);
                    $tStr = sprintf('%d:%02d', floor($time / 60), $time % 60);
                ?>
                    <tr class="<?= e($rowClass) ?>">
                        <td class="num"><strong>#<?= $rank ?></strong></td>
                        <td>
                            <div class="school"><?= e($r['school_name']) ?></div>
                            <?php if (!empty($r['school_code'])): ?>
                                <div class="muted"><?= e($r['school_code']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($r['region'] ?? '—') ?></td>
                        <td class="muted"><?= e($r['slot_label'] ?? '—') ?></td>
                        <td class="num"><strong><?= rtrim(rtrim(number_format((float)$r['total_score'], 2), '0'), '.') ?></strong></td>
                        <td class="num"><?= (int)$r['correct_count'] ?></td>
                        <td class="num"><?= (int)$r['wrong_count'] ?></td>
                        <td class="num"><?= (int)$r['unanswered_count'] ?></td>
                        <td class="num"><?= $tStr ?></td>
                        <?php if ($isR1): ?>
                            <td class="center">
                                <?= (int)$r['qualified_next_round'] === 1 ? '<strong>Q</strong>' : '' ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="report-footer">
        <span>Olympics Run 2026 · <?= e($assocName) ?></span>
        <span>Generated <?= e($generated_at) ?></span>
    </div>
</div>

<script>
    // Optional: ?print=1 auto-launches the print dialog when the tab opens.
    if (new URLSearchParams(location.search).has('print')) {
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 250);
        });
    }
</script>

</body>
</html>
