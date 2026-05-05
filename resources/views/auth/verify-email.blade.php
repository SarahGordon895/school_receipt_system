<x-guest-layout>
    <div class="mb-4 text-sm text-school-muted leading-relaxed">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-700 bg-green-50 border border-green-200/80 rounded-lg px-3 py-2">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-6 flex flex-col sm:flex-row sm:items-center gap-4 sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}" class="w-full sm:w-auto">
            @csrf
            <x-primary-button class="w-full sm:w-auto justify-center">
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-auto">
            @csrf
            <button type="submit"
                class="w-full sm:w-auto text-sm font-medium text-school-muted hover:text-school-primary py-2.5 px-4 rounded-lg border border-gray-200 hover:border-school-primary/30 hover:bg-school-surface/80 transition focus:outline-none focus:ring-2 focus:ring-school-accent focus:ring-offset-2">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
