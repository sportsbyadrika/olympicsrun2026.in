<div class="d-md-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 text-navy mb-1">School Dashboard</h1>
        <p class="text-muted mb-0">
            Welcome, <?= e(Auth::user()['name']) ?>.
        </p>
    </div>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Round 1 Slot</h2>
                <p class="h5 text-navy mb-1">&mdash;</p>
                <p class="small text-muted mb-0">Your scheduled qualifier window.</p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6">
        <div class="card panel border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h6 text-uppercase text-muted mb-2">Round 2 Slot</h2>
                <p class="h5 text-accent mb-1">&mdash;</p>
                <p class="small text-muted mb-0">Shown only if you qualify.</p>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4 mb-0 small">
    <i class="bi bi-info-circle me-1"></i>
    Profile, slot details, the quiz screen, and result card land in the next phase.
</div>
