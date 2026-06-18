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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400..800;1,400..800&family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&display=swap" rel="stylesheet">

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    @include('layouts.partials.icons-head')
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
                    <div class="user-chip d-none d-lg-flex order-lg-first me-lg-2">
                        <span class="user-chip-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="user-chip-meta">
                            <span class="user-chip-name d-block">{{ auth()->user()->name }}</span>
                            <span class="user-chip-id d-block">{{ auth()->user()->login_identifier }}</span>
                        </span>
                    </div>

                    @yield('actions')

                    @if(auth()->user()->hasRole('super_admin', 'school_admin'))
                        <x-icon-btn :href="route('receipts.create')" icon="receipt-cutoff" label="Generate receipt"
                            variant="primary" :iconOnly="false" class="order-lg-last" />
                    @endif

                    <x-icon-btn :href="route('profile.edit')" icon="person-circle" label="My profile"
                        variant="outline-secondary" :iconOnly="false" />

                    <form method="POST" action="{{ route('logout') }}" class="d-grid d-lg-inline">
                        @csrf
                        <x-icon-btn type="submit" icon="box-arrow-right" label="Logout" variant="outline-secondary"
                            class="w-100 w-lg-auto" />
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar-backdrop d-lg-none" id="sidebarBackdrop" hidden></div>

    <aside class="sidebar-school" id="sidebarSchool" aria-label="Main navigation">
        <div class="p-3 pt-4">
            <div class="nav-label px-2 mb-2">Menu</div>
            @include('layouts.partials.sidebar-nav')
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

            @if (session('warning'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <div class="fw-semibold mb-1">Please fix the following:</div>
                    <ul class="mb-0 small">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

            document.querySelectorAll('.form-with-loading').forEach((form) => {
                form.addEventListener('submit', () => {
                    const btn = form.querySelector('button[type="submit"]');
                    if (!btn || btn.disabled) return;
                    btn.disabled = true;
                    const label = form.dataset.loadingLabel || 'Working…';
                    const text = btn.querySelector('.btn-icon-text');
                    if (text) text.textContent = label;
                    btn.classList.add('is-loading');
                });
            });
        })();
    </script>
    @stack('scripts')
</body>

</html>
