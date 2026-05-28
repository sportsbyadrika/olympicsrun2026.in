<?php
/** @var array<string,string> $roles */
/** @var string $selected_role */
$old = $_SESSION['_old'] ?? [];
unset($_SESSION['_old']);
$selectedRole = $old['role'] ?? $selected_role ?? Auth::ROLE_ADMIN;
$oldUsername  = $old['username'] ?? '';
?>
<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
        <div class="card panel shadow-sm border-0 mt-3 mt-md-5">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="brand-mark brand-mark-lg mx-auto mb-2">OR</div>
                    <h1 class="h4 text-navy mb-1">Olympics Run 2026</h1>
                    <p class="text-muted small mb-0">Kerala Olympic Association</p>
                </div>

                <form method="post" action="/login" novalidate>
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="role" class="form-label">I am signing in as</label>
                        <select class="form-select" id="role" name="role" required>
                            <?php foreach ($roles as $value => $label): ?>
                                <option value="<?= e($value) ?>"
                                    <?= $value === $selectedRole ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username"
                               name="username" autocomplete="username"
                               value="<?= e($oldUsername) ?>"
                               required autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password"
                               name="password" autocomplete="current-password"
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center small text-muted mt-3 mb-0">
            Need help? <a href="mailto:support@olympicsrun2026.in"
                          class="link-accent">support@olympicsrun2026.in</a>
        </p>
    </div>
</div>
