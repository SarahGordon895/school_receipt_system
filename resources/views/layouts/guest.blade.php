@php
    $guestPage = match (\Illuminate\Support\Facades\Route::currentRouteName()) {
        'register' => [
            'title' => __('Register'),
            'heading' => __('New staff account'),
            'intro' => __('Create your account to access receipts, reports, and student records.'),
        ],
        'password.request' => [
            'title' => __('Password help'),
            'heading' => __('Reset your password'),
            'intro' => __('We will email you a secure link to choose a new password.'),
        ],
        'password.reset' => [
            'title' => __('New password'),
            'heading' => __('Set a new password'),
            'intro' => __('Choose a strong password to protect school financial data.'),
        ],
        'verification.notice' => [
            'title' => __('Verify email'),
            'heading' => __('Confirm your email'),
            'intro' => __('Please verify your email address before using the system.'),
        ],
        'password.confirm' => [
            'title' => __('Security check'),
            'heading' => __('Confirm your password'),
            'intro' => __('This area is sensitive. Confirm your password to continue.'),
        ],
        default => [
            'title' => __('Sign in'),
            'heading' => __('Sign in to continue'),
            'intro' => __(''),
        ],
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ optional($appSetting)->school_name ?? config('app.name', 'Laravel') }} — {{ $guestPage['title'] }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400..700;1,9..40,400..700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased text-gray-900 min-h-screen bg-school-surface">
    <div class="min-h-screen flex flex-col lg:flex-row">
        <div
            class="lg:w-5/12 xl:w-2/5 bg-gradient-to-br from-school-primary via-school-primary to-school-primary-hover text-white px-8 py-12 lg:py-16 flex flex-col justify-between shrink-0">
            <div>
                @if ($appSetting?->logo_path)
                    <img src="{{ asset('storage/' . $appSetting->logo_path) }}" alt=""
                        class="h-14 w-auto object-contain mb-6 bg-white/10 rounded-lg p-2">
                @else
                    <div
                        class="inline-flex h-14 w-14 items-center justify-center rounded-xl bg-white/15 text-2xl font-bold mb-6 border border-white/20">
                        {{ \Illuminate\Support\Str::substr(optional($appSetting)->school_name ?? 'M', 0, 1) }}
                    </div>
                @endif
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight leading-tight">
                    {{ optional($appSetting)->school_name ?? 'School Receipt System' }}
                </h1>
                <p class="mt-4 text-white/85 text-sm sm:text-base max-w-md leading-relaxed">
                    {{ $guestPage['intro'] }}
                </p>
            </div>
            <div class="mt-10 lg:mt-0 text-xs sm:text-sm text-white/70 space-y-1">
                @if ($appSetting?->address)
                    <p class="flex items-start gap-2">
                        <span class="opacity-80 shrink-0"><i class="bi bi-geo-alt"></i></span>
                        <span>{{ $appSetting->address }}</span>
                    </p>
                @endif
                @if ($appSetting?->contact_phone)
                    <p><i class="bi bi-telephone me-2 opacity-80"></i>{{ $appSetting->contact_phone }}</p>
                @endif
            </div>
        </div>

        <div class="flex-1 flex items-center justify-center p-6 sm:p-10 lg:p-14">
            <div class="w-full max-w-md">
                <div class="rounded-2xl bg-white shadow-lg shadow-school-primary/5 border border-gray-200/80 p-6 sm:p-8">
                    <p class="text-sm font-medium text-school-muted uppercase tracking-wider mb-1">{{ __('Staff access') }}</p>
                    <h2 class="text-xl font-bold text-school-primary mb-6">{{ $guestPage['heading'] }}</h2>
                    {{ $slot }}
                </div>
                <p class="text-center text-xs text-school-muted mt-6">
                    &copy; {{ date('Y') }} {{ optional($appSetting)->school_name ?? 'School' }}. {{ __('For official use only.') }}
                </p>
            </div>
        </div>
    </div>
</body>

</html>
