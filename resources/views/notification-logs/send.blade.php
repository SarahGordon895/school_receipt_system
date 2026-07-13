@extends('layouts.app')
@section('title', 'Send SMS & Email')

@section('actions')
    <x-icon-btn :href="route('messages.index')" icon="bi-chat-dots" label="SMS centre" variant="outline-secondary" :iconOnly="false" />
    <x-icon-btn :href="route('notification-logs.index')" icon="bi-arrow-left" label="Message history" variant="outline-secondary" :iconOnly="false" />
@endsection

@section('content')
@php
    $maxBatch = $maxBatchParents ?? 5;
    $minBatch = $minBatchParents ?? 1;
    $oldSelected = collect(old('student_ids', $selectedStudentId ? [$selectedStudentId] : []))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="alert alert-info mb-3">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Manual send:</strong> pick the <strong>event template</strong>, select <strong>{{ $minBatch }} to {{ $maxBatch }} parents only</strong> (not all), choose <strong>SMS</strong> and/or <strong>email</strong>, then send.
  Daily automation at 06:00 still runs separately for all eligible parents.
</div>

<div class="card">
    <div class="card-header fw-semibold"><i class="bi bi-send me-2"></i>Send to parents using template</div>
    <div class="card-body">
        <form method="POST" action="{{ route('notification-logs.send.store') }}" id="send-reminder-form">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="message_type" class="form-label">Event / message type</label>
                    <select name="message_type" id="message_type" class="form-select @error('message_type') is-invalid @enderror" required>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type }}" @selected(old('message_type', $selectedMessageType ?? 'fee_reminder_14') === $type)>
                                {{ $eventLabels[$type] ?? $type }}
                            </option>
                        @endforeach
                    </select>
                    @error('message_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6 d-flex align-items-end flex-wrap gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="filter_unpaid_only" checked>
                        <label class="form-check-label" for="filter_unpaid_only">Show only students with balance</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select_eligible">
                        <label class="form-check-label" for="select_eligible">Match reminder stage (14/7/3/0/overdue)</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <label class="form-label mb-0">
                            Select parents
                            <span class="text-muted" id="student-count">({{ $students->count() }} with contact)</span>
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-primary" id="selection-counter">0 / {{ $maxBatch }} selected</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-selection">Clear</button>
                        </div>
                    </div>

                    <div class="border rounded p-2 bg-light" style="max-height: 22rem; overflow-y: auto;" id="student-picker">
                        @foreach($students as $student)
                            @php
                                $daysUntil = $student->fee_due_date
                                    ? now()->startOfDay()->diffInDays($student->fee_due_date->startOfDay(), false)
                                    : null;
                                $isOverdue = $student->fee_due_date && $student->fee_due_date->isPast() && $student->balance > 0;
                            @endphp
                            <div class="form-check student-row py-1 border-bottom"
                                data-balance="{{ $student->balance }}"
                                data-due="{{ $student->fee_due_date?->format('d/m/Y') }}"
                                data-days="{{ $daysUntil }}"
                                data-overdue="{{ $isOverdue ? '1' : '0' }}"
                                data-name="{{ $student->name }}">
                                <input class="form-check-input student-checkbox" type="checkbox"
                                    name="student_ids[]" value="{{ $student->id }}" id="student_{{ $student->id }}"
                                    @checked(in_array($student->id, $oldSelected, true))>
                                <label class="form-check-label w-100" for="student_{{ $student->id }}">
                                    <span class="fw-semibold">{{ $student->name }}</span>
                                    @if($student->admission_no)
                                        <span class="text-muted">({{ $student->admission_no }})</span>
                                    @endif
                                    — {{ $student->class_name ?? 'Class N/A' }}
                                    — Balance Tsh {{ format_tzs($student->balance) }}
                                    @if($student->balance <= 0)
                                        <span class="badge text-bg-success">Paid</span>
                                    @elseif($isOverdue)
                                        <span class="badge text-bg-danger">Overdue</span>
                                    @elseif($daysUntil !== null)
                                        <span class="badge text-bg-warning">{{ $daysUntil }}d</span>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('student_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <div class="form-text">Choose {{ $minBatch }}–{{ $maxBatch }} parents only. You cannot select all at once.</div>
                </div>

                <div class="col-12">
                    <label class="form-label d-block">Channels</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="send_sms" id="send_sms" value="1" @checked(old('send_sms', true))>
                        <label class="form-check-label" for="send_sms"><i class="bi bi-phone me-1"></i>Send SMS</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="send_email" id="send_email" value="1" @checked(old('send_email', true))>
                        <label class="form-check-label" for="send_email"><i class="bi bi-envelope me-1"></i>Send email</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Template preview</label>
                    <div id="template-preview" class="border rounded p-3 bg-light small"></div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <x-form-actions :cancelUrl="route('messages.index')" submitLabel="Send SMS &amp; email now" submitIcon="bi-send" />
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const messageType = document.getElementById('message_type');
    const templatePreview = document.getElementById('template-preview');
    const filterUnpaid = document.getElementById('filter_unpaid_only');
    const selectEligible = document.getElementById('select_eligible');
    const form = document.getElementById('send-reminder-form');
    const templates = @json($templates);
    const maxBatch = {{ $maxBatch }};
    const minBatch = {{ $minBatch }};
    const rows = Array.from(document.querySelectorAll('.student-row'));
    const checkboxes = Array.from(document.querySelectorAll('.student-checkbox'));
    const counter = document.getElementById('selection-counter');

    const milestoneDays = {
        fee_reminder_14: 14,
        fee_reminder_7: 7,
        fee_reminder_3: 3,
        fee_reminder_due: 0,
        overdue: -1
    };

    function selectedCount() {
        return checkboxes.filter(cb => cb.checked).length;
    }

    function updateCounter() {
        const count = selectedCount();
        counter.textContent = count + ' / ' + maxBatch + ' selected';
        counter.className = 'badge ' + (count >= maxBatch ? 'text-bg-warning' : 'text-bg-primary');
        checkboxes.forEach(cb => {
            if (!cb.checked) {
                cb.disabled = count >= maxBatch;
            } else {
                cb.disabled = false;
            }
        });
    }

    function eligibleForType(row, type) {
        if (type === 'payment_received') return true;
        if (Number(row.dataset.balance || 0) <= 0) return false;
        if (type === 'overdue') return row.dataset.overdue === '1';
        if (type === 'fee_reminder') return Number(row.dataset.balance || 0) > 0;
        const days = milestoneDays[type];
        if (days === undefined) return true;
        return Number(row.dataset.days) === days;
    }

    function filterStudents() {
        const type = messageType.value;
        let visible = 0;
        rows.forEach(function (row) {
            let show = true;
            if (filterUnpaid.checked && Number(row.dataset.balance || 0) <= 0) show = false;
            if (show && selectEligible.checked) show = eligibleForType(row, type);
            row.style.display = show ? '' : 'none';
            if (!show) {
                const cb = row.querySelector('.student-checkbox');
                if (cb && cb.checked) cb.checked = false;
            }
            if (show) visible++;
        });
        document.getElementById('student-count').textContent = '(' + visible + ' visible)';
        updateCounter();
        updatePreview();
    }

    function updatePreview() {
        const type = messageType.value;
        const checked = checkboxes.find(cb => cb.checked);
        const row = checked ? checked.closest('.student-row') : null;
        let template = templates[type] || '';
        if (row) {
            template = template
                .replaceAll('{student_name}', row.dataset.name || '')
                .replaceAll('{balance}', Number(row.dataset.balance || 0).toLocaleString())
                .replaceAll('{due_date}', row.dataset.due || 'N/A')
                .replaceAll('{days_until_due}', row.dataset.days || 'N/A')
                .replaceAll('{amount}', '0')
                .replaceAll('{receipt_no}', 'MANUAL');
        }
        templatePreview.textContent = template;
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked && selectedCount() > maxBatch) {
                this.checked = false;
                alert('Maximum ' + maxBatch + ' parents per send.');
            }
            updateCounter();
            updatePreview();
        });
    });

    document.getElementById('clear-selection').addEventListener('click', function () {
        checkboxes.forEach(cb => { cb.checked = false; cb.disabled = false; });
        updateCounter();
        updatePreview();
    });

    messageType.addEventListener('change', filterStudents);
    filterUnpaid.addEventListener('change', filterStudents);
    selectEligible.addEventListener('change', filterStudents);
    filterStudents();

    form.addEventListener('submit', function (event) {
        const count = selectedCount();
        if (count < minBatch) {
            event.preventDefault();
            alert('Select at least ' + minBatch + ' parent(s).');
            return;
        }
        if (count > maxBatch) {
            event.preventDefault();
            alert('Maximum ' + maxBatch + ' parents per send.');
            return;
        }
        if (!confirm('Send to ' + count + ' parent(s) now?')) {
            event.preventDefault();
        }
    });
});
</script>
@endpush
@endsection
