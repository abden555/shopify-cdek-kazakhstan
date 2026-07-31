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
            <a class="fw-semibold small text-decoration-none text-body" href="{{ route('admin.account.edit') }}">{{ auth()->user()->name }}</a>
            <a class="d-block small account-link" href="{{ route('admin.account.edit') }}">My account</a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="btn btn-link btn-sm p-0 text-body-secondary" type="submit">Sign out</button>
            </form>
        </div>
        <a class="avatar text-decoration-none" href="{{ route('admin.account.edit') }}" aria-label="Manage your account">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</a>
    </div>
</header>
