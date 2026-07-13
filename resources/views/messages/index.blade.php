@extends('layouts.app')
@section('title', 'SMS & Email Centre')

@section('actions')
    <x-icon-btn :href="route('notification-logs.send.create')" icon="bi-send" label="Send now" variant="primary" :iconOnly="false" />
    <x-icon-btn :href="route('notification-logs.index')" icon="bi-journal-text" label="Message history" variant="outline-secondary" :iconOnly="false" />
@endsection

@section('content')
<div class="page-hero-school mb-3">
  <p class="mb-0"><i class="bi bi-chat-dots me-2"></i>SMS &amp; Email to parents — manual bulk/individual send and automated fee events</p>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card h-100 border-primary-subtle">
      <div class="card-body py-3">
        <div class="small text-muted">Parents to remind (unpaid)</div>
        <div class="fs-4 fw-bold text-primary">{{ $stats['unpaid_with_contact'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">SMS sent this month</div>
        <div class="fs-4 fw-bold text-success">{{ $stats['sms_sent_month'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Emails sent this month</div>
        <div class="fs-4 fw-bold text-info">{{ $stats['email_sent_month'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Failed this month</div>
        <div class="fs-4 fw-bold text-danger">{{ $stats['failed_month'] }}</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card h-100 border-success">
      <div class="card-header fw-semibold text-success"><i class="bi bi-send me-2"></i>Manual send (school bursar)</div>
      <div class="card-body">
        <p class="small text-muted">Choose an <strong>event template</strong>, select <strong>1 to 5 parents only</strong>, then send by <strong>SMS</strong>, <strong>email</strong>, or both.</p>
        <ul class="small mb-3">
          <li>Pick 1–5 parents per batch (not all)</li>
          <li>Template preview updates as you change message type</li>
          <li>Unpaid report also uses the same 1–5 limit</li>
        </ul>
        <a href="{{ route('notification-logs.send.create') }}" class="btn btn-success w-100 mb-2">
          <i class="bi bi-send me-1"></i> Open manual SMS &amp; Email send
        </a>
        <a href="{{ route('reports.unpaid') }}" class="btn btn-outline-success w-100">
          <i class="bi bi-exclamation-triangle me-1"></i> Unpaid list → quick send per student
        </a>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header fw-semibold"><i class="bi bi-lightning me-2"></i>Automated triggers (system)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr><th>Event</th><th>When it fires</th><th>Channels</th></tr>
            </thead>
            <tbody>
              @foreach($automatedEvents as $row)
                <tr>
                  <td class="fw-semibold">{{ $row['event'] }}</td>
                  <td class="small">{{ $row['when'] }}</td>
                  <td><span class="badge text-bg-secondary">{{ $row['channels'] }}</span></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <p class="small text-muted p-3 mb-0">Scheduler: <code>php artisan fees:send-reminders</code> daily at 06:00. Template wording is configured by the developer under System Settings.</p>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold"><i class="bi bi-chat-text me-2"></i>Message templates by event</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>Event</th><th>Template text (SMS &amp; email use same wording)</th><th></th></tr>
        </thead>
        <tbody>
          @foreach($templates as $type => $meta)
            <tr>
              <td class="fw-semibold text-nowrap">{{ $meta['label'] }}</td>
              <td class="small">{{ Str::limit($meta['body'], 120) }}</td>
              <td class="text-end">
                <a href="{{ route('notification-logs.send.create', ['message_type' => $type]) }}" class="btn btn-sm btn-outline-primary">Use template</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
