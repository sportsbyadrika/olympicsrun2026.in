<?php
/** @var array $school */
/** @var array $logins */
/** @var array $participants */ // [login_id => [slot => participant row]]
/** @var int   $maxTeams */
/** @var string $defaultTeam */
$sid      = (int)$school['school_id'];
$count    = count($logins);
$atLimit  = $count >= $maxTeams;
$hasEmail = trim((string)($school['contact_email'] ?? '')) !== '';

// Renders the two participant fieldsets inside a modal. $prefix scopes the
// element ids so the add and edit modals don't collide.
$participantFields = function (string $prefix, array $rows = []) {
    ob_start();
    foreach ([1, 2] as $slot):
        $p = $rows[$slot] ?? [];
        $pid = (int)($p['participant_id'] ?? 0);
        ?>
        <div class="border rounded p-3 mb-3">
            <div class="fw-semibold text-navy mb-2">Participant <?= $slot ?></div>
            <div class="row g-2">
                <div class="col-12 col-sm-7">
                    <label class="form-label small mb-1">Name</label>
                    <input type="text" class="form-control form-control-sm"
                           name="participants[<?= $slot ?>][name]"
                           value="<?= e($p['participant_name'] ?? '') ?>" maxlength="150">
                </div>
                <div class="col-6 col-sm-5">
                    <label class="form-label small mb-1">Studying standard</label>
                    <input type="text" class="form-control form-control-sm"
                           name="participants[<?= $slot ?>][standard]"
                           value="<?= e($p['studying_standard'] ?? '') ?>" maxlength="50"
                           placeholder="e.g. Class 10">
                </div>
                <div class="col-4">
                    <label class="form-label small mb-1">Age</label>
                    <input type="number" class="form-control form-control-sm"
                           name="participants[<?= $slot ?>][age]" min="3" max="30"
                           value="<?= e((string)($p['age'] ?? '')) ?>">
                </div>
                <div class="col-8">
                    <label class="form-label small mb-1">Gender</label>
                    <select class="form-select form-select-sm" name="participants[<?= $slot ?>][gender]">
                        <option value="">—</option>
                        <?php foreach (TeamParticipant::GENDERS as $g): ?>
                            <option value="<?= e($g) ?>" <?= ($p['gender'] ?? '') === $g ? 'selected' : '' ?>>
                                <?= e(ucfirst($g)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small mb-1">Passport photo</label>
                    <div class="d-flex align-items-start gap-3">
                        <img class="js-photo-preview border rounded"
                             src="<?= $pid ? '/admin/participants/' . $pid . '/photo' : '' ?>"
                             alt="" width="72" height="90"
                             style="object-fit:cover;background:#f0f1f4;<?= $pid ? '' : 'display:none;' ?>">
                        <div class="flex-grow-1">
                            <input type="file" accept="image/*"
                                   class="form-control form-control-sm js-photo-file" data-slot="<?= $slot ?>">
                            <!-- base64 crop result posted with the form -->
                            <input type="hidden" name="participant_photo[<?= $slot ?>]"
                                   class="js-photo-data" value="">
                            <div class="form-text small">JPG/PNG. You'll be able to crop after choosing.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach;
    return ob_get_clean();
};
?>
<div class="d-md-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h3 text-navy mb-0"><?= e($school['school_name']) ?></h1>
        <p class="text-muted small mb-0">
            <?= e($school['association_name']) ?>
            <?php if (!empty($school['school_code'])): ?> · <?= e($school['school_code']) ?><?php endif; ?>
            <?php if (!empty($school['school_type_name'])): ?> · <?= e($school['school_type_name']) ?><?php endif; ?>
            <?php if (!empty($school['syllabus_name'])): ?> · <?= e($school['syllabus_name']) ?><?php endif; ?>
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

<!-- Teams and Logins -->
<div class="card panel border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold text-navy">
            Teams and Logins
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
                    <th>Participants</th>
                    <th>Status</th>
                    <th class="d-none d-md-table-cell">Credentials sent</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logins)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">
                        No teams yet. Use “Add team” to create one.
                    </td></tr>
                <?php else: foreach ($logins as $l):
                    $lid  = (int)$l['school_login_id'];
                    $rows = $participants[$lid] ?? []; ?>
                    <tr>
                        <td class="fw-semibold text-navy"><?= e($l['username']) ?></td>
                        <td><?= e($l['team_label'] ?? '—') ?></td>
                        <td>
                            <?php foreach ([1, 2] as $slot):
                                $p = $rows[$slot] ?? null;
                                if (!$p) continue;
                                $pid = (int)$p['participant_id']; ?>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <?php if (!empty($p['photo_path'])): ?>
                                        <img src="/admin/participants/<?= $pid ?>/photo" alt=""
                                             width="28" height="35"
                                             style="object-fit:cover;border-radius:3px;">
                                    <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-person-square"></i></span>
                                    <?php endif; ?>
                                    <span class="small">
                                        <?= e($p['participant_name']) ?>
                                        <?php if (!empty($p['studying_standard'])): ?>
                                            <span class="text-muted">· <?= e($p['studying_standard']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($rows)): ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
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
                                    data-status="<?= e($l['status']) ?>"
                                    data-p='<?= e(json_encode($rows, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>'>
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
                                  onsubmit="return confirm('Delete this team?');">
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
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" action="/admin/schools/<?= $sid ?>/logins" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Add team</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label" for="add-team_label">Team name</label>
              <input type="text" class="form-control" id="add-team_label" name="team_label"
                     maxlength="100" value="<?= e($defaultTeam) ?>">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="add-status">Status</label>
              <select class="form-select" id="add-status" name="status">
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="add-username">Username *</label>
              <input type="text" class="form-control" id="add-username" name="username"
                     maxlength="64" required>
            </div>
            <div class="col-12 col-md-6">
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
          </div>
          <hr>
          <h6 class="text-navy">Participants</h6>
          <?= $participantFields('add') ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create team</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ Edit Team modal ============ -->
