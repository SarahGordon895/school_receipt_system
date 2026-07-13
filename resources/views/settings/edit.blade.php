@extends('layouts.app')
@section('title', 'System Settings')

@section('content')
<div class="alert alert-info mb-3">
  <i class="bi bi-shield-lock me-2"></i>
  <strong>Developer / system configuration.</strong>
  Configure school branding, SMS templates, bank accounts for parent payments, and fee setup.
  Day-to-day receipts, students, and reports are handled by the <strong>School Admin</strong> account.
</div>

<div class="card mb-3">
  <div class="card-header fw-semibold"><i class="bi bi-building me-2"></i>School Information</div>
  <div class="card-body">
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
      @csrf @method('PUT')

      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="school_name" name="school_name" placeholder="School Name" required
                   value="{{ old('school_name',$setting->school_name) }}">
            <label for="school_name">School Name</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="reg_number" name="reg_number" placeholder="Reg Number"
                   value="{{ old('reg_number',$setting->reg_number) }}">
            <label for="reg_number">Registration Number</label>
          </div>
        </div>

        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="contact_phone" name="contact_phone" placeholder="Phone"
                   value="{{ old('contact_phone',$setting->contact_phone) }}">
            <label for="contact_phone">Contact Phone</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="Email"
                   value="{{ old('contact_email',$setting->contact_email) }}">
            <label for="contact_email">Contact Email</label>
          </div>
        </div>

        <div class="col-md-12">
          <div class="form-floating">
            <input type="text" class="form-control" id="address" name="address" placeholder="Address"
                   value="{{ old('address',$setting->address) }}">
            <label for="address">Address</label>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Logo</label>
          <input type="file" class="form-control" name="logo" accept="image/*">
          @if($setting->logo_path)
            <div class="mt-2 d-flex align-items-center gap-3">
              <img src="{{ asset('storage/'.$setting->logo_path) }}" alt="Logo" class="rounded border" style="height:48px;">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                <label class="form-check-label" for="remove_logo">Remove logo</label>
              </div>
            </div>
          @endif
        </div>

        <div class="col-md-12">
          <div class="form-floating">
            <textarea class="form-control" id="receipt_footer" name="receipt_footer" style="height: 120px"
              placeholder="Footer to print on receipts (optional)">{{ old('receipt_footer',$setting->receipt_footer) }}</textarea>
            <label for="receipt_footer">Receipt Footer (optional)</label>
          </div>
        </div>
      </div>

      <hr class="my-4">

      <h6 class="fw-semibold text-school-primary mb-3"><i class="bi bi-bank me-2"></i>School Bank Accounts (fee payments)</h6>
      <p class="small text-muted">Parents pay school fees into these accounts and upload their NMB or CRDB bank receipt PDF. The system verifies the beneficiary account, amount, and reference automatically.</p>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="bank_nmb_account_name" name="bank_nmb_account_name"
              value="{{ old('bank_nmb_account_name', $setting->bank_nmb_account_name) }}" placeholder="Account name">
            <label for="bank_nmb_account_name">NMB account name</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="bank_nmb_account_number" name="bank_nmb_account_number"
              value="{{ old('bank_nmb_account_number', $setting->bank_nmb_account_number) }}" placeholder="Account number">
            <label for="bank_nmb_account_number">NMB account number</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="bank_crdb_account_name" name="bank_crdb_account_name"
              value="{{ old('bank_crdb_account_name', $setting->bank_crdb_account_name) }}" placeholder="Account name">
            <label for="bank_crdb_account_name">CRDB account name</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="bank_crdb_account_number" name="bank_crdb_account_number"
              value="{{ old('bank_crdb_account_number', $setting->bank_crdb_account_number) }}" placeholder="Account number">
            <label for="bank_crdb_account_number">CRDB account number</label>
          </div>
        </div>
      </div>

      <hr class="my-4">

      <h6 class="fw-semibold text-school-primary mb-3"><i class="bi bi-phone me-2"></i>SMS Notifications</h6>
      <p class="small text-muted">Configure SMS for payment alerts and fee reminders. Use <strong>simulate mode</strong> on localhost (messages are logged, not sent).</p>

      <div class="row g-3">
        <div class="col-md-6">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="sms_enabled" value="1" id="sms_enabled"
              @checked(old('sms_enabled', $setting->sms_enabled))>
            <label class="form-check-label" for="sms_enabled">Enable SMS notifications</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="sms_simulate" value="1" id="sms_simulate"
              @checked(old('sms_simulate', $setting->sms_simulate ?? true))>
            <label class="form-check-label" for="sms_simulate">Simulate SMS (log only — for demo/localhost)</label>
          </div>
        </div>
        <div class="col-md-12">
          <div class="form-floating">
            <input type="url" class="form-control" id="sms_api_endpoint" name="sms_api_endpoint"
              placeholder="https://api.provider.com/send"
              value="{{ old('sms_api_endpoint', $setting->sms_api_endpoint) }}">
            <label for="sms_api_endpoint">SMS API Endpoint (leave empty when simulating)</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="password" class="form-control" id="sms_api_token" name="sms_api_token"
              placeholder="API token" autocomplete="new-password"
              value="{{ old('sms_api_token', $setting->sms_api_token) }}">
            <label for="sms_api_token">SMS API Token</label>
          </div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="sms_sender_id" name="sms_sender_id" placeholder="MBONEA"
              value="{{ old('sms_sender_id', $setting->sms_sender_id ?? 'SCHOOL') }}">
            <label for="sms_sender_id">Sender ID</label>
          </div>
          <div class="form-text">Must match your approved iMart sender ID exactly (e.g. COLLEGE).</div>
        </div>
        <div class="col-md-6">
          <div class="form-floating">
            <input type="text" class="form-control" id="sms_test_phone" name="sms_test_phone"
              placeholder="+2557XXXXXXXX" value="{{ old('sms_test_phone', $setting->contact_phone) }}">
            <label for="sms_test_phone">Test phone (optional — sends test SMS on save)</label>
          </div>
        </div>
      </div>

      <hr class="my-4">
      <h6 class="fw-semibold text-school-primary mb-2"><i class="bi bi-chat-text me-2"></i>SMS Message Templates</h6>
      <p class="small text-muted mb-3">
        Templates are sent automatically by the system at <strong>14 days, 7 days, 3 days, and on the due date</strong> before fees are due, plus daily overdue notices.
        Payment confirmations are sent when a receipt is recorded.
        Placeholders:
        @foreach(\App\Services\NotificationTemplateService::placeholders() as $placeholder)
          <code>{{ $placeholder }}</code>@unless($loop->last), @endunless
        @endforeach
      </p>

      @php $templateDefaults = app(\App\Services\NotificationTemplateService::class)->defaultTemplates(); @endphp

      <div class="row g-3">
        <div class="col-12">
          <label for="sms_template_payment_received" class="form-label">Payment confirmation (sent automatically on payment)</label>
          <textarea class="form-control" id="sms_template_payment_received" name="sms_template_payment_received" rows="2">{{ old('sms_template_payment_received', $setting->sms_template_payment_received ?: $templateDefaults['payment_received']) }}</textarea>
        </div>
        <div class="col-12">
          <label for="sms_template_fee_reminder_14" class="form-label">2-week advance reminder (14 days before due date)</label>
          <textarea class="form-control" id="sms_template_fee_reminder_14" name="sms_template_fee_reminder_14" rows="2">{{ old('sms_template_fee_reminder_14', $setting->sms_template_fee_reminder_14 ?: $templateDefaults['fee_reminder_14']) }}</textarea>
        </div>
        <div class="col-12">
          <label for="sms_template_fee_reminder" class="form-label">Fee reminder (7 days, 3 days, due today — uses same template)</label>
          <textarea class="form-control" id="sms_template_fee_reminder" name="sms_template_fee_reminder" rows="2">{{ old('sms_template_fee_reminder', $setting->sms_template_fee_reminder ?: $templateDefaults['fee_reminder']) }}</textarea>
        </div>
        <div class="col-12">
          <label for="sms_template_overdue" class="form-label">Overdue notice (after due date passed)</label>
          <textarea class="form-control" id="sms_template_overdue" name="sms_template_overdue" rows="2">{{ old('sms_template_overdue', $setting->sms_template_overdue ?: $templateDefaults['overdue']) }}</textarea>
        </div>
      </div>

      <x-form-actions :cancelUrl="route('settings.edit')" submitLabel="Save settings" submitIcon="bi-check-lg" class="mt-4" />
    </form>
  </div>
</div>
@endsection
