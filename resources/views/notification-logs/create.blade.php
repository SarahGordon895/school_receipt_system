@extends('layouts.app')
@section('title', 'Record Reminder Log')

@section('actions')
    <x-icon-btn :href="route('notification-logs.index')" icon="bi-arrow-left" label="Back to logs" variant="outline-secondary" :iconOnly="false" />
@endsection

@section('content')
<div class="card">
    <div class="card-header fw-semibold">Record reminder activity</div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Use this form to log reminders sent outside the system (phone call, in-person, or manual SMS/email).
            Automated payment and fee reminders are recorded automatically.
        </p>
        <form method="POST" action="{{ route('notification-logs.store') }}">
            @csrf
            @include('notification-logs._form', ['log' => $log, 'students' => $students])
            <div class="d-flex gap-2 mt-3">
                <x-form-actions :cancelUrl="route('notification-logs.index')" submitLabel="Save log" />
            </div>
        </form>
    </div>
</div>
@endsection
