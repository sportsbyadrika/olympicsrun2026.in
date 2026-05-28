<?php /** @var int $pendingCount */ /** @var int $masterCount */ /** @var int $myReviews */ ?>
<div class="d-md-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 text-navy mb-1">Panelist Dashboard</h1>
        <p class="text-muted mb-0">Welcome, <?= e(Auth::user()['name']) ?>.</p>
    </div>
    <a href="/panelist/master/new" class="btn btn-primary mt-3 mt-md-0">
        <i class="bi bi-plus-lg me-1"></i> Add to master
    </a>
</div>

<div class="row g-3">
    <div class="col-12 col-md-4">
        <a href="/panelist/review" class="text-decoration-none">
            <div class="card panel border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-2">Review queue</h2>
                    <p class="display-6 text-navy mb-1"><?= $pendingCount ?></p>
                    <p class="small text-muted mb-0">Pending questions to triage.</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-4">
        <a href="/panelist/master" class="text-decoration-none">
            <div class="card panel border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted mb-2">Master bank</h2>
                    <p class="display-6 text-accent mb-1"><?= $masterCount ?></p>
                    <p class="small text-muted mb-0">Active questions in curated pool.</p>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-md-4">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">My reviews</h2>
                <p class="display-6 text-navy mb-1"><?= $myReviews ?></p>
                <p class="small text-muted mb-0">Decisions you've made.</p>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4 mb-0 small">
    <i class="bi bi-info-circle me-1"></i>
    Use the Slot Builder to drag master questions onto Round 1 / Round 2 slots.
</div>
