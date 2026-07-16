@php
  $primaryLink = $student->primaryParentLink ?? null;
  $selectedParentId = old('parent_user_id', $student->parent_user_id ?? $primaryLink?->parent_user_id);
  $selectedRelationship = old('parent_relationship', $primaryLink?->relationship ?? 'Guardian');
  $portalLoginEmail = old('portal_login_email', $student->parentUser?->email ?? '');
  $defaultParentMode = filled($selectedParentId)
      ? 'existing'
      : (($parentAccounts ?? collect())->isNotEmpty() ? 'existing' : 'new');
  $parentMode = old('parent_mode', $defaultParentMode);
@endphp

@if(isset($student) && $student->exists)
  <div class="col-12">
    <div class="alert alert-light border d-flex flex-wrap gap-3 align-items-center mb-0">
      <div><i class="bi bi-link-45deg text-primary"></i> <strong>Admission link</strong></div>
      @if($primaryLink)
        <span class="badge text-bg-success">Primary {{ $primaryLink->relationship }}</span>
        @if($primaryLink->linked_at)
          <span class="small text-muted">Linked {{ $primaryLink->linked_at->format('d M Y H:i') }}</span>
        @endif
        @if($student->admitted_at)
          <span class="small text-muted">Admitted {{ $student->admitted_at->format('d M Y') }}</span>
        @endif
        @if($primaryLink->linkedBy)
          <span class="small text-muted">By {{ $primaryLink->linkedBy->name }}</span>
        @elseif($student->registeredBy)
          <span class="small text-muted">Registered by {{ $student->registeredBy->name }}</span>
        @endif
      @else
        <span class="badge text-bg-warning">No official link — select or create parent below</span>
      @endif
    </div>
  </div>
@endif

<div class="col-12">
  <label class="form-label fw-semibold"><i class="bi bi-people me-1"></i> Parent / Guardian</label>
  <div class="d-flex flex-wrap gap-3">
    <div class="form-check">
      <input class="form-check-input" type="radio" name="parent_mode" id="parent_mode_existing" value="existing"
        @checked($parentMode === 'existing')>
      <label class="form-check-label" for="parent_mode_existing">Existing parent account</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="radio" name="parent_mode" id="parent_mode_new" value="new"
        @checked($parentMode === 'new')>
      <label class="form-check-label" for="parent_mode_new">New parent account</label>
    </div>
  </div>
  @error('parent_mode')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
</div>

