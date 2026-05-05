<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', optional($appSetting)->school_name ?? 'School Receipts')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&display=swap" rel="stylesheet">

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('css/school-theme.css') }}" rel="stylesheet">

    @stack('head')
</head>

<body class="school-app-body">
    <nav class="navbar navbar-expand-lg navbar-school fixed-top">
        <div class="container-fluid px-3 px-lg-4">
            <button class="btn btn-link text-dark d-lg-none p-1 me-1" type="button" id="sidebarToggle"
                aria-controls="sidebarSchool" aria-expanded="false" aria-label="Open menu">
                <i class="bi bi-list fs-3"></i>
            </button>

            @php
                $homeRoute = auth()->check() && auth()->user()->isParent() ? 'parent.dashboard' : 'dashboard';
            @endphp
            <a class="navbar-brand d-flex align-items-center gap-2 text-truncate" href="{{ route($homeRoute) }}">
                @if ($appSetting?->logo_path)
                    <img src="{{ asset('storage/' . $appSetting->logo_path) }}" alt=""
                        class="rounded flex-shrink-0" style="height:32px;width:auto;object-fit:contain;">
                @endif
                <span class="text-truncate">{{ optional($appSetting)->school_name ?? 'School Receipts' }}</span>
            </a>

            <button class="navbar-toggler border-0 shadow-none px-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarActions" aria-controls="navbarActions" aria-expanded="false"
                aria-label="Toggle actions">
                <i class="bi bi-three-dots-vertical fs-4"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-end" id="navbarActions">
                <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 mt-3 mt-lg-0">
                    @yield('actions')

                    @if(auth()->user()->hasRole('super_admin', 'school_admin'))
                        <a href="{{ route('receipts.create') }}" class="btn btn-primary order-lg-last">
                            <i class="bi bi-receipt-cutoff me-1"></i> Generate Receipt
                        </a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="d-grid d-lg-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary w-100">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop" hidden></div>

    <aside class="sidebar-school" id="sidebarSchool" aria-label="Main navigation">
        <div class="p-3 pt-4">
            <div class="nav-label px-2 mb-2">Menu</div>
            <div class="list-group list-group-flush">
                @if(auth()->user()->hasRole('super_admin', 'school_admin'))
                    <a href="{{ route('dashboard') }}"
                        class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>
                    <a href="{{ route('receipts.index') }}"
                        class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('receipts.*') ? 'active' : '' }}">
                        <i class="bi bi-receipt me-2"></i> Receipts
                    </a>
                    <a href="{{ route('reports.index') }}"
                        class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="bi bi-graph-up me-2"></i> Reports
                    </a>
                    <a href="{{ route('notification-logs.index') }}"
                        class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('notification-logs.*') ? 'active' : '' }}">
                        <i class="bi bi-bell me-2"></i> Notification Logs
                    </a>
                    <a href="{{ route('students.index') }}"
                        class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('students.*') ? 'active' : '' }}">
                        <i class="bi bi-people me-2"></i> Students
                    </a>
                    <a href="{{ route('fee-structures.index') }}"
                        class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('fee-structures.*') ? 'active' : '' }}">
                        <i class="bi bi-cash-coin me-2"></i> Fee Structures
                    </a>
                    <a href="{{ route('payment-categories.index') }}"
                        class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('payment-categories.*') ? 'active' : '' }}">
                        <i class="bi bi-tags me-2"></i> Payment Categories
                    </a>
                    <a href="{{ route('settings.edit') }}"
                        class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                @endif

                @if(auth()->user()->isParent())
                    <a href="{{ route('parent.dashboard') }}"
                        class="list-group-item list-group-item-action d-flex align-items-center {{ request()->routeIs('parent.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-person-vcard me-2"></i> Parent Portal
                    </a>
                    <a href="{{ route('parent.notifications') }}"
                        class="list-group-item list-group-item-action d-flex align-items-center justify-content-between {{ request()->routeIs('parent.notifications') ? 'active' : '' }}">
                        <span><i class="bi bi-bell me-2"></i> My Notifications</span>
                        @if(($parentUnreadNotifications ?? 0) > 0)
                            <span class="badge rounded-pill text-bg-primary">{{ $parentUnreadNotifications }}</span>
                        @endif
                    </a>
                @endif
            </div>
        </div>
    </aside>

    <main class="school-main">
        <div class="container-fluid px-0">

            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Please fix the following:</div>
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @hasSection('content')
                <h1 class="page-title-school">@yield('title')</h1>
                @yield('content')
            @else
                {{ $slot }}
            @endif
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const sidebar = document.getElementById('sidebarSchool');
            const toggle = document.getElementById('sidebarToggle');
            const backdrop = document.getElementById('sidebarBackdrop');

            function closeSidebar() {
                sidebar?.classList.remove('show');
                backdrop?.classList.remove('show');
                backdrop?.setAttribute('hidden', '');
                toggle?.setAttribute('aria-expanded', 'false');
            }

            function openSidebar() {
                sidebar?.classList.add('show');
                backdrop?.classList.add('show');
                backdrop?.removeAttribute('hidden');
                toggle?.setAttribute('aria-expanded', 'true');
            }

            toggle?.addEventListener('click', () => {
                if (sidebar?.classList.contains('show')) closeSidebar();
                else openSidebar();
            });

            backdrop?.addEventListener('click', closeSidebar);

            window.addEventListener('resize', () => {
                if (window.matchMedia('(min-width: 992px)').matches) closeSidebar();
            });
        })();
    </script>
    @stack('scripts')
</body>

</html>
