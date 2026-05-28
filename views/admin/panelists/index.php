<?php /** @var array $panelists */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Expert Panelists</h1>
    <a href="/admin/panelists/new" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add panelist
    </a>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th class="d-none d-md-table-cell">Email</th>
                    <th class="d-none d-lg-table-cell">Association</th>
                    <th class="d-none d-lg-table-cell">Expertise</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($panelists)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No panelists yet.</td></tr>
                <?php else: foreach ($panelists as $p): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-navy"><?= e($p['full_name']) ?></div>
                            <div class="small text-muted d-md-none"><?= e($p['email']) ?></div>
                        </td>
                        <td><code><?= e($p['username']) ?></code></td>
                        <td class="d-none d-md-table-cell small"><?= e($p['email']) ?></td>
                        <td class="d-none d-lg-table-cell"><?= e($p['association_name']) ?></td>
                        <td class="d-none d-lg-table-cell small text-muted"><?= e($p['expertise'] ?? '—') ?></td>
                        <td><span class="<?= status_badge($p['status']) ?>"><?= e(status_label($p['status'])) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="/admin/panelists/<?= (int)$p['panelist_id'] ?>/edit"
                               class="btn btn-sm btn-outline-navy"><i class="bi bi-pencil"></i></a>
                            <form action="/admin/panelists/<?= (int)$p['panelist_id'] ?>/delete"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this panelist?');">
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
