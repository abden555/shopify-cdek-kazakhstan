<x-admin.layout title="Dashboard">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="h3 mb-1">Welcome to Shopify CDEK Kazakhstan</h2>
            <p class="text-body-secondary mb-0">Your operational overview will appear here as integrations are configured.</p>
        </div>
        <span class="badge rounded-pill text-bg-light border px-3 py-2"><i class="bi bi-circle-fill text-success me-2 small"></i>Application online</span>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xxl-3">
            <article class="metric-card">
                <div class="metric-icon bg-primary-subtle text-primary"><i class="bi bi-bag-check"></i></div>
                <p class="metric-label">Orders today</p>
                <p class="metric-value">—</p>
                <p class="metric-hint">Available after Shopify setup</p>
            </article>
        </div>
        <div class="col-sm-6 col-xxl-3">
            <article class="metric-card">
                <div class="metric-icon bg-success-subtle text-success"><i class="bi bi-truck"></i></div>
                <p class="metric-label">Shipments created</p>
                <p class="metric-value">—</p>
                <p class="metric-hint">Available after CDEK setup</p>
            </article>
        </div>
        <div class="col-sm-6 col-xxl-3">
            <article class="metric-card">
                <div class="metric-icon bg-warning-subtle text-warning-emphasis"><i class="bi bi-clock-history"></i></div>
                <p class="metric-label">Pending actions</p>
                <p class="metric-value">0</p>
                <p class="metric-hint">No action required</p>
            </article>
        </div>
        <div class="col-sm-6 col-xxl-3">
            <article class="metric-card">
                <div class="metric-icon bg-info-subtle text-info"><i class="bi bi-activity"></i></div>
                <p class="metric-label">Background jobs</p>
                <p class="metric-value">0</p>
                <p class="metric-hint">Queue processing is ready</p>
            </article>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="h5 mb-1">Getting started</h2>
                            <p class="text-body-secondary small mb-0">Complete these foundations before enabling integrations.</p>
                        </div>
                        <i class="bi bi-rocket-takeoff text-primary fs-3"></i>
                    </div>
                    <ol class="setup-list mb-0">
                        <li><span class="setup-number">1</span><div><strong>Configure administrator access</strong><p>Set up authentication and roles for the back office.</p></div></li>
                        <li><span class="setup-number">2</span><div><strong>Review system settings</strong><p>Confirm local, queue, mail, and logging environments.</p></div></li>
                        <li><span class="setup-number">3</span><div><strong>Connect integrations</strong><p>Shopify and CDEK configuration will be added in a later phase.</p></div></li>
                    </ol>
                </div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Platform status</h2>
                    <dl class="status-list mb-0">
                        <div><dt>Application</dt><dd><span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Online</span></dd></div>
                        <div><dt>Database</dt><dd><span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Connected</span></dd></div>
                        <div><dt>Shopify</dt><dd><span class="text-body-secondary">Not configured</span></dd></div>
                        <div><dt>CDEK</dt><dd><span class="text-body-secondary">Not configured</span></dd></div>
                    </dl>
                </div>
            </section>
        </div>
    </div>
</x-admin.layout>
