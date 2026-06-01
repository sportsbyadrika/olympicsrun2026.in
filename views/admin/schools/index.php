<?php /** @var array $schools */ /** @var string $nameFilter */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Schools</h1>
    <a href="/admin/schools/new" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add school
    </a>
</div>

<!-- Filter -->
<form method="get" action="/admin/schools" class="card panel border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label small mb-1" for="q">Search by school name</label>
                <input type="text" name="q" id="q" class="form-control form-control-sm"
                       value="<?= e($nameFilter) ?>" placeholder="Type part of a school name…">
            </div>
            <div class="col-12 col-md-auto">
                <button class="btn btn-sm btn-outline-navy">Search</button>
                <?php if ($nameFilter !== ''): ?>
                    <a href="/admin/schools" class="btn btn-sm btn-link">Clear</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</form>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>School</th>
                    <th class="d-none d-md-table-cell">Code</th>
                    <th class="d-none d-lg-table-cell">Type</th>
                    <th class="d-none d-lg-table-cell">Syllabus</th>
                    <th class="d-none d-md-table-cell">Association</th>
                    <th class="text-center">Teams</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schools)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">
                        <?= $nameFilter !== '' ? 'No schools match “' . e($nameFilter) . '”.' : 'No schools yet.' ?>
                    </td></tr>
                <?php else: foreach ($schools as $s): ?>
                    <tr>
                        <td>
                            <a href="/admin/schools/<?= (int)$s['school_id'] ?>"
                               class="fw-semibold text-navy text-decoration-none">
                                <?= e($s['school_name']) ?>
                            </a>
                            <div class="small text-muted d-md-none"><?= e($s['school_code'] ?? '') ?></div>
                        </td>
                        <td class="d-none d-md-table-cell"><?= e($s['school_code'] ?? '—') ?></td>
                        <td class="d-none d-lg-table-cell"><?= e($s['school_type_name'] ?? '—') ?></td>
                        <td class="d-none d-lg-table-cell"><?= e($s['syllabus_name'] ?? '—') ?></td>
                        <td class="d-none d-md-table-cell"><?= e($s['association_name']) ?></td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border"><?= (int)($s['team_count'] ?? 0) ?></span>
                        </td>
                        <td><span class="<?= status_badge($s['status']) ?>"><?= e(status_label($s['status'])) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="/admin/schools/<?= (int)$s['school_id'] ?>"
                               class="btn btn-sm btn-primary"><i class="bi bi-people me-1"></i>Manage</a>
                            <a href="/admin/schools/<?= (int)$s['school_id'] ?>/edit"
                               class="btn btn-sm btn-outline-navy"><i class="bi bi-pencil"></i></a>
                            <form action="/admin/schools/<?= (int)$s['school_id'] ?>/delete"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this school? Its teams and logins will also be removed.');">
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
