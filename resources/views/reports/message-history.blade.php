@extends('layouts.app')
@section('title', 'SMS & Email History Report')

@section('actions')
  @if($generated ?? false)
    <form method="POST" action="{{ route('reports.messages.pdf') }}" class="d-inline">
      @csrf
      @foreach($request->all() as $key => $value)
        @if(is_scalar($value) && $value !== '')
          <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
      @endforeach
      <x-icon-btn type="submit" icon="bi-file-earmark-pdf" label="Export PDF" variant="danger" :iconOnly="false" />
    </form>
  @endif
  <x-icon-btn :href="route('notification-logs.index')" icon="bi-journal-text" label="Live message logs" variant="outline-secondary" :iconOnly="false" />
  <x-icon-btn :href="route('reports.index')" icon="bi-arrow-left" label="Back to reports" variant="outline-primary" :iconOnly="false" />
@endsection

@section('content')
<p class="text-muted small mb-3">
  Official report of <strong>SMS and email notifications</strong> recorded in message history (notification logs).
  Use filters below, then export PDF for bursar records.
</p>

<div class="card mb-3">
  <div class="card-header fw-semibold"><i class="bi bi-funnel me-2"></i>Generate message history report</div>
  <div class="card-body">
    <form method="POST" action="{{ route('reports.messages') }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">From date</label>
          <input type="date" name="date_from" class="form-control" value="{{ old('date_from', $request->date_from ?? '') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">To date</label>
          <input type="date" name="date_to" class="form-control" value="{{ old('date_to', $request->date_to ?? '') }}">
        </div>
        <div class="col-md-2">
          <label class="form-label">Channel</label>
          <select name="channel" class="form-select">
            <option value="">All</option>
            <option value="sms" @selected(old('channel', $request->channel ?? '') === 'sms')>SMS</option>
            <option value="email" @selected(old('channel', $request->channel ?? '') === 'email')>Email</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All</option>
            @foreach(['sent','failed','skipped'] as $status)
              <option value="{{ $status }}" @selected(old('status', $request->status ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Student</label>
          <select name="student_id" class="form-select">
            <option value="">All</option>
            @foreach($students ?? [] as $student)
              <option value="{{ $student->id }}" @selected((string) old('student_id', $request->student_id ?? '') === (string) $student->id)>{{ $student->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Search message / student</label>
          <input type="text" name="q" class="form-control" value="{{ old('q', $request->q ?? '') }}" placeholder="Keyword">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-school-primary w-100"><i class="bi bi-search me-1"></i> Generate report</button>
        </div>
      </div>
    </form>
  </div>
</div>

@if($generated ?? false)
<div class="row mb-3 g-3">
  <div class="col-6 col-md-2">
    <div class="card h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Total</div>
      <div class="fs-5 fw-bold">{{ $summary['total'] ?? 0 }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card border-success h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Sent</div>
      <div class="fs-5 fw-bold text-success">{{ $summary['sent'] ?? 0 }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card border-danger h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Failed</div>
      <div class="fs-5 fw-bold text-danger">{{ $summary['failed'] ?? 0 }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card border-warning h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Skipped</div>
      <div class="fs-5 fw-bold text-warning">{{ $summary['skipped'] ?? 0 }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">SMS</div>
      <div class="fs-5 fw-bold">{{ $summary['sms'] ?? 0 }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Email</div>
      <div class="fs-5 fw-bold">{{ $summary['email'] ?? 0 }}</div>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold"><i class="bi bi-chat-dots me-2"></i>Message history</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Student</th>
            <th>Channel</th>
            <th>Event</th>
            <th>Status</th>
            <th>Message</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $log)
            <tr>
              <td>{{ $log->sent_on?->format('d/m/Y') ?? '—' }}</td>
              <td>
                <div class="fw-semibold">{{ $log->student?->name ?? '—' }}</div>
                <small class="text-muted">{{ $log->student?->admission_no ?? '' }}</small>
              </td>
              <td><span class="badge text-bg-secondary">{{ strtoupper($log->channel) }}</span></td>
              <td>{{ str_replace('_', ' ', $log->event_type ?? '—') }}</td>
              <td><span class="badge text-bg-{{ $log->statusBadge() }}">{{ $log->statusLabel() }}</span></td>
              <td class="small">{{ \Illuminate\Support\Str::limit($log->message, 120) }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No messages match the selected filters.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif
@endsection
