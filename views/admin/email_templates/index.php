<?php /** @var array $templates */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 text-navy mb-2 mb-md-0">Email Templates</h1>
    <span class="small text-muted">SMTP settings live in
        <a href="/admin/settings" class="link-accent">Settings</a>.</span>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th class="d-none d-md-table-cell">Subject</th>
                    <th class="d-none d-lg-table-cell">Placeholders</th>
                    <th class="d-none d-lg-table-cell">Updated</th>
                    <th class="text-end"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($templates)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No templates defined.</td></tr>
                <?php else: foreach ($templates as $t): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-navy"><?= e($t['name']) ?></div>
                            <div class="small text-muted"><code><?= e($t['template_key']) ?></code></div>
                        </td>
                        <td class="d-none d-md-table-cell small"><?= e($t['subject']) ?></td>
                        <td class="d-none d-lg-table-cell small text-muted">
                            <?= e($t['placeholders'] ?? '—') ?>
                        </td>
                        <td class="d-none d-lg-table-cell small text-muted">
                            <?= e(dt_display($t['updated_at'])) ?>
                        </td>
                        <td class="text-end">
                            <a href="/admin/email-templates/<?= urlencode($t['template_key']) ?>/edit"
                               class="btn btn-sm btn-outline-navy"><i class="bi bi-pencil"></i> Edit</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
