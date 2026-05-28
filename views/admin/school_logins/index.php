<?php /** @var array $logins */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">School Logins</h1>
    <a href="/admin/school-logins/new" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add login
    </a>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>School</th>
                    <th>Username</th>
                    <th class="d-none d-md-table-cell">Team</th>
                    <th class="d-none d-lg-table-cell">Last login</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logins)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No school logins yet.</td></tr>
                <?php else: foreach ($logins as $l): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-navy"><?= e($l['school_name']) ?></div>
                            <div class="small text-muted"><?= e($l['association_name']) ?></div>
                        </td>
                        <td><code><?= e($l['username']) ?></code></td>
                        <td class="d-none d-md-table-cell"><?= e($l['team_label'] ?? '—') ?></td>
                        <td class="d-none d-lg-table-cell small text-muted"><?= e(dt_display($l['last_login_at'])) ?></td>
                        <td><span class="<?= status_badge($l['status']) ?>"><?= e(status_label($l['status'])) ?></span></td>
                        <td class="text-end text-nowrap">
                            <form action="/admin/school-logins/<?= (int)$l['school_login_id'] ?>/reset"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Generate a new random password? The old one will stop working immediately.');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-navy" title="Reset password">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </form>
                            <a href="/admin/school-logins/<?= (int)$l['school_login_id'] ?>/edit"
                               class="btn btn-sm btn-outline-navy"><i class="bi bi-pencil"></i></a>
                            <form action="/admin/school-logins/<?= (int)$l['school_login_id'] ?>/delete"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this login?');">
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
