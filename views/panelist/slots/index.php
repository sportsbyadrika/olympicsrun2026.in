<?php /** @var array $slots */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Slot Builder</h1>
    <span class="text-muted small">Pick a slot to drag master questions into it.</span>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Slot</th>
                    <th class="d-none d-md-table-cell">Round</th>
                    <th class="d-none d-md-table-cell">Window</th>
                    <th>Questions</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($slots)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No slots yet for your association.</td></tr>
                <?php else: foreach ($slots as $s):
                    $target = (int)$s['questions_per_quiz'];
                    $count  = (int)$s['question_count'];
                    $pct = $target > 0 ? min(100, (int)round($count / $target * 100)) : 0;
                    $isOverflow = $count > $target; ?>
                    <tr>
                        <td>
                            <a href="/panelist/slots/<?= (int)$s['slot_id'] ?>" class="fw-semibold text-navy text-decoration-none">
                                <?= e($s['slot_label'] ?? '#' . $s['slot_id']) ?>
                            </a>
                            <div class="small text-muted d-md-none">
                                R<?= (int)$s['round_number'] ?> · <?= e(dt_display($s['starts_at'], 'd M, H:i')) ?>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            R<?= (int)$s['round_number'] ?> — <?= e($s['round_name']) ?>
                        </td>
                        <td class="d-none d-md-table-cell small text-muted">
                            <?= e(dt_display($s['starts_at'], 'd M, H:i')) ?>
                        </td>
                        <td style="min-width:140px">
                            <div class="small mb-1 <?= $isOverflow ? 'text-danger fw-semibold' : '' ?>">
                                <?= $count ?> / <?= $target ?>
                            </div>
                            <div class="progress" style="height:6px">
                                <div class="progress-bar <?= $isOverflow ? 'bg-danger' : 'bg-accent' ?>"
                                     style="width: <?= $pct ?>%"></div>
                            </div>
                        </td>
                        <td><span class="<?= status_badge($s['status']) ?>"><?= e(status_label($s['status'])) ?></span></td>
                        <td class="text-end">
                            <a href="/panelist/slots/<?= (int)$s['slot_id'] ?>"
                               class="btn btn-sm btn-accent text-white">
                                <i class="bi bi-arrows-move me-1"></i> Build
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>.bg-accent{background-color:var(--or-teal)!important;}</style>
