<div class="d-md-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 text-navy mb-1">Panelist Dashboard</h1>
        <p class="text-muted mb-0">
            Welcome, <?= e(Auth::user()['name']) ?>.
        </p>
    </div>
    <a href="/panelist/questions/new" class="btn btn-primary mt-3 mt-md-0">
        <i class="bi bi-plus-lg me-1"></i> New question
    </a>
</div>

<div class="row g-3">
    <div class="col-12 col-md-4">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Submitted</h2>
                <p class="display-6 text-navy mb-1">&mdash;</p>
                <p class="small text-muted mb-0">All-time.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Approved</h2>
                <p class="display-6 text-accent mb-1">&mdash;</p>
                <p class="small text-muted mb-0">In curated pool.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Pending</h2>
                <p class="display-6 text-navy mb-1">&mdash;</p>
                <p class="small text-muted mb-0">Awaiting review.</p>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4 mb-0 small">
    <i class="bi bi-info-circle me-1"></i>
    Question submission and review screens land in the next phase.
</div>
