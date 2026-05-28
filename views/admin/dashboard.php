<div class="d-md-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 text-navy mb-1">Admin Dashboard</h1>
        <p class="text-muted mb-0">
            Welcome, <?= e(Auth::user()['name']) ?>.
        </p>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Users</h2>
                <p class="display-6 text-navy mb-1">&mdash;</p>
                <p class="small text-muted mb-0">Total accounts across all roles.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Master Questions</h2>
                <p class="display-6 text-accent mb-1">&mdash;</p>
                <p class="small text-muted mb-0">Curated pool size.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Active Slots</h2>
                <p class="display-6 text-navy mb-1">&mdash;</p>
                <p class="small text-muted mb-0">Open or scheduled.</p>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4 mb-0 small">
    <i class="bi bi-info-circle me-1"></i>
    Feature screens (users, questions, sets, slots, settings, reports) will
    be wired up in the next phase. The navigation links above are placeholders.
</div>
