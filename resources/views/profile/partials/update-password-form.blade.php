<p class="small text-muted mb-3">
    {{ __('Ensure your account is using a long, random password to stay secure.') }}
</p>

<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="row g-3">
        <div class="col-12">
            <div class="form-floating">
                <input type="password" class="form-control @if($errors->updatePassword->has('current_password')) is-invalid @endif"
                    id="update_password_current_password" name="current_password" placeholder="Current password" autocomplete="current-password">
                <label for="update_password_current_password">{{ __('Current Password') }}</label>
                @foreach ($errors->updatePassword->get('current_password') as $message)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @endforeach
            </div>
        </div>
        <div class="col-12">
            <div class="form-floating">
                <input type="password" class="form-control @if($errors->updatePassword->has('password')) is-invalid @endif"
                    id="update_password_password" name="password" placeholder="New password" autocomplete="new-password">
                <label for="update_password_password">{{ __('New Password') }}</label>
                @foreach ($errors->updatePassword->get('password') as $message)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @endforeach
            </div>
        </div>
        <div class="col-12">
            <div class="form-floating">
                <input type="password" class="form-control @if($errors->updatePassword->has('password_confirmation')) is-invalid @endif"
                    id="update_password_password_confirmation" name="password_confirmation" placeholder="Confirm password" autocomplete="new-password">
                <label for="update_password_password_confirmation">{{ __('Confirm Password') }}</label>
                @foreach ($errors->updatePassword->get('password_confirmation') as $message)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mt-3">
        <x-icon-btn type="submit" icon="key" :label="__('Update password')" variant="primary" :iconOnly="false" />
        @if (session('status') === 'password-updated')
            <span class="small text-success"><i class="bi bi-check-circle me-1"></i>{{ __('Saved.') }}</span>
        @endif
    </div>
</form>
