<?php /** @var array $slots */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Slots</h1>
    <a href="/admin/slots/new" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add slot
    </a>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Slot</th>
                    <th class="d-none d-md-table-cell">Round</th>
                    <th>Window</th>
                    <th>Assigned</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($slots)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No slots yet.</td></tr>
                <?php else: foreach ($slots as $s):
                    $cap = (int)$s['capacity']; $used = (int)$s['assigned_count'];
                    $pct = $cap > 0 ? min(100, (int)round($used / $cap * 100)) : 0; ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-navy"><?= e($s['slot_label'] ?? '—') ?></div>
                            <div class="small text-muted d-md-none">
                                <?= e($s['association_name']) ?> · R<?= (int)$s['round_number'] ?>
                            </div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div>R<?= (int)$s['round_number'] ?> — <?= e($s['round_name']) ?></div>
                            <div class="small text-muted"><?= e($s['association_name']) ?></div>
                        </td>
                        <td class="small">
                            <?= e(dt_display($s['starts_at'], 'd M, H:i')) ?><br>
                            <span class="text-muted">– <?= e(dt_display($s['ends_at'], 'H:i')) ?></span>
                        </td>
                        <td style="min-width:120px">
                            <div class="small mb-1"><?= $used ?> / <?= $cap ?></div>
                            <div class="progress" style="height:6px">
                                <div class="progress-bar bg-accent" role="progressbar"
                                     style="width: <?= $pct ?>%"
                                     aria-valuenow="<?= $used ?>" aria-valuemin="0" aria-valuemax="<?= $cap ?>"></div>
                            </div>
                        </td>
                        <td><span class="<?= status_badge($s['status']) ?>"><?= e(status_label($s['status'])) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="/admin/slots/<?= (int)$s['slot_id'] ?>/assign"
                               class="btn btn-sm btn-accent text-white" title="Assign schools">
                                <i class="bi bi-people"></i>
                            </a>
                            <a href="/admin/slots/<?= (int)$s['slot_id'] ?>/edit"
                               class="btn btn-sm btn-outline-navy"><i class="bi bi-pencil"></i></a>
                            <form action="/admin/slots/<?= (int)$s['slot_id'] ?>/delete"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this slot?');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>.bg-accent{background-color:var(--or-teal)!important;}</style>
