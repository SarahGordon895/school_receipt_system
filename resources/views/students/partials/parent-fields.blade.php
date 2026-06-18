@php
  $primaryLink = $student->primaryParentLink ?? null;
  $selectedParentId = old('parent_user_id', $student->parent_user_id ?? $primaryLink?->parent_user_id);
  $selectedRelationship = old('parent_relationship', $primaryLink?->relationship ?? 'Guardian');
  $portalLoginEmail = old('portal_login_email', $student->parentUser?->email ?? '');
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
        <span class="badge text-bg-warning">No official link — select parent below</span>
      @endif
    </div>
  </div>
@endif

<div class="col-md-6">
  <label class="form-label fw-semibold"><i class="bi bi-person-badge me-1"></i> Parent / Guardian Portal Account <span class="text-danger">*</span></label>
  <select class="form-select @error('parent_user_id') is-invalid @enderror" name="parent_user_id" id="parent_user_id" required>
    <option value="">— Select parent account —</option>
    @foreach($parentAccounts as $parentAccount)
      <option value="{{ $parentAccount->id }}"
        data-email="{{ $parentAccount->email }}"
        data-name="{{ $parentAccount->name }}"
        @selected((string) $selectedParentId === (string) $parentAccount->id)>
        {{ $parentAccount->name }} ({{ $parentAccount->email }})
      </option>
    @endforeach
  </select>
  @error('parent_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
  <div class="form-text">Parent uses this account to log in to the portal (phone + password).</div>
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
    <input type="text" class="form-control" id="parent_name" name="parent_name" placeholder="Parent name"
      value="{{ old('parent_name', $student->parent_name ?? '') }}">
    <label for="parent_name">Parent / Guardian Name</label>
  </div>
</div>
<div class="col-md-4">
  <div class="form-floating">
    <input type="text" class="form-control @error('parent_phone') is-invalid @enderror" id="parent_phone" name="parent_phone" placeholder="Parent phone" required
      value="{{ old('parent_phone', $student->parent_phone ?? '') }}">
    <label for="parent_phone">Parent Phone (SMS) *</label>
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
<div class="col-md-6">
  <div class="form-floating">
    <input type="email" class="form-control @error('portal_login_email') is-invalid @enderror" id="portal_login_email" name="portal_login_email" placeholder="Portal login email"
      value="{{ $portalLoginEmail }}">
    <label for="portal_login_email">Portal Login Email</label>
  </div>
  @error('portal_login_email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  <div class="form-text">Changing this updates the parent portal account login email.</div>
</div>
<div class="col-md-6 d-flex align-items-end">
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="copy_notification_email">
    <label class="form-check-label" for="copy_notification_email">Use notification email for portal login too</label>
  </div>
</div>

@push('scripts')
<script>
  (function () {
    const select = document.getElementById('parent_user_id');
    const form = select?.closest('form');
    const portalEmail = document.getElementById('portal_login_email');
    const notifyEmail = document.getElementById('parent_email');
    const copyCheckbox = document.getElementById('copy_notification_email');
    if (!select || !form) return;

    function copyNotifyToPortal() {
      if (copyCheckbox?.checked && notifyEmail && portalEmail) {
        portalEmail.value = notifyEmail.value;
      }
    }

    copyCheckbox?.addEventListener('change', copyNotifyToPortal);
    notifyEmail?.addEventListener('input', copyNotifyToPortal);

    select.addEventListener('change', function () {
      if (form.dataset.studentForm === 'edit') {
        return;
      }

      const opt = select.selectedOptions[0];
      if (!opt || !opt.value) return;

      const name = document.getElementById('parent_name');
      if (portalEmail && opt.dataset.email) portalEmail.value = opt.dataset.email;
      if (name && opt.dataset.name) name.value = opt.dataset.name;
      if (notifyEmail && opt.dataset.email && !notifyEmail.value) notifyEmail.value = opt.dataset.email;
    });
  })();
</script>
@endpush
