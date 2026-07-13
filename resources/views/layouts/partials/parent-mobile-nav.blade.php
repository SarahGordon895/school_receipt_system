<nav class="parent-mobile-nav d-lg-none" aria-label="Parent quick navigation">
    <a href="{{ route('parent.dashboard') }}"
        class="parent-mobile-nav-link {{ request()->routeIs('parent.dashboard') || request()->routeIs('parent.students.*') ? 'active' : '' }}">
        <i class="bi bi-house-door" aria-hidden="true"></i>
        <span>Portal</span>
    </a>
    <a href="{{ route('parent.notifications') }}"
        class="parent-mobile-nav-link {{ request()->routeIs('parent.notifications*') ? 'active' : '' }}">
        <i class="bi bi-bell" aria-hidden="true"></i>
        <span>SMS/Email</span>
        @if (($parentUnreadNotifications ?? 0) > 0)
            <span class="parent-mobile-nav-badge">{{ $parentUnreadNotifications }}</span>
        @endif
    </a>
    <a href="{{ route('parent.bank-payments.index') }}"
        class="parent-mobile-nav-link {{ request()->routeIs('parent.bank-payments.*') ? 'active' : '' }}">
        <i class="bi bi-bank" aria-hidden="true"></i>
        <span>Bank</span>
    </a>
    <a href="{{ route('profile.edit') }}"
        class="parent-mobile-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
        <i class="bi bi-person-circle" aria-hidden="true"></i>
        <span>Profile</span>
    </a>
</nav>
