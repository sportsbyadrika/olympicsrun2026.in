<?php
/** @var array $template */
$old    = pull_old();
$errors = pull_errors();
$action = '/admin/email-templates/' . urlencode($template['template_key']);
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-0">Edit Email Template</h1>
        <p class="text-muted small mb-0"><code><?= e($template['template_key']) ?></code></p>
    </div>
    <a href="/admin/email-templates" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
</div>

<div class="card panel border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= e($action) ?>" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-12 col-md-8">
                    <label class="form-label" for="name">Display name *</label>
                    <input type="text" name="name" id="name"
                           class="form-control<?= invalid_class($errors, 'name') ?>"
                           value="<?= e(field($old, $template, 'name')) ?>" required maxlength="150">
                    <?= err($errors, 'name') ?>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="placeholders">Placeholders</label>
                    <input type="text" name="placeholders" id="placeholders"
                           class="form-control"
                           value="<?= e(field($old, $template, 'placeholders')) ?>" maxlength="500"
                           placeholder="comma list">
                    <div class="form-text">Reference only — `{{name}}` tokens are replaced at send.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="subject">Subject *</label>
                    <input type="text" name="subject" id="subject"
                           class="form-control<?= invalid_class($errors, 'subject') ?>"
                           value="<?= e(field($old, $template, 'subject')) ?>" required maxlength="255">
                    <?= err($errors, 'subject') ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="body_html">HTML body *</label>
                    <textarea name="body_html" id="body_html" rows="10"
                              class="form-control font-monospace<?= invalid_class($errors, 'body_html') ?>"
                              required spellcheck="false"><?= e(field($old, $template, 'body_html')) ?></textarea>
                    <div class="form-text small">
                        Use `{{placeholder}}` tokens. Unknown tokens render as empty string.
                    </div>
                    <?= err($errors, 'body_html') ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="body_text">Plain-text body <small class="text-muted">(optional fallback)</small></label>
                    <textarea name="body_text" id="body_text" rows="6"
                              class="form-control font-monospace"
                              spellcheck="false"><?= e(field($old, $template, 'body_text')) ?></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary">Save template</button>
                <a href="/admin/email-templates" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
