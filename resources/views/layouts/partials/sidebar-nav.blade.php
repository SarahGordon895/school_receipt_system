<nav class="sidebar-nav" aria-label="Main menu">
    @if (auth()->user()->canManageSchool())
        <div class="nav-label px-2 mb-2 text-uppercase small text-muted">School operations</div>
        <a href="{{ route('dashboard') }}"
            class="sidebar-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Dashboard</span>
        </a>
        <a href="{{ route('reports.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-graph-up" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Bursar Reports</span>
        </a>
        <a href="{{ route('messages.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('messages.*') || request()->routeIs('notification-logs.send.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-chat-dots" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">SMS &amp; Email</span>
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
        <a href="{{ route('bank-payments.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('bank-payments.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-bank" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Bank Payments</span>
        </a>
        <a href="{{ route('notification-logs.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('notification-logs.*') && !request()->routeIs('notification-logs.send.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Message History</span>
        </a>
    @endif

    @if (auth()->user()->isSuperAdmin())
        <div class="nav-label px-2 mb-2 mt-2 text-uppercase small text-muted">System setup (developer)</div>
        <a href="{{ route('settings.edit') }}"
            class="sidebar-nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-gear" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">System Settings</span>
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
        <a href="{{ route('parent.bank-payments.index') }}"
            class="sidebar-nav-link {{ request()->routeIs('parent.bank-payments.*') ? 'active' : '' }}">
            <span class="sidebar-nav-icon"><i class="bi bi-bank" aria-hidden="true"></i></span>
            <span class="sidebar-nav-text">Bank Payments</span>
        </a>
    @endif
</nav>