<div class="modal fade" id="editTeamModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="post" id="editTeamForm" action="" novalidate>
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title">Edit team</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label" for="edit-team_label">Team name</label>
              <input type="text" class="form-control" id="edit-team_label" name="team_label" maxlength="100">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="edit-status">Status</label>
              <select class="form-select" id="edit-status" name="status">
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="edit-username">Username *</label>
              <input type="text" class="form-control" id="edit-username" name="username" maxlength="64" required>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label" for="edit-password">
                Password <small class="text-muted">(blank = keep current)</small>
              </label>
              <input type="text" class="form-control" id="edit-password" name="password" maxlength="100">
            </div>
          </div>
          <hr>
          <h6 class="text-navy">Participants</h6>
          <?= $participantFields('edit') ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ Crop modal ============ -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Crop photo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div style="max-height:60vh">
          <img id="cropImage" src="" style="max-width:100%; display:block;">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="cropApply">Apply crop</button>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
function genPwd() {
    var c = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789', s = '';
    for (var i = 0; i < 10; i++) s += c.charAt(Math.floor(Math.random() * c.length));
    return s;
}

// Run after the page (and Bootstrap, which loads at the end of <body>) is
// ready. Without this, the script executes mid-parse before `bootstrap` is
// defined, throws, and never wires up the Edit modal / photo cropper.
document.addEventListener('DOMContentLoaded', function () {
    var cropper = null, activeCtx = null;
    var cropModalEl = document.getElementById('cropModal');
    var cropModal   = new bootstrap.Modal(cropModalEl);
    var cropImage   = document.getElementById('cropImage');

    // Wire file inputs (in both add and edit modals) to the cropper.
    document.querySelectorAll('.js-photo-file').forEach(function (input) {
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function (e) {
                // Remember which fieldset triggered this.
                var group = input.closest('.row') || input.closest('.border');
                activeCtx = {
                    dataField: group.querySelector('.js-photo-data'),
                    preview:   group.querySelector('.js-photo-preview')
                };
                cropImage.src = e.target.result;
                cropModal.show();
            };
            reader.readAsDataURL(file);
            input.value = ''; // allow re-selecting the same file later
        });
    });

    cropModalEl.addEventListener('shown.bs.modal', function () {
        if (cropper) cropper.destroy();
        cropper = new Cropper(cropImage, {
            aspectRatio: 4 / 5,   // passport-ish portrait
            viewMode: 1,
            autoCropArea: 1
        });
    });
    cropModalEl.addEventListener('hidden.bs.modal', function () {
        if (cropper) { cropper.destroy(); cropper = null; }
    });

    document.getElementById('cropApply').addEventListener('click', function () {
        if (!cropper || !activeCtx) return;
        var canvas = cropper.getCroppedCanvas({ width: 400, height: 500 });
        var data = canvas.toDataURL('image/jpeg', 0.85);
        activeCtx.dataField.value = data;
        if (activeCtx.preview) {
            activeCtx.preview.src = data;
            activeCtx.preview.style.display = '';
        }
        cropModal.hide();
    });

    // Populate the edit modal from the clicked row.
    var baseAction = '/admin/schools/<?= $sid ?>/logins/';
    document.querySelectorAll('.js-edit-team').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = document.getElementById('editTeamForm');
            form.action = baseAction + btn.dataset.id;
            document.getElementById('edit-team_label').value = btn.dataset.team || '';
            document.getElementById('edit-username').value   = btn.dataset.username || '';
            document.getElementById('edit-password').value   = '';
            document.getElementById('edit-status').value     = btn.dataset.status || 'active';

            // Reset participant photo hidden fields (don't resend unless re-cropped).
            form.querySelectorAll('.js-photo-data').forEach(function (h) { h.value = ''; });

            // Fill participant fields from the row's data-p JSON.
            var rows = {};
            try { rows = JSON.parse(btn.dataset.p || '{}'); } catch (e) { rows = {}; }
            [1, 2].forEach(function (slot) {
                var p = rows[slot] || {};
                var set = function (field, val) {
                    var el = form.querySelector('[name="participants[' + slot + '][' + field + ']"]');
                    if (el) el.value = val == null ? '' : val;
                };
                set('name', p.participant_name);
                set('standard', p.studying_standard);
                set('age', p.age);
                set('gender', p.gender);

                // Photo preview: show existing photo (served by id) if present.
                var block = form.querySelectorAll('.border.rounded.p-3')[slot - 1];
                if (block) {
                    var prev = block.querySelector('.js-photo-preview');
                    if (prev) {
                        if (p.participant_id) {
                            prev.src = '/admin/participants/' + p.participant_id + '/photo?t=' + Date.now();
                            prev.style.display = '';
                        } else {
                            prev.removeAttribute('src');
                            prev.style.display = 'none';
                        }
                    }
                }
            });

            new bootstrap.Modal(document.getElementById('editTeamModal')).show();
        });
    });
});
</script>
