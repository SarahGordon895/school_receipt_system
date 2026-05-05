@extends('layouts.app')
@section('title','My Notifications')

@section('actions')
  <form method="POST" action="{{ route('parent.notifications.read-all') }}" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-primary">
      <i class="bi bi-check2-all me-1"></i> Mark all as read
    </button>
  </form>
  <a href="{{ route('parent.dashboard') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Back to Parent Portal
  </a>
@endsection

@section('content')
<div class="row g-3 mb-3">
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Total</div>
        <div class="fs-5 fw-semibold">{{ $stats['total'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Sent</div>
        <div class="fs-5 fw-semibold text-success">{{ $stats['sent'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Failed</div>
        <div class="fs-5 fw-semibold text-danger">{{ $stats['failed'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">SMS</div>
        <div class="fs-5 fw-semibold">{{ $stats['sms'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Email</div>
        <div class="fs-5 fw-semibold">{{ $stats['email'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Unread</div>
        <div class="fs-5 fw-semibold text-primary">{{ $stats['unread'] }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header fw-semibold">Filter Notifications</div>
  <div class="card-body">
    <form method="GET" class="row g-2">
      <div class="col-12 col-md-3">
        <label class="form-label small text-muted mb-1">Search</label>
        <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}"
               placeholder="Student name, admission, message...">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small text-muted mb-1">Student</label>
        <select name="student_id" class="form-select">
          <option value="">All</option>
          @foreach($students as $student)
            <option value="{{ $student->id }}" @selected((string)($filters['student_id'] ?? '') === (string)$student->id)>
              {{ $student->name }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small text-muted mb-1">Channel</label>
        <select name="channel" class="form-select">
          <option value="">All</option>
          <option value="email" @selected(($filters['channel'] ?? '') === 'email')>Email</option>
          <option value="sms" @selected(($filters['channel'] ?? '') === 'sms')>SMS</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label small text-muted mb-1">Status</label>
        <select name="status" class="form-select">
          <option value="">All</option>
          <option value="sent" @selected(($filters['status'] ?? '') === 'sent')>Sent</option>
          <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Failed</option>
          <option value="skipped" @selected(($filters['status'] ?? '') === 'skipped')>Skipped</option>
        </select>
      </div>
      <div class="col-6 col-md-1">
        <label class="form-label small text-muted mb-1">From</label>
        <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
      </div>
      <div class="col-6 col-md-1">
        <label class="form-label small text-muted mb-1">To</label>
        <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
      </div>
      <div class="col-12 col-md-1 d-flex align-items-end">
        <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i></button>
      </div>
      <div class="col-12 col-md-1 d-flex align-items-end">
        <a href="{{ route('parent.notifications') }}" class="btn btn-outline-secondary w-100">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
    <span>Notifications sent to your students</span>
    <span class="badge text-bg-light">{{ $logs->total() }} total</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Student</th>
            <th>Channel</th>
            <th>Status</th>
            <th>Message</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($logs as $log)
            <tr>
              <td class="text-nowrap">{{ $log->sent_on?->format('Y-m-d') }}</td>
              <td>
                <div class="fw-semibold">{{ $log->student?->name ?? 'Unknown' }}</div>
                <div class="small text-muted">{{ $log->student?->admission_no ?? '' }}</div>
              </td>
              <td><span class="badge text-bg-secondary text-uppercase">{{ $log->channel }}</span></td>
              <td>
                @php
                  $badge = match ($log->status) {
                    'sent' => 'success',
                    'failed' => 'danger',
                    'skipped' => 'warning',
                    default => 'secondary',
                  };
                @endphp
                <span class="badge text-bg-{{ $badge }}">{{ $log->status }}</span>
              </td>
              <td class="small text-muted">
                {{ $log->message ?: '—' }}
                @if(!$log->read_at)
                  <span class="badge text-bg-primary ms-2">Unread</span>
                @endif
              </td>
              <td class="text-end">
                @if(!$log->read_at)
                  <form method="POST" action="{{ route('parent.notifications.read', $log) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary">Mark as read</button>
                  </form>
                @else
                  <span class="small text-success">Read {{ $log->read_at?->format('Y-m-d H:i') }}</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted py-4">No notification history available yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">{{ $logs->links() }}</div>
</div>
@endsection
