<?php
$currentPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$items = Auth::check() ? nav_for_role(Auth::role()) : [];

$isItemActive = static function (array $item) use ($currentPath): bool {
    if (isset($item['url']) && $currentPath === $item['url']) return true;
    if (!empty($item['children'])) {
        foreach ($item['children'] as $c) {
            if (isset($c['url']) && str_starts_with($currentPath ?: '', rtrim($c['url'], '/'))) {
                return true;
            }
        }
    }
    return false;
};
?>
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
                    <?php foreach ($items as $i => $item):
                        $active = $isItemActive($item);
                    ?>
                        <?php if (!empty($item['children'])): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?= $active ? 'active' : '' ?>"
                                   href="#" role="button"
                                   id="nav-dd-<?= $i ?>"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    <?= e($item['label']) ?>
                                </a>
                                <ul class="dropdown-menu shadow-sm" aria-labelledby="nav-dd-<?= $i ?>">
                                    <?php foreach ($item['children'] as $c): ?>
                                        <li>
                                            <a class="dropdown-item <?= $currentPath === $c['url'] ? 'active' : '' ?>"
                                               href="<?= e($c['url']) ?>">
                                                <?= e($c['label']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $active ? 'active' : '' ?>"
                                   <?= $active ? 'aria-current="page"' : '' ?>
                                   href="<?= e($item['url']) ?>">
                                    <?= e($item['label']) ?>
                                </a>
                            </li>
                        <?php endif; ?>
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
