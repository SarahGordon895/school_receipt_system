<p class="small text-muted mb-3">
    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted.') }}
</p>

<x-icon-btn type="button" icon="trash" :label="__('Delete account')" variant="danger" :iconOnly="false"
    data-bs-toggle="modal" data-bs-target="#confirmUserDeletionModal" />

<div class="modal fade" id="confirmUserDeletionModal" tabindex="-1" aria-labelledby="confirmUserDeletionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                <div class="modal-header border-danger-subtle">
                    <h5 class="modal-title text-danger" id="confirmUserDeletionLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>{{ __('Delete account?') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        {{ __('Please enter your password to confirm permanent deletion.') }}
                    </p>
                    <div class="form-floating">
                        <input type="password" class="form-control @if($errors->userDeletion->has('password')) is-invalid @endif"
                            id="delete_password" name="password" placeholder="Password" required>
                        <label for="delete_password">{{ __('Password') }}</label>
                        @foreach ($errors->userDeletion->get('password') as $message)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <x-icon-btn type="button" icon="x-lg" :label="__('Cancel')" variant="outline-secondary" :iconOnly="false"
                        data-bs-dismiss="modal" />
                    <x-icon-btn type="submit" icon="trash" :label="__('Delete permanently')" variant="danger" :iconOnly="false" />
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->userDeletion->isNotEmpty())
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmUserDeletionModal')).show();
        });
    </script>
    @endpush
@endif
