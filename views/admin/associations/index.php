<?php /** @var array $associations */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Associations</h1>
    <a href="/admin/associations/new" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add association
    </a>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th class="d-none d-md-table-cell">Region</th>
                    <th class="d-none d-lg-table-cell">Contact</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($associations)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No associations yet.</td></tr>
                <?php else: foreach ($associations as $a): ?>
                    <tr>
                        <td><div class="fw-semibold text-navy"><?= e($a['name']) ?></div></td>
                        <td><code><?= e($a['short_code']) ?></code></td>
                        <td class="d-none d-md-table-cell"><?= e($a['region'] ?? '—') ?></td>
                        <td class="d-none d-lg-table-cell small">
                            <?= e($a['contact_email'] ?? '—') ?><br>
                            <span class="text-muted"><?= e($a['contact_phone'] ?? '') ?></span>
                        </td>
                        <td><span class="<?= status_badge($a['status']) ?>"><?= e(status_label($a['status'])) ?></span></td>
                        <td class="text-end">
                            <a href="/admin/associations/<?= (int)$a['association_id'] ?>/edit"
                               class="btn btn-sm btn-outline-navy"><i class="bi bi-pencil"></i></a>
                            <form action="/admin/associations/<?= (int)$a['association_id'] ?>/delete"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this association? Cascade-deletes its users, schools and questions.');">
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
