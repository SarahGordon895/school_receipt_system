@extends('layouts.app')
@section('title', 'Notification Logs')

@section('content')
<div class="card mb-3">
    <div class="card-header fw-semibold">Filter reminder activity</div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">Student name / admission</label>
                <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Search">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label small text-muted">Student</label>
                <select name="student_id" class="form-select">
                    <option value="">All students</option>
                    @foreach($students as $s)
                        <option value="{{ $s->id }}" @selected(($filters['student_id'] ?? '') == $s->id)>
                            {{ $s->name }}{{ $s->admission_no ? ' ('.$s->admission_no.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted">Channel</label>
                <select name="channel" class="form-select">
                    <option value="">All</option>
                    <option value="email" @selected(($filters['channel'] ?? '') === 'email')>Email</option>
                    <option value="sms" @selected(($filters['channel'] ?? '') === 'sms')>SMS</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="sent" @selected(($filters['status'] ?? '') === 'sent')>Sent</option>
                    <option value="failed" @selected(($filters['status'] ?? '') === 'failed')>Failed</option>
                    <option value="skipped" @selected(($filters['status'] ?? '') === 'skipped')>Skipped</option>
                </select>
            </div>
            <div class="col-12 col-md-6 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Apply</button>
                <a href="{{ route('notification-logs.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span>Reminder logs</span>
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
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap">{{ $log->sent_on?->format('Y-m-d') }}</td>
                            <td>
                                @if($log->student)
                                    <span class="fw-semibold">{{ $log->student->name }}</span>
                                    @if($log->student->admission_no)
                                        <span class="text-muted small">({{ $log->student->admission_no }})</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
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
                            <td class="small text-muted">{{ \Illuminate\Support\Str::limit($log->message ?? '', 120) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No notification logs match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $logs->links() }}</div>
</div>
@endsection