<div class="col-md-6 parent-mode-existing">
  <label class="form-label fw-semibold"><i class="bi bi-person-badge me-1"></i> Parent / Guardian Portal Account <span class="text-danger">*</span></label>
  <select class="form-select @error('parent_user_id') is-invalid @enderror" name="parent_user_id" id="parent_user_id">
    <option value="">— Select parent account —</option>
    @foreach($parentAccounts as $parentAccount)
      <option value="{{ $parentAccount->id }}"
        data-email="{{ $parentAccount->email }}"
        data-name="{{ $parentAccount->name }}"
        data-phone="{{ $parentAccount->phone }}"
        @selected((string) $selectedParentId === (string) $parentAccount->id)>
        {{ $parentAccount->name }} ({{ $parentAccount->email }})
      </option>
    @endforeach
  </select>
  @error('parent_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
  <div class="form-text">Parent uses this account to log in to the portal (phone + password).</div>
</div>

<div class="col-md-6 parent-mode-new">
  <div class="form-floating">
    <input type="email" class="form-control @error('portal_login_email') is-invalid @enderror" id="new_parent_portal_email" name="portal_login_email" placeholder="Portal login email"
      value="{{ $portalLoginEmail }}">
    <label for="new_parent_portal_email">Portal Login Email *</label>
  </div>
  @error('portal_login_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  <div class="form-text">Used as the parent account email. Login still uses phone + password.</div>
</div>
<div class="col-md-3 parent-mode-new">
  <div class="form-floating">
    <input type="password" class="form-control @error('parent_password') is-invalid @enderror" id="parent_password" name="parent_password" placeholder="Password" autocomplete="new-password">
    <label for="parent_password">Portal Password *</label>
  </div>
  @error('parent_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
<div class="col-md-3 parent-mode-new">
  <div class="form-floating">
    <input type="password" class="form-control" id="parent_password_confirmation" name="parent_password_confirmation" placeholder="Confirm password" autocomplete="new-password">
    <label for="parent_password_confirmation">Confirm Password *</label>
  </div>
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold">Relationship to student <span class="text-danger">*</span></label>
  <select class="form-select @error('parent_relationship') is-invalid @enderror" name="parent_relationship" required>
    @foreach(\App\Models\StudentParentLink::RELATIONSHIPS as $rel)
      <option value="{{ $rel }}" @selected($selectedRelationship === $rel)>{{ $rel }}</option>
    @endforeach
  </select>
  @error('parent_relationship')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
  <div class="form-floating">
    <input type="text" class="form-control @error('parent_name') is-invalid @enderror" id="parent_name" name="parent_name" placeholder="Parent name"
      value="{{ old('parent_name', $student->parent_name ?? '') }}">
    <label for="parent_name">Parent / Guardian Name <span class="parent-mode-new text-danger">*</span></label>
  </div>
  @error('parent_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
  <div class="form-floating">
    <input type="text" class="form-control @error('parent_phone') is-invalid @enderror" id="parent_phone" name="parent_phone" placeholder="Parent phone" required
      value="{{ old('parent_phone', $student->parent_phone ?? '') }}">
    <label for="parent_phone">Parent Phone (SMS / Login) *</label>
  </div>
  @error('parent_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
<div class="col-md-4">
  <div class="form-floating">
    <input type="email" class="form-control @error('parent_email') is-invalid @enderror" id="parent_email" name="parent_email" placeholder="Notification email"
      value="{{ old('parent_email', $student->parent_email ?? '') }}">
    <label for="parent_email">Notification Email (SMS alerts companion)</label>
  </div>
  @error('parent_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  <div class="form-text">Fee reminder and payment emails are sent here.</div>
</div>

<div class="col-md-6 parent-mode-existing">
  <div class="form-floating">
    <input type="email" class="form-control @error('portal_login_email') is-invalid @enderror" id="portal_login_email" name="portal_login_email" placeholder="Portal login email"
      value="{{ $portalLoginEmail }}">
    <label for="portal_login_email">Portal Login Email</label>
  </div>
  @error('portal_login_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  <div class="form-text">Changing this updates the parent portal account login email.</div>
</div>
<div class="col-md-6 parent-mode-existing d-flex align-items-end">
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="copy_notification_email">
    <label class="form-check-label" for="copy_notification_email">Use notification email for portal login too</label>
  </div>
</div>
<div class="col-md-6 parent-mode-new d-flex align-items-end">
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="copy_notification_email_new">
    <label class="form-check-label" for="copy_notification_email_new">Use portal email for notifications too</label>
  </div>
</div>

@push('scripts')
<script>
  (function () {
    const form = document.querySelector('form[data-student-form]');
    if (!form) return;

    const modeExisting = document.getElementById('parent_mode_existing');
    const modeNew = document.getElementById('parent_mode_new');
    const select = document.getElementById('parent_user_id');
    const portalEmailExisting = document.getElementById('portal_login_email');
    const portalEmailNew = document.getElementById('new_parent_portal_email');
    const notifyEmail = document.getElementById('parent_email');
    const copyCheckbox = document.getElementById('copy_notification_email');
    const copyCheckboxNew = document.getElementById('copy_notification_email_new');

    function currentMode() {
      return modeNew?.checked ? 'new' : 'existing';
    }

    function setSectionEnabled(selector, enabled) {
      document.querySelectorAll(selector).forEach((el) => {
        el.classList.toggle('d-none', !enabled);
        el.querySelectorAll('input, select, textarea').forEach((input) => {
          if (input.type === 'radio' || input.type === 'checkbox') return;
          input.disabled = !enabled;
          if (input.name === 'parent_user_id') {
            input.required = enabled;
          }
        });
      });
    }

    function syncMode() {
      const isNew = currentMode() === 'new';
      setSectionEnabled('.parent-mode-existing', !isNew);
      setSectionEnabled('.parent-mode-new', isNew);

      // Only one portal_login_email field should submit.
      if (portalEmailExisting) portalEmailExisting.disabled = isNew;
      if (portalEmailNew) portalEmailNew.disabled = !isNew;
    }

    function copyNotifyToPortal() {
      if (copyCheckbox?.checked && notifyEmail && portalEmailExisting && !portalEmailExisting.disabled) {
        portalEmailExisting.value = notifyEmail.value;
      }
    }

    function copyPortalToNotify() {
      if (copyCheckboxNew?.checked && notifyEmail && portalEmailNew && !portalEmailNew.disabled) {
        notifyEmail.value = portalEmailNew.value;
      }
    }

    modeExisting?.addEventListener('change', syncMode);
    modeNew?.addEventListener('change', syncMode);
    copyCheckbox?.addEventListener('change', copyNotifyToPortal);
    notifyEmail?.addEventListener('input', copyNotifyToPortal);
    copyCheckboxNew?.addEventListener('change', copyPortalToNotify);
    portalEmailNew?.addEventListener('input', copyPortalToNotify);

    select?.addEventListener('change', function () {
      if (form.dataset.studentForm === 'edit' || currentMode() !== 'existing') {
        return;
      }

      const opt = select.selectedOptions[0];
      if (!opt || !opt.value) return;

      const name = document.getElementById('parent_name');
      const phone = document.getElementById('parent_phone');
      if (portalEmailExisting && opt.dataset.email) portalEmailExisting.value = opt.dataset.email;
      if (name && opt.dataset.name) name.value = opt.dataset.name;
      if (phone && opt.dataset.phone && !phone.value) phone.value = opt.dataset.phone;
      if (notifyEmail && opt.dataset.email && !notifyEmail.value) notifyEmail.value = opt.dataset.email;
    });

    syncMode();
  })();
</script>
@endpush
