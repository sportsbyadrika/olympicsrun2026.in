<?php /** @var array $qCounts */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 text-navy mb-1">Association Dashboard</h1>
        <p class="text-muted mb-0">Welcome, <?= e(Auth::user()['name']) ?>.</p>
    </div>
    <a href="/association/questions/new" class="btn btn-primary mt-3 mt-md-0">
        <i class="bi bi-plus-lg me-1"></i> New question
    </a>
</div>

<h2 class="h6 text-uppercase text-muted mb-2">Question bank</h2>
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['label' => 'All',            'value' => $qCounts['total'],          'color' => 'text-navy',   'status' => 'all'],
        ['label' => 'Drafts',         'value' => $qCounts['draft'],          'color' => 'text-navy',   'status' => 'draft'],
        ['label' => 'Pending review', 'value' => $qCounts['pending'],        'color' => 'text-accent', 'status' => 'pending'],
        ['label' => 'Approved',       'value' => $qCounts['approved'],       'color' => 'text-accent', 'status' => 'approved'],
        ['label' => 'Needs revision', 'value' => $qCounts['needs_revision'], 'color' => 'text-navy',   'status' => 'needs_revision'],
        ['label' => 'Rejected',       'value' => $qCounts['rejected'],       'color' => 'text-navy',   'status' => 'rejected'],
    ];
    foreach ($cards as $c): ?>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="/association/questions?status=<?= e($c['status']) ?>" class="text-decoration-none">
                <div class="card panel border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <p class="small text-muted text-uppercase mb-1"><?= e($c['label']) ?></p>
                        <p class="h3 <?= e($c['color']) ?> mb-0"><?= (int)$c['value'] ?></p>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="alert alert-info mb-0 small">
    <i class="bi bi-info-circle me-1"></i>
    Schools, slots, results and reports screens land in following phases.
</div>
