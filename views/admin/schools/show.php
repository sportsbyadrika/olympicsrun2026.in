<?php
/** @var array $school */
/** @var array $logins */
/** @var int   $maxTeams */
$sid       = (int)$school['school_id'];
$count     = count($logins);
$atLimit   = $count >= $maxTeams;
$hasEmail  = trim((string)($school['contact_email'] ?? '')) !== '';
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-0"><?= e($school['school_name']) ?></h1>
        <p class="text-muted small mb-0">
            <?= e($school['association_name']) ?>
            <?php if (!empty($school['school_code'])): ?>
                · <?= e($school['school_code']) ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/schools/<?= $sid ?>/edit" class="btn btn-outline-navy">
            <i class="bi bi-pencil me-1"></i> Edit school
        </a>
        <a href="/admin/schools" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>
</div>

<!-- School summary -->
<div class="card panel border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-6 col-md-3">
                <div class="text-muted">Status</div>
                <span class="<?= status_badge($school['status']) ?>"><?= e(status_label($school['status'])) ?></span>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted">Region</div>
                <div class="fw-semibold text-navy"><?= e($school['region'] ?? '—') ?></div>
            </div>
            <div class="col-12 col-md-3">
                <div class="text-muted">Contact email</div>
                <div class="fw-semibold <?= $hasEmail ? 'text-navy' : 'text-danger' ?>">
                    <?= $hasEmail ? e($school['contact_email']) : 'Not set' ?>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-muted">Contact phone</div>
                <div class="fw-semibold text-navy"><?= e($school['contact_phone'] ?? '—') ?></div>
            </div>
        </div>
        <?php if (!$hasEmail): ?>
            <div class="alert alert-warning mt-3 mb-0 small py-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                No contact email on file — password emails to this school will fail until you
                <a href="/admin/schools/<?= $sid ?>/edit" class="alert-link">add one</a>.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Teams / logins -->
<div class="card panel border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold text-navy">
            Team Logins
            <span class="badge bg-light text-dark border ms-1"><?= $count ?> / <?= (int)$maxTeams ?></span>
        </span>
        <button type="button" class="btn btn-sm btn-primary"
                data-bs-toggle="modal" data-bs-target="#addTeamModal"
                <?= $atLimit ? 'disabled' : '' ?>>
            <i class="bi bi-plus-lg me-1"></i> Add team
        </button>
    </div>

    <?php if ($atLimit): ?>
        <div class="card-body pb-0">
            <div class="alert alert-info small py-2 mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Team limit reached. Increase <code>max_teams_per_school</code> in
                <a href="/admin/settings" class="alert-link">Settings</a> to add more.
            </div>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Username</th>
                    <th>Team</th>
                    <th>Status</th>
                    <th class="d-none d-md-table-cell">Credentials sent</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logins)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">
                        No team logins yet. Use “Add team” to create one.
                    </td></tr>
                <?php else: foreach ($logins as $l):
                    $lid = (int)$l['school_login_id']; ?>
                    <tr>
                        <td class="fw-semibold text-navy"><?= e($l['username']) ?></td>
                        <td><?= e($l['team_label'] ?? '—') ?></td>
                        <td><span class="<?= status_badge($l['status']) ?>"><?= e(status_label($l['status'])) ?></span></td>
                        <td class="d-none d-md-table-cell small text-muted">
                            <?= !empty($l['credentials_sent_at']) ? e(dt_display($l['credentials_sent_at'])) : '—' ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="btn btn-sm btn-outline-navy js-edit-team"
                                    title="Edit team"
                                    data-id="<?= $lid ?>"
                                    data-username="<?= e($l['username']) ?>"
                                    data-team="<?= e($l['team_label'] ?? '') ?>"
                                    data-status="<?= e($l['status']) ?>">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="/admin/schools/<?= $sid ?>/logins/<?= $lid ?>/reset"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Reset this team’s password and email it to the school?');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-warning" title="Reset &amp; email password">
                                    <i class="bi bi-key"></i>
                                </button>
                            </form>
                            <form action="/admin/schools/<?= $sid ?>/logins/<?= $lid ?>/delete"
                                  method="post" class="d-inline"
                                  onsubmit="return confirm('Delete this team login?');">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ============ Add Team modal ============ -->
<div class="modal fade" id="addTeamModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="/admin/schools/<?= $sid ?>/logins" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Add team login</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="add-team_label">Team label</label>
            <input type="text" class="form-control" id="add-team_label" name="team_label"
                   maxlength="100" placeholder="e.g. Senior Team">
          </div>
          <div class="mb-3">
            <label class="form-label" for="add-username">Username *</label>
            <input type="text" class="form-control" id="add-username" name="username"
                   maxlength="64" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="add-password">Password *</label>
            <div class="input-group">
              <input type="text" class="form-control" id="add-password" name="password"
                     maxlength="100" required>
              <button class="btn btn-outline-secondary" type="button"
                      onclick="document.getElementById('add-password').value = genPwd();">
                Generate
              </button>
            </div>
          </div>
          <div class="mb-1">
            <label class="form-label" for="add-status">Status</label>
            <select class="form-select" id="add-status" name="status">
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create team</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ Edit Team modal (shared, filled by JS) ============ -->
<div class="modal fade" id="editTeamModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" id="editTeamForm" action="" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Edit team login</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label" for="edit-team_label">Team label</label>
            <input type="text" class="form-control" id="edit-team_label" name="team_label" maxlength="100">
          </div>
          <div class="mb-3">
            <label class="form-label" for="edit-username">Username *</label>
            <input type="text" class="form-control" id="edit-username" name="username"
                   maxlength="64" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="edit-password">
              Password <small class="text-muted">(leave blank to keep current)</small>
            </label>
            <input type="text" class="form-control" id="edit-password" name="password" maxlength="100">
          </div>
          <div class="mb-1">
            <label class="form-label" for="edit-status">Status</label>
            <select class="form-select" id="edit-status" name="status">
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Random password helper for the "Generate" button.
function genPwd() {
    var c = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    var s = '';
    for (var i = 0; i < 10; i++) s += c.charAt(Math.floor(Math.random() * c.length));
    return s;
}

// Populate the shared edit modal from the clicked row's data-* attributes.
(function () {
    var baseAction = '/admin/schools/<?= $sid ?>/logins/';
    document.querySelectorAll('.js-edit-team').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('editTeamForm').action = baseAction + btn.dataset.id;
            document.getElementById('edit-team_label').value = btn.dataset.team || '';
            document.getElementById('edit-username').value   = btn.dataset.username || '';
            document.getElementById('edit-password').value   = '';
            document.getElementById('edit-status').value     = btn.dataset.status || 'active';
            new bootstrap.Modal(document.getElementById('editTeamModal')).show();
        });
    });
})();
</script>
