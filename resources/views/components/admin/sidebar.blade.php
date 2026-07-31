<aside class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
    <div class="offcanvas-header d-lg-none border-bottom border-secondary-subtle">
        <a class="brand" href="{{ route('admin.dashboard') }}" id="adminSidebarLabel">
            @if(filled($applicationBranding['logo_path'] ?? null))<img class="brand-logo" src="{{ asset('storage/'.$applicationBranding['logo_path']) }}" alt="Application logo">@else SC<span>KZ</span>@endif
        </a>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button>
    </div>

    <div class="admin-sidebar-content offcanvas-body">
        <a class="brand d-none d-lg-inline-flex" href="{{ route('admin.dashboard') }}">
            @if(filled($applicationBranding['logo_path'] ?? null))
                <img class="brand-logo" src="{{ asset('storage/'.$applicationBranding['logo_path']) }}" alt="Application logo">
            @else
                SC<span>KZ</span>
            @endif
        </a>
        <p class="brand-caption">Shopify CDEK Kazakhstan</p>

        <nav class="nav flex-column sidebar-nav" aria-label="Primary navigation">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a>
            <span class="sidebar-label">Operations</span>
            <a class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}"><i class="bi bi-bag"></i>Orders</a>
            <a class="nav-link {{ request()->routeIs('admin.shipments.*') ? 'active' : '' }}" href="{{ route('admin.shipments.index') }}"><i class="bi bi-truck"></i>Shipments</a>
            <span class="sidebar-label">Configuration</span>
            <a class="nav-link {{ request()->routeIs('admin.settings.cdek.*') ? 'active' : '' }}" href="{{ route('admin.settings.cdek.edit') }}"><i class="bi bi-plug"></i>Integrations</a>
            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.cdek.edit') }}"><i class="bi bi-gear"></i>Settings</a>
            <a class="nav-link {{ request()->routeIs('admin.account.*') ? 'active' : '' }}" href="{{ route('admin.account.edit') }}"><i class="bi bi-person-gear"></i>My account</a>
        </nav>

        <div class="sidebar-footer mt-auto">
            <span class="status-dot"></span>
            <span>System ready</span>
        </div>
    </div>
</aside>
