<?php /** @var array $rounds */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Rounds</h1>
    <a href="/admin/rounds/new" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add round
    </a>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Round</th>
                    <th class="d-none d-md-table-cell">Association</th>
                    <th class="d-none d-lg-table-cell">Slot / Quiz</th>
                    <th class="d-none d-lg-table-cell">Qs</th>
                    <th class="d-none d-md-table-cell">Window</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rounds)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No rounds yet.</td></tr>
                <?php else: foreach ($rounds as $r): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-navy">
                                R<?= (int)$r['round_number'] ?> — <?= e($r['name']) ?>
                            </div>
                            <div class="small text-muted d-md-none"><?= e($r['association_name']) ?></div>
                        </td>
                        <td class="d-none d-md-table-cell"><?= e($r['association_name']) ?></td>
                        <td class="d-none d-lg-table-cell small">
                            <?= (int)$r['slot_duration_minutes'] ?>m slot ·
                            <?= (int)$r['quiz_duration_minutes'] ?>m quiz
                        </td>
                        <td class="d-none d-lg-table-cell"><?= (int)$r['questions_per_quiz'] ?></td>
                        <td class="d-none d-md-table-cell small text-muted">
                            <?= e(dt_display($r['starts_at'], 'd M, H:i')) ?>
                            <?= $r['ends_at'] ? '<br>– ' . e(dt_display($r['ends_at'], 'd M, H:i')) : '' ?>
                        </td>
                        <td><span class="<?= status_badge($r['status']) ?>"><?= e(status_label($r['status'])) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="/admin/rounds/<?= (int)$r['round_id'] ?>/edit"
                               class="btn btn-sm btn-outline-navy"><i class="bi bi-pencil"></i></a>
                            <form action="/admin/rounds/<?= (int)$r['round_id'] ?>/delete"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this round? Slots and results will be removed.');">
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
