<?php /** @var array $schools */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Schools</h1>
    <a href="/admin/schools/new" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add school
    </a>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>School</th>
                    <th class="d-none d-md-table-cell">Code</th>
                    <th class="d-none d-lg-table-cell">Association</th>
                    <th class="d-none d-lg-table-cell">Region</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schools)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No schools yet.</td></tr>
                <?php else: foreach ($schools as $s): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-navy"><?= e($s['school_name']) ?></div>
                            <div class="small text-muted d-md-none"><?= e($s['school_code'] ?? '') ?></div>
                            <div class="small text-muted d-lg-none"><?= e($s['association_name']) ?></div>
                        </td>
                        <td class="d-none d-md-table-cell"><code><?= e($s['school_code'] ?? '—') ?></code></td>
                        <td class="d-none d-lg-table-cell"><?= e($s['association_name']) ?></td>
                        <td class="d-none d-lg-table-cell"><?= e($s['region'] ?? '—') ?></td>
                        <td><span class="<?= status_badge($s['status']) ?>"><?= e(status_label($s['status'])) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="/admin/school-logins/new?school_id=<?= (int)$s['school_id'] ?>&generate=1"
                               class="btn btn-sm btn-outline-navy" title="Generate login">
                                <i class="bi bi-key"></i>
                            </a>
                            <a href="/admin/schools/<?= (int)$s['school_id'] ?>/edit"
                               class="btn btn-sm btn-outline-navy" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form action="/admin/schools/<?= (int)$s['school_id'] ?>/delete"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this school?');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
