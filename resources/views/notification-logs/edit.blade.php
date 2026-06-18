@extends('layouts.app')
@section('title', 'Edit Reminder Log')

@section('actions')
    <x-icon-btn :href="route('notification-logs.show', $log)" icon="bi-arrow-left" label="Back to log" variant="outline-secondary" :iconOnly="false" />
@endsection

@section('content')
<div class="card">
    <div class="card-header fw-semibold">Update reminder log</div>
    <div class="card-body">
        <form method="POST" action="{{ route('notification-logs.update', $log) }}">
            @csrf
            @method('PUT')
            @include('notification-logs._form', ['log' => $log, 'students' => $students])
            <div class="d-flex gap-2 mt-3">
                <x-form-actions :cancelUrl="route('notification-logs.show', $log)" submitLabel="Update log" submitIcon="bi-check-lg" />
            </div>
        </form>
    </div>
</div>
@endsection
