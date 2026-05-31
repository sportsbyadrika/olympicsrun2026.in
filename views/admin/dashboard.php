<?php /** @var array $stats */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 text-navy mb-1">Admin Dashboard</h1>
        <p class="text-muted mb-0">Welcome, <?= e(Auth::user()['name']) ?>.</p>
    </div>
</div>

<div class="row g-3">
    <?php
    $cards = [
        ['label' => 'Associations',      'value' => $stats['associations'],      'url' => '/admin/associations',      'color' => 'text-navy'],
        ['label' => 'Schools',           'value' => $stats['schools_total'],     'url' => '/admin/schools',           'color' => 'text-navy',
         'sub' => $stats['schools_approved'] . ' approved · ' . $stats['schools_pending'] . ' pending'],
        ['label' => 'Association Users', 'value' => $stats['association_users'], 'url' => '/admin/association-users', 'color' => 'text-accent'],
        ['label' => 'Expert Panelists',  'value' => $stats['panelists'],         'url' => '/admin/panelists',         'color' => 'text-accent'],
        ['label' => 'Team Logins',       'value' => $stats['school_logins'],     'url' => '/admin/schools',           'color' => 'text-navy'],
    ];
    foreach ($cards as $c): ?>
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="<?= e($c['url']) ?>" class="text-decoration-none">
                <div class="card panel border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-muted mb-2"><?= e($c['label']) ?></h2>
                        <p class="display-6 <?= e($c['color']) ?> mb-1"><?= (int)$c['value'] ?></p>
                        <p class="small text-muted mb-0"><?= e($c['sub'] ?? 'Manage &rarr;') ?></p>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="alert alert-info mt-4 mb-0 small">
    <i class="bi bi-info-circle me-1"></i>
    Rounds, slots, questions, settings, and reports land in following phases.
</div>
