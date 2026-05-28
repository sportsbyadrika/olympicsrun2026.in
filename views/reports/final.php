<?php
/** @var array $rounds */
/** @var array $rows */
/** @var ?array $association */
/** @var string $generated_at */
$assocName = $association['name'] ?? 'Kerala Olympic Association';
$hasR1 = $hasR2 = false;
foreach ($rounds as $r) {
    if ((int)$r['round_number'] === 1) $hasR1 = true;
    if ((int)$r['round_number'] === 2) $hasR2 = true;
}
$fmt = static fn($v) => $v !== null
    ? rtrim(rtrim(number_format((float)$v, 2), '0'), '.')
    : '—';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Final Consolidated Result') ?></title>
    <link rel="stylesheet" href="/css/report.css">
</head>
<body>

<div class="report-toolbar no-print">
    <span class="title">Olympics Run 2026 — Final Consolidated Result</span>
    <button type="button" onclick="window.print()">Print</button>
    <button type="button" class="secondary" onclick="window.close()">Close</button>
</div>

<div class="report-page">

    <div class="report-header">
        <div class="brand"><?= e($assocName) ?> · Olympics Run 2026</div>
        <h1>Final Consolidated Result</h1>
        <p class="subtitle">
            Combined Round 1 + Round 2 scores · <?= count($rows) ?> participant(s)
        </p>
    </div>

    <dl class="report-meta">
        <div>
            <dt>Generated</dt>
            <dd><?= e($generated_at) ?></dd>
        </div>
        <?php foreach ($rounds as $r): ?>
            <div>
                <dt>R<?= (int)$r['round_number'] ?> · <?= e($r['name']) ?></dt>
                <dd><?= (int)$r['questions_per_quiz'] ?> questions</dd>
            </div>
        <?php endforeach; ?>
    </dl>

    <?php if (empty($rows)): ?>
        <p class="muted">No submitted results yet.</p>
    <?php else: ?>
    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr>
                    <th class="num">Rank</th>
                    <th>School</th>
                    <th>Region</th>
                    <?php if ($hasR1): ?>
                        <th class="num">R1 score</th>
                        <th class="num">R1 rank</th>
                    <?php endif; ?>
                    <?php if ($hasR2): ?>
                        <th class="num">R2 score</th>
                        <th class="num">R2 rank</th>
                    <?php endif; ?>
                    <th class="num">Combined</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $i => $row):
                $rank = $i + 1;
                $rowClass = $rank >= 1 && $rank <= 3 ? 'rank-' . $rank : '';
            ?>
                <tr class="<?= e($rowClass) ?>">
                    <td class="num"><strong>#<?= $rank ?></strong></td>
                    <td>
                        <div class="school"><?= e($row['school_name']) ?></div>
                        <?php if (!empty($row['school_code'])): ?>
                            <div class="muted"><?= e($row['school_code']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($row['region'] ?? '—') ?></td>
                    <?php if ($hasR1): ?>
                        <td class="num">
                            <?= $fmt($row['r1_score']) ?>
                            <?php if ($row['r1_correct'] !== null): ?>
                                <div class="muted"><?= (int)$row['r1_correct'] ?>/<?= (int)$row['r1_total'] ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="num">
                            <?= $row['r1_rank'] ? '#' . (int)$row['r1_rank'] : '—' ?>
                            <?= (int)($row['r1_qualified'] ?? 0) === 1 ? ' <strong>Q</strong>' : '' ?>
                        </td>
                    <?php endif; ?>
                    <?php if ($hasR2): ?>
                        <td class="num">
                            <?= $fmt($row['r2_score']) ?>
                            <?php if ($row['r2_correct'] !== null): ?>
                                <div class="muted"><?= (int)$row['r2_correct'] ?>/<?= (int)$row['r2_total'] ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="num"><?= $row['r2_rank'] ? '#' . (int)$row['r2_rank'] : '—' ?></td>
                    <?php endif; ?>
                    <td class="num"><strong><?= $fmt($row['combined_score']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="<?= 3 + ($hasR1 ? 2 : 0) + ($hasR2 ? 2 : 0) + 1 ?>"
                        class="muted" style="border-bottom:0; padding-top:0.75rem">
                        Tie-break: combined R1 + R2 time taken (lower is better).
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <div class="report-footer">
        <span>Olympics Run 2026 · <?= e($assocName) ?></span>
        <span>Generated <?= e($generated_at) ?></span>
    </div>
</div>

<script>
    if (new URLSearchParams(location.search).has('print')) {
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 250);
        });
    }
</script>

</body>
</html>
