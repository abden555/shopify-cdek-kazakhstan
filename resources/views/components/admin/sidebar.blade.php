<aside class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
    <div class="offcanvas-header d-lg-none border-bottom border-secondary-subtle">
        <a class="brand" href="{{ route('admin.dashboard') }}" id="adminSidebarLabel">SC<span>KZ</span></a>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close"></button>
    </div>

    <div class="admin-sidebar-content offcanvas-body">
        <a class="brand d-none d-lg-inline-flex" href="{{ route('admin.dashboard') }}">SC<span>KZ</span></a>
        <p class="brand-caption">Shopify CDEK Kazakhstan</p>

        <nav class="nav flex-column sidebar-nav" aria-label="Primary navigation">
            <a class="nav-link active" href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a>
            <span class="sidebar-label">Operations</span>
            <a class="nav-link disabled" href="#" aria-disabled="true"><i class="bi bi-bag"></i>Orders <span class="badge text-bg-secondary ms-auto">Soon</span></a>
            <a class="nav-link disabled" href="#" aria-disabled="true"><i class="bi bi-truck"></i>Shipments <span class="badge text-bg-secondary ms-auto">Soon</span></a>
            <span class="sidebar-label">Configuration</span>
            <a class="nav-link disabled" href="#" aria-disabled="true"><i class="bi bi-plug"></i>Integrations <span class="badge text-bg-secondary ms-auto">Soon</span></a>
            <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.cdek.edit') }}"><i class="bi bi-gear"></i>Settings</a>
        </nav>

        <div class="sidebar-footer mt-auto">
            <span class="status-dot"></span>
            <span>System ready</span>
        </div>
    </div>
</aside>
