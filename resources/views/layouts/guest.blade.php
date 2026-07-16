@php
    $guestPage = match (\Illuminate\Support\Facades\Route::currentRouteName()) {
        'register' => ['title' => __('Register'), 'heading' => __('Create account')],
        'password.request' => ['title' => __('Password help'), 'heading' => __('Reset password')],
        'password.reset' => ['title' => __('New password'), 'heading' => __('New password')],
        'verification.notice' => ['title' => __('Verify email'), 'heading' => __('Verify email')],
        'password.confirm' => ['title' => __('Security'), 'heading' => __('Confirm password')],
        default => ['title' => __('Sign in'), 'heading' => __('Welcome back')],
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ optional($appSetting)->school_name ?? config('app.name', 'FTRS') }} — {{ $guestPage['title'] }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400..800;1,400..800&display=swap" rel="stylesheet">

    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    @include('layouts.partials.icons-head')
    <link href="{{ asset('css/school-theme.css') }}" rel="stylesheet">
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="guest-body">
    <div class="guest-shell">
        <aside class="guest-brand-panel">
            <div class="guest-brand-inner">
                <div class="guest-brand-top">
                    @if ($appSetting?->logo_path)
                        <img src="{{ asset('storage/' . $appSetting->logo_path) }}" alt=""
                            class="guest-brand-logo">
                    @else
                        <div class="guest-brand-mark">
                            {{ \Illuminate\Support\Str::substr(optional($appSetting)->school_name ?? 'M', 0, 1) }}
                        </div>
                    @endif
                    <h1 class="guest-brand-title">
                        {{ optional($appSetting)->school_name ?? 'Mbonea Secondary School' }}
                    </h1>
                    <p class="guest-brand-tagline">Fee Tracking &amp; Receipt System</p>
                </div>

                <div class="guest-brand-features">
                    <div class="guest-feature">
                        <span class="guest-feature-icon"><i class="bi bi-receipt-cutoff"></i></span>
                        <span>Receipts &amp; payments</span>
                    </div>
                    <div class="guest-feature">
                        <span class="guest-feature-icon"><i class="bi bi-bell"></i></span>
                        <span>SMS &amp; email alerts</span>
                    </div>
                    <div class="guest-feature">
                        <span class="guest-feature-icon"><i class="bi bi-graph-up-arrow"></i></span>
                        <span>Reports &amp; balances</span>
                    </div>
                </div>

                @if ($appSetting?->address || $appSetting?->contact_phone)
                    <div class="guest-brand-footer">
                        @if ($appSetting?->address)
                            <p><i class="bi bi-geo-alt"></i> {{ $appSetting->address }}</p>
                        @endif
                        @if ($appSetting?->contact_phone)
                            <p><i class="bi bi-telephone"></i> {{ $appSetting->contact_phone }}</p>
                        @endif
                    </div>
                @endif
            </div>
            <div class="guest-brand-pattern" aria-hidden="true"></div>
        </aside>

        <main class="guest-main">
            <div class="guest-card">
                <h2 class="guest-card-title">{{ $guestPage['heading'] }}</h2>
                {{ $slot }}
            </div>
            <p class="guest-copyright">
                &copy; {{ date('Y') }} {{ optional($appSetting)->school_name ?? 'School' }}
            </p>
        </main>
    </div>
    @stack('scripts')
</body>

</html>
