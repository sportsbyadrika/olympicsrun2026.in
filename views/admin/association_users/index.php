<?php /** @var array $users */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Association Users</h1>
    <a href="/admin/association-users/new" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add user
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
                    <th class="d-none d-lg-table-cell">Role</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No association users yet.</td></tr>
                <?php else: foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-navy"><?= e($u['full_name']) ?></div>
                            <div class="small text-muted d-md-none"><?= e($u['email']) ?></div>
                            <div class="small text-muted d-lg-none"><?= e($u['association_name']) ?></div>
                        </td>
                        <td><code><?= e($u['username']) ?></code></td>
                        <td class="d-none d-md-table-cell small"><?= e($u['email']) ?></td>
                        <td class="d-none d-lg-table-cell"><?= e($u['association_name']) ?></td>
                        <td class="d-none d-lg-table-cell small text-muted"><?= e($u['role_label']) ?></td>
                        <td><span class="<?= status_badge($u['status']) ?>"><?= e(status_label($u['status'])) ?></span></td>
                        <td class="text-end text-nowrap">
                            <a href="/admin/association-users/<?= (int)$u['association_user_id'] ?>/edit"
                               class="btn btn-sm btn-outline-navy"><i class="bi bi-pencil"></i></a>
                            <form action="/admin/association-users/<?= (int)$u['association_user_id'] ?>/delete"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this user?');">
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
