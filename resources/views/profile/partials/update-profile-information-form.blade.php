<p class="small text-muted mb-3">
    {{ __("Update your account's profile information and email address.") }}
</p>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="row g-3">
        <div class="col-12">
            <div class="form-floating">
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                    value="{{ old('name', $user->name) }}" placeholder="Name" required autocomplete="name">
                <label for="name">{{ __('Name') }}</label>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="col-12">
            <div class="form-floating">
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                    value="{{ old('email', $user->email) }}" placeholder="Email" required autocomplete="username">
                <label for="email">{{ __('Email') }}</label>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 small">
                    <p class="text-muted mb-1">{{ __('Your email address is unverified.') }}</p>
                    <button form="send-verification" type="submit" class="btn btn-link btn-sm p-0 align-baseline">
                        <i class="bi bi-envelope-arrow-up me-1"></i>{{ __('Re-send verification email') }}
                    </button>
                    @if (session('status') === 'verification-link-sent')
                        <p class="text-success mt-1 mb-0">{{ __('A new verification link has been sent.') }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mt-3">
        <x-icon-btn type="submit" icon="check-lg" :label="__('Save profile')" variant="primary" :iconOnly="false" />
        @if (session('status') === 'profile-updated')
            <span class="small text-success"><i class="bi bi-check-circle me-1"></i>{{ __('Saved.') }}</span>
        @endif
    </div>
</form>
