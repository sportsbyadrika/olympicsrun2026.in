<nav class="navbar navbar-expand-lg navbar-dark bg-navy shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-semibold d-flex align-items-center" href="/">
            <span class="brand-mark me-2" aria-hidden="true">OR</span>
            <span>Olympics Run 2026</span>
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <?php if (Auth::check()): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php
                    $currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
                    foreach (nav_for_role(Auth::role()) as $item):
                        $active = $currentPath === $item['url'];
                    ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $active ? 'active' : '' ?>"
                               <?= $active ? 'aria-current="page"' : '' ?>
                               href="<?= e($item['url']) ?>">
                                <?= e($item['label']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center"
                           href="#" id="userMenu" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= e(Auth::user()['name'] ?? Auth::user()['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                            aria-labelledby="userMenu">
                            <li>
                                <span class="dropdown-item-text small text-muted">
                                    Signed in as <strong><?= e(Auth::roles()[Auth::role()] ?? Auth::role()) ?></strong>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/logout">
                                    <i class="bi bi-box-arrow-right me-1"></i> Sign out
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/login">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Sign in
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
