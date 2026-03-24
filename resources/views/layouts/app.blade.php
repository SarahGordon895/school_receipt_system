<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'School Receipts')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Bootstrap 5 + Icons (CDN for simplicity) --}}
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background: #f6f7fb;
        }

        .sidebar {
            width: 260px;
            background: #fff;
            border-right: 1px solid #e9ecef;
            position: fixed;
            top: 56px;
            bottom: 0;
            left: 0;
            overflow-y: auto;
        }

        .content-wrap {
            margin-left: 260px;
            padding: 1.25rem;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                left: -260px;
                transition: all .25s;
            }

            .sidebar.show {
                left: 0;
            }

            .content-wrap {
                margin-left: 0;
            }
        }

        /* Subtle card shadow */
        .card {
            border: 1px solid #e9ecef;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
        }
    </style>

    @stack('head')
</head>

<body>
    {{-- Topbar --}}
    <nav class="navbar navbar-expand-lg bg-white border-bottom fixed-top">
        <div class="container-fluid">
            <button class="btn d-lg-none me-2" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bi bi-list fs-4"></i>
            </button>
            <a class="navbar-brand fw-semibold d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                @if ($appSetting?->logo_path)
                    <img src="{{ asset('/public/storage/' . $appSetting->logo_path) }}" alt="Logo"
                        style="height:28px;width:auto;">
                @endif
                <span>{{ $appSetting->school_name ?? 'School Receipts' }}</span>
            </a>


            <div class="ms-auto d-flex align-items-center gap-2">
                {{-- Optional per-page actions --}}
                @yield('actions')

                <a href="{{ route('receipts.create') }}" class="btn btn-primary">
                    <i class="bi bi-receipt-cutoff me-1"></i> Generate Receipt
                </a>

                <form method="POST" action="{{ route('logout') }}" class="ms-2">
                    @csrf
                    <button class="btn btn-outline-secondary">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Sidebar --}}
    <aside class="sidebar" id="sidebar">
        <div class="p-3">
            <div class="text-uppercase text-muted small mb-2">Navigation</div>
            <div class="list-group list-group-flush">
                <a href="{{ route('dashboard') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a href="{{ route('receipts.index') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-receipt me-2"></i> Receipts
                </a>
                <a href="{{ route('reports.index') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-graph-up me-2"></i> Reports
                </a>
                <a href="{{ route('students.index') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-people me-2"></i> Students
                </a>

                <a href="{{ route('payment-categories.index') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-tags me-2"></i> Payment Categories
                </a>


                <a href="{{ route('classes.index') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-collection me-2"></i> Classes
                </a>
                <a href="{{ route('streams.index') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-diagram-3 me-2"></i> Streams
                </a>
                <a href="{{ route('settings.edit') }}"
                    class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-gear me-2"></i> Settings
                </a>
            </div>
        </div>
    </aside>

    {{-- Content --}}
    <main class="content-wrap">
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
                    <ul class="mb-0">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <h4 class="mb-3">@yield('title')</h4>
            @yield('content')
        </div>
    </main>

    {{-- JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    </script>
    @stack('scripts')
</body>

</html>
