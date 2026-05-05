<x-guest-layout>
    <div class="mb-4 text-sm text-school-muted leading-relaxed">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 mt-6">
            <a class="text-sm text-center sm:text-start text-school-muted hover:text-school-primary focus:outline-none focus:ring-2 focus:ring-school-accent focus:ring-offset-2 rounded-md"
                href="{{ route('login') }}">
                <i class="bi bi-arrow-left me-1"></i>{{ __('Back to sign in') }}
            </a>
            <x-primary-button class="w-full sm:w-auto justify-center">
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
