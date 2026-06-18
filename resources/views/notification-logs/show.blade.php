@extends('layouts.app')
@section('title', 'Reminder Log')

@section('actions')
    <div class="page-actions">
        @include('notification-logs.partials.resend-button', ['log' => $log])
        <x-icon-btn :href="route('notification-logs.edit', $log)" icon="bi-pencil" label="Edit log" variant="outline-primary" :iconOnly="false" />
    </div>
@endsection

@section('content')

<div class="card mb-3">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span>Reminder details</span>
        <div class="table-actions">
            <x-icon-btn :href="route('notification-logs.index')" icon="bi-arrow-left" label="Back to logs" variant="outline-secondary" size="sm" />
            @include('notification-logs.partials.resend-button', ['log' => $log])
            <x-icon-btn :href="route('notification-logs.edit', $log)" icon="bi-pencil" label="Edit" variant="outline-primary" size="sm" />
            <form action="{{ route('notification-logs.destroy', $log) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <x-icon-btn type="submit" icon="bi-trash" label="Delete" variant="outline-danger" size="sm"
                    confirm="Delete this reminder log?" />
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="small text-muted">Student</div>
                <div class="fw-semibold">
                    @if($log->student)
                        {{ $log->student->name }}
                        @if($log->student->admission_no)
                            <span class="text-muted">({{ $log->student->admission_no }})</span>
                        @endif
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </div>
                @if($log->student?->class_name)
                    <div class="small text-muted">{{ $log->student->class_name }}</div>
                @endif
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Date sent</div>
                <div>{{ $log->sent_on?->format('Y-m-d') ?? '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Channel</div>
                <div><span class="badge text-bg-secondary text-uppercase">{{ $log->channel }}</span></div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Status</div>
                <div><span class="badge text-bg-{{ $log->statusBadge() }}">{{ $log->statusLabel() }}</span></div>
            </div>
            @if($log->channel === 'sms' && $log->gateway_uid)
                <div class="col-md-3">
                    <div class="small text-muted">Gateway reference</div>
                    <div class="small font-monospace">{{ $log->gateway_uid }}</div>
                </div>
            @endif
            @if($log->delivery_status)
                <div class="col-md-3">
                    <div class="small text-muted">Delivery report</div>
                    <div>{{ $log->delivery_status }}</div>
                </div>
            @endif
            <div class="col-md-3">
                <div class="small text-muted">Parent read</div>
                <div>
                    @if($log->read_at)
                        <span class="badge text-bg-success">Read {{ $log->read_at->format('Y-m-d H:i') }}</span>
                    @else
                        <span class="badge text-bg-light text-dark">Unread</span>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Recorded</div>
                <div>{{ $log->created_at?->format('Y-m-d H:i') ?? '—' }}</div>
            </div>
            @if($log->student)
                <div class="col-md-6">
                    <div class="small text-muted">Parent contact</div>
                    <div class="small">
                        {{ $log->student->parent_name ?: '—' }}
                        @if($log->student->parent_email)
                            <br>{{ $log->student->parent_email }}
                        @endif
                        @if($log->student->parent_phone)
                            <br>{{ $log->student->parent_phone }}
                        @endif
                    </div>
                </div>
            @endif
            <div class="col-12">
                <div class="small text-muted">Message</div>
                <div class="p-3 bg-light rounded">{{ $log->message ?: '—' }}</div>
            </div>
            @if($log->isResolvableFailure())
                <div class="col-12">
                    <div class="alert alert-warning mb-0">
                        <div class="fw-semibold mb-2">This reminder did not complete successfully</div>
                        <p class="mb-3 small">If the parent already received the {{ strtoupper($log->channel) }}, use <strong>Mark delivered</strong>. Otherwise, resend or refresh gateway status.</p>
                        <div class="d-flex flex-wrap gap-2">
                            @include('notification-logs.partials.resend-button', ['log' => $log])
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
