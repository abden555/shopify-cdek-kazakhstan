<header class="admin-header">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Open navigation">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div>
            <p class="eyebrow mb-1">Administration</p>
            <h1 class="h4 mb-0">{{ $title }}</h1>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-light position-relative" type="button" aria-label="Notifications" disabled>
            <i class="bi bi-bell"></i>
            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle"><span class="visually-hidden">No unread notifications</span></span>
        </button>
        <div class="d-none d-sm-block text-end">
            <div class="fw-semibold small">{{ auth()->user()->name }}</div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="btn btn-link btn-sm p-0 text-body-secondary" type="submit">Sign out</button>
            </form>
        </div>
        <div class="avatar" aria-hidden="true">A</div>
    </div>
</header>
