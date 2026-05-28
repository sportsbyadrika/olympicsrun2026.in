<?php
/** @var array $slot */
/** @var array $assigned */
/** @var array $available */
$cap  = (int)$slot['capacity'];
$used = count($assigned);
$room = max(0, $cap - $used);
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-1"><?= e($slot['slot_label'] ?? 'Slot #' . (int)$slot['slot_id']) ?></h1>
        <p class="text-muted mb-0">
            <?= e($slot['association_name']) ?> ·
            R<?= (int)$slot['round_number'] ?> <?= e($slot['round_name']) ?> ·
            <?= e(dt_display($slot['starts_at'], 'd M, H:i')) ?>
            – <?= e(dt_display($slot['ends_at'], 'H:i')) ?>
        </p>
    </div>
    <a href="/admin/slots" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>All slots</a>
</div>

<div class="row g-3">
    <!-- Assigned -->
    <div class="col-12 col-lg-6">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 text-navy mb-0">Assigned</h2>
                    <span class="badge bg-navy text-white"><?= $used ?> / <?= $cap ?></span>
                </div>

                <?php if (empty($assigned)): ?>
                    <p class="text-muted small mb-0">No schools assigned yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr><th>School</th><th>Team login</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned as $a): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-navy"><?= e($a['school_name']) ?></div>
                                            <div class="small text-muted"><?= e($a['school_code'] ?? '') ?></div>
                                        </td>
                                        <td><?= $a['login_username'] ? '<code>' . e($a['login_username']) . '</code>' : '<span class="text-muted small">—</span>' ?></td>
                                        <td><span class="<?= status_badge($a['attempt_status']) ?>"><?= e(status_label($a['attempt_status'])) ?></span></td>
                                        <td class="text-end">
                                            <form action="/admin/slots/<?= (int)$slot['slot_id'] ?>/unassign"
                                                  method="post" class="d-inline"
                                                  onsubmit="return confirm('Remove this school from the slot?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="school_id" value="<?= (int)$a['school_id'] ?>">
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Available -->
    <div class="col-12 col-lg-6">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 text-navy mb-0">Available</h2>
                    <span class="small text-muted"><?= $room ?> seat<?= $room === 1 ? '' : 's' ?> left</span>
                </div>

                <?php if (empty($available)): ?>
                    <p class="text-muted small mb-0">
                        No more schools available. (Approved schools already assigned to a slot
                        in this round are excluded — one team per school per round.)
                    </p>
                <?php else: ?>
                    <form method="post" action="/admin/slots/<?= (int)$slot['slot_id'] ?>/assign">
                        <?= csrf_field() ?>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:32px"></th>
                                        <th>School</th>
                                        <th>Login</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($available as $av): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="school_ids[]"
                                                       value="<?= (int)$av['school_id'] ?>"
                                                       class="form-check-input"
                                                       <?= $room === 0 ? 'disabled' : '' ?>>
                                            </td>
                                            <td>
                                                <div class="fw-semibold text-navy"><?= e($av['school_name']) ?></div>
                                                <div class="small text-muted"><?= e($av['school_code'] ?? '') ?></div>
                                            </td>
                                            <td>
                                                <?php if ($av['default_login_id']): ?>
                                                    <i class="bi bi-check-circle text-success"></i>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">no login</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-primary" <?= $room === 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-arrow-left me-1"></i> Assign selected
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
