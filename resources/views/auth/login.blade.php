<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php
        $loginType = old('login_type', 'school_admin');
    @endphp

    <div class="login-role-tabs" role="tablist" aria-label="Sign in as">
        <button type="button" class="login-role-tab {{ $loginType === 'school_admin' ? 'active' : '' }}"
            data-login-type="school_admin" role="tab"
            aria-selected="{{ $loginType === 'school_admin' ? 'true' : 'false' }}">
            <i class="bi bi-building" aria-hidden="true"></i>
            <span>School Admin</span>
        </button>
        <button type="button" class="login-role-tab {{ $loginType === 'parent' ? 'active' : '' }}"
            data-login-type="parent" role="tab"
            aria-selected="{{ $loginType === 'parent' ? 'true' : 'false' }}">
            <i class="bi bi-phone" aria-hidden="true"></i>
            <span>Parent</span>
        </button>
        <button type="button" class="login-role-tab {{ $loginType === 'super_admin' ? 'active' : '' }}"
            data-login-type="super_admin" role="tab"
            aria-selected="{{ $loginType === 'super_admin' ? 'true' : 'false' }}">
            <i class="bi bi-shield-lock" aria-hidden="true"></i>
            <span>Developer</span>
        </button>
    </div>

    <form method="POST" action="{{ route('login') }}" id="loginForm" class="login-form">
        @csrf
        <input type="hidden" name="login_type" id="login_type" value="{{ $loginType }}">

        <div id="emailField" class="login-field {{ $loginType === 'parent' ? 'd-none' : '' }}">
            <label for="email" class="login-label">
                <span id="emailLabelText">
                    {{ $loginType === 'super_admin' ? __('Personal email') : __('Official email') }}
                </span>
            </label>
            <div class="login-input-wrap">
                <i class="bi bi-envelope" aria-hidden="true"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    autocomplete="username" class="login-input"
                    {{ $loginType === 'parent' ? '' : 'required' }}>
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div id="phoneField" class="login-field {{ $loginType === 'parent' ? '' : 'd-none' }}">
            <label for="phone" class="login-label">{{ __('Phone number') }}</label>
            <div class="login-input-wrap">
                <i class="bi bi-telephone" aria-hidden="true"></i>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                    autocomplete="tel" placeholder="+2557XXXXXXXX" class="login-input"
                    {{ $loginType === 'parent' ? 'required' : '' }}>
            </div>
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="login-field">
            <label for="password" class="login-label">{{ __('Password') }}</label>
            <div class="login-input-wrap">
                <i class="bi bi-lock" aria-hidden="true"></i>
                <input id="password" type="password" name="password" required
                    autocomplete="current-password" class="login-input">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="login-options">
            <label for="remember_me" class="login-remember">
                <input id="remember_me" type="checkbox" name="remember">
                <span>{{ __('Remember me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a id="forgotLink" href="{{ route('password.request') }}"
                    class="login-forgot {{ $loginType === 'parent' ? 'd-none' : '' }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <button type="submit" class="login-submit">
            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
            {{ __('Sign in') }}
        </button>
    </form>

    @push('scripts')
    <script>
        (function () {
            const tabs = document.querySelectorAll('.login-role-tab');
            const loginTypeInput = document.getElementById('login_type');
            const emailField = document.getElementById('emailField');
            const phoneField = document.getElementById('phoneField');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const emailLabel = document.getElementById('emailLabelText');
            const forgotLink = document.getElementById('forgotLink');

            function setType(type) {
                loginTypeInput.value = type;
                tabs.forEach(tab => {
                    const active = tab.dataset.loginType === type;
                    tab.classList.toggle('active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                const isParent = type === 'parent';
                emailField.classList.toggle('d-none', isParent);
                phoneField.classList.toggle('d-none', !isParent);
                forgotLink?.classList.toggle('d-none', isParent);

                emailInput.required = !isParent;
                phoneInput.required = isParent;

                if (type === 'super_admin') {
                    emailLabel.textContent = @json(__('Personal email'));
                } else if (type === 'school_admin') {
                    emailLabel.textContent = @json(__('Official email'));
                }
            }

            tabs.forEach(tab => {
                tab.addEventListener('click', () => setType(tab.dataset.loginType));
            });
        })();
    </script>
    @endpush
</x-guest-layout>
