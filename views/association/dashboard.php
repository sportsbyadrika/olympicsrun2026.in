<div class="d-md-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 text-navy mb-1">Association Dashboard</h1>
        <p class="text-muted mb-0">
            Welcome, <?= e(Auth::user()['name']) ?>.
        </p>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Pending Schools</h2>
                <p class="display-6 text-navy mb-1">&mdash;</p>
                <p class="small text-muted mb-0">Awaiting approval.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Slots Today</h2>
                <p class="display-6 text-accent mb-1">&mdash;</p>
                <p class="small text-muted mb-0">Live quiz windows.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Submitted Attempts</h2>
                <p class="display-6 text-navy mb-1">&mdash;</p>
                <p class="small text-muted mb-0">Ready to publish.</p>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4 mb-0 small">
    <i class="bi bi-info-circle me-1"></i>
    Schools, slots, results, and reports screens will arrive in the next phase.
</div>
