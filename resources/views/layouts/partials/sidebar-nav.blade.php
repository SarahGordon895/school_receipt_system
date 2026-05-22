<nav class="sidebar-nav" aria-label="Main menu">
    @if (auth()->user()->hasRole('super_admin', 'school_admin'))
        <a href="{{ route('dashboard') }}"
            class="sidebar-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Dashboard</span>
        </a>
        <a href="{{ route('receipts.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('receipts.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Receipts</span>
        </a>
        <a href="{{ route('students.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('students.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Students</span>
        </a>
        <a href="{{ route('fee-structures.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('fee-structures.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-cash-coin" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Fee Structures</span>
        </a>
        <a href="{{ route('payment-categories.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('payment-categories.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-tags" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Payment Categories</span>
        </a>
        <a href="{{ route('reports.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-graph-up" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Reports</span>
        </a>
        <a href="{{ route('notification-logs.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('notification-logs.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Notifications</span>
        </a>
        <a href="{{ route('settings.edit') }}"
            class="sidebar-nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-gear" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Settings</span>
        </a>
    @endif

    <a href="{{ route('profile.edit') }}"
        class="sidebar-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
        <span class="sidebar-nav-icon"><i class="bi bi-person-circle" aria-hidden="true"></i></span>
        <span class="sidebar-nav-text">My Profile</span>
    </a>

    @if (auth()->user()->isParent())
        <a href="{{ route('parent.dashboard') }}"
            class="sidebar-nav-link {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Parent Portal</span>
        </a>
        <a href="{{ route('parent.notifications') }}"
            class="sidebar-nav-link {{ request()->routeIs('parent.notifications') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-bell" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">My Notifications</span>
            @if (($parentUnreadNotifications ?? 0) > 0)
                <span class="badge rounded-pill text-bg-primary sidebar-nav-badge">{{ $parentUnreadNotifications }}</span>
            @endif
        </a>
    @endif
</nav>
