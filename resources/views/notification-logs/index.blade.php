@extends('layouts.app')
@section('title', 'Notification Logs')

@section('actions')
    <div class="page-actions">
        <x-icon-btn :href="route('reports.messages')" icon="bi-file-earmark-text" label="Export report" variant="outline-primary" :iconOnly="false" />
        <x-icon-btn :href="route('messages.index')" icon="bi-send" label="Send SMS &amp; email" variant="primary" :iconOnly="false" />
        <x-icon-btn :href="route('notification-logs.create')" icon="bi-journal-plus" label="Record log" variant="outline-secondary" :iconOnly="false" />
    </div>
@endsection

@section('content')
@php
    $sentCount = (int) ($stats['sent'] ?? 0);
    $failedCount = (int) ($stats['failed'] ?? 0);
    $skippedCount = (int) ($stats['skipped'] ?? 0);
    $currentStatus = $filters['status'] ?? '';
    $filterQuery = array_filter($filters ?? [], fn ($value) => filled($value));
    $statHref = function (?string $status) use ($filterQuery, $currentStatus) {
        $query = $filterQuery;

        if ($status === null || $currentStatus === $status) {
            unset($query['status']);
        } else {
            $query['status'] = $status;
        }

        return route('notification-logs.index', $query);
    };
@endphp

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ $statHref('sent') }}" class="text-decoration-none d-block h-100">
            <div class="card h-100 stat-card-school stat-card-log stat-card-log-success @if($currentStatus === 'sent') is-active @endif">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2 text-muted small text-uppercase fw-semibold stat-card-log-title">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Delivered</span>
                        </div>
                        @if($currentStatus === 'sent')
                            <span class="badge text-bg-success">Filtered</span>
                        @endif
                    </div>
                    <div class="fs-2 fw-bold mt-2 stat-value text-success">{{ $sentCount }}</div>
                    <div class="small text-muted mt-1">SMS and email sent successfully</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ $statHref('failed') }}" class="text-decoration-none d-block h-100">
            <div class="card h-100 stat-card-school stat-card-log stat-card-log-danger @if($currentStatus === 'failed') is-active @endif">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2 text-muted small text-uppercase fw-semibold stat-card-log-title">
                            <i class="bi bi-x-circle-fill text-danger"></i>
                            <span>Failed</span>
                        </div>
                        @if($currentStatus === 'failed')
                            <span class="badge text-bg-danger">Filtered</span>
                        @endif
                    </div>
                    <div class="fs-2 fw-bold mt-2 stat-value text-danger">{{ $failedCount }}</div>
                    <div class="small text-muted mt-1">Needs resend or review</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ $statHref('skipped') }}" class="text-decoration-none d-block h-100">
            <div class="card h-100 stat-card-school stat-card-log stat-card-log-warning @if($currentStatus === 'skipped') is-active @endif">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2 text-muted small text-uppercase fw-semibold stat-card-log-title">
                            <i class="bi bi-skip-forward-fill text-warning"></i>
                            <span>Skipped</span>
                        </div>
                        @if($currentStatus === 'skipped')
                            <span class="badge text-bg-warning text-dark">Filtered</span>
                        @endif
                    </div>
                    <div class="fs-2 fw-bold mt-2 stat-value text-warning">{{ $skippedCount }}</div>
                    <div class="small text-muted mt-1">No contact or SMS disabled</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ $statHref(null) }}" class="text-decoration-none d-block h-100">
            <div class="card h-100 stat-card-school stat-card-log stat-card-log-neutral @if($currentStatus === '') is-active @endif">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2 text-muted small text-uppercase fw-semibold stat-card-log-title">
                            <i class="bi bi-journal-text text-school-primary"></i>
                            <span>All logs</span>
                        </div>
                        @if($currentStatus === '')
                            <span class="badge text-bg-light text-dark border">Showing all</span>
                        @endif
                    </div>
                    <div class="fs-2 fw-bold mt-2 stat-value text-school-primary">{{ $logs->total() }}</div>
                    <div class="small text-muted mt-1">Matching your current filters</div>
                </div>
            </div>
        </a>
    </div>
</div>

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
            <div class="col-12 col-md-6 d-flex align-items-end">
                <div class="filter-bar-actions">
                    <x-icon-btn type="submit" icon="bi-funnel-fill" label="Apply filters" variant="primary" />
                    <x-icon-btn :href="route('notification-logs.index')" icon="bi-arrow-counterclockwise" label="Reset filters"
                        variant="outline-secondary" />
                </div>
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
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="@if($log->status === 'failed') table-row-failed @elseif($log->status === 'sent') table-row-sent @endif">
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
                                <span class="badge text-bg-{{ $log->statusBadge() }}">{{ $log->statusLabel() }}</span>
                                @if($log->delivery_status)
                                    <div class="small text-muted mt-1">{{ $log->delivery_status }}</div>
                                @endif
                            </td>
                            <td class="small text-muted">{{ \Illuminate\Support\Str::limit($log->message ?? '', 120) }}</td>
                            <td class="text-end">
                                <x-table-actions
                                    :view="route('notification-logs.show', $log)"
                                    :edit="route('notification-logs.edit', $log)"
                                    :delete="route('notification-logs.destroy', $log)"
                                    deleteConfirm="Delete this reminder log?">
                                    @include('notification-logs.partials.resend-button', ['log' => $log])
                                </x-table-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No notification logs match your filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">{{ $logs->links() }}</div>
</div>
@endsection
