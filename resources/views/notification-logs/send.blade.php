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
  <strong>Manual send:</strong> choose the <strong>event template</strong>, select
  <strong>{{ $minBatch }} to {{ $maxBatch }} students</strong>, then send SMS and/or email to their parents.
  Students are grouped by class for easier selection.
</div>

<div class="card">
    <div class="card-header fw-semibold"><i class="bi bi-send me-2"></i>Send to parents by student</div>
    <div class="card-body">
        <form method="POST" action="{{ route('notification-logs.send.store') }}" id="send-reminder-form">
            @csrf

            <div class="row g-3">
                <div class="col-md-7">
                    <label for="message_type" class="form-label">Event / message template</label>
                    <select name="message_type" id="message_type" class="form-select @error('message_type') is-invalid @enderror" required>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type }}"
                                @selected(old('message_type', $selectedMessageType ?? 'auto') === $type)>
                                {{ $eventLabels[$type] ?? $type }}
                            </option>
                        @endforeach
                    </select>
                    @error('message_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text" id="template-hint">
                        Templates follow fee status. Auto uses each selected student’s current stage.
                    </div>
                </div>

                <div class="col-md-5 d-flex align-items-end flex-wrap gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="filter_unpaid_only" checked>
                        <label class="form-check-label" for="filter_unpaid_only">Show unpaid only</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="filter_with_contact" checked>
                        <label class="form-check-label" for="filter_with_contact">With parent contact only</label>
                    </div>
                </div>

                <div class="col-md-5">
                    <label for="student-search" class="form-label">Search students</label>
                    <input type="search" id="student-search" class="form-control" placeholder="Name, admission no, parent, phone…">
                </div>
                <div class="col-md-3">
                    <label for="class-filter" class="form-label">Class</label>
                    <select id="class-filter" class="form-select">
                        <option value="">All classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class }}">{{ $class }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end justify-content-between gap-2">
                    <span class="badge text-bg-primary" id="selection-counter">0 / {{ $maxBatch }} selected</span>
                    <div class="d-flex gap-2">
                        <span class="text-muted small align-self-center" id="student-count">{{ $students->count() }} listed</span>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-selection">Clear</button>
                    </div>
                </div>

                <div class="col-12">
                    <div class="border rounded overflow-hidden" id="student-picker">
                        <div class="table-responsive" style="max-height: 28rem;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width:2.5rem"></th>
                                        <th>Student</th>
                                        <th>Parent contact</th>
                                        <th class="text-end">Balance</th>
                                        <th>Due</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($studentsByClass as $className => $rows)
                                        <tr class="class-group-header table-secondary" data-class-group="{{ $className }}">
                                            <td colspan="6" class="fw-semibold py-2">
                                                <i class="bi bi-collection me-1"></i>{{ $className }}
                                                <span class="text-muted fw-normal">({{ $rows->count() }})</span>
                                            </td>
                                        </tr>
                                        @foreach($rows as $row)
                                            @php $student = $row['student']; @endphp
                                            <tr class="student-row"
                                                data-balance="{{ $row['balance'] }}"
                                                data-due="{{ $row['due_date'] }}"
                                                data-days="{{ $row['days_until'] }}"
                                                data-event-type="{{ $row['suggested_type'] }}"
                                                data-student-name="{{ $student->name }}"
                                                data-parent-name="{{ $row['parent_name'] }}"
                                                data-class="{{ $row['class_name'] }}"
                                                data-has-contact="{{ $row['has_contact'] ? '1' : '0' }}"
                                                data-search="{{ strtolower(trim(($student->name ?? '').' '.($student->admission_no ?? '').' '.$row['parent_name'].' '.($row['parent_phone'] ?? '').' '.($row['parent_email'] ?? '').' '.$row['class_name'])) }}">
                                                <td>
                                                    <input class="form-check-input student-checkbox" type="checkbox"
                                                        name="student_ids[]" value="{{ $student->id }}"
                                                        id="student_{{ $student->id }}"
                                                        @checked(in_array((int) $student->id, $oldSelected, true))
                                                        @disabled(! $row['has_contact'])>
                                                </td>
                                                <td>
                                                    <label class="form-check-label d-block" for="student_{{ $student->id }}">
                                                        <span class="fw-semibold">{{ $student->name }}</span>
                                                        <div class="small text-muted">
                                                            {{ $student->admission_no ?: 'No admission no' }}
                                                        </div>
                                                    </label>
                                                </td>
                                                <td>
                                                    <div>{{ $row['parent_name'] }}</div>
                                                    <div class="small text-muted">
                                                        {{ $row['parent_phone'] ?: 'No phone' }}
                                                        @if($row['parent_email']) · {{ $row['parent_email'] }} @endif
                                                    </div>
                                                    @unless($row['has_contact'])
                                                        <span class="badge text-bg-warning">No contact</span>
                                                    @endunless
                                                </td>
                                                <td class="text-end {{ $row['balance'] > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                                                    Tsh {{ format_tzs($row['balance']) }}
                                                </td>
                                                <td>{{ $row['due_date'] }}</td>
                                                <td>
                                                    <span class="badge text-bg-{{ $row['suggested_type'] === 'overdue' ? 'danger' : ($row['balance'] <= 0 ? 'success' : 'warning') }}">
                                                        {{ $row['suggested_label'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No students found. Admit or import students first.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @error('student_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <div class="form-text">Choose {{ $minBatch }}–{{ $maxBatch }} students. Messages go to each student’s parent contacts.</div>
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

                <div class="col-md-6">
                    <label class="form-label">Template preview</label>
                    <div id="template-preview" class="border rounded p-3 bg-light small" style="min-height: 6rem;"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">All event templates</label>
                    <div class="border rounded p-2 bg-white small" style="max-height: 12rem; overflow-y: auto;">
                        @foreach($templateCatalog as $type => $item)
                            <div class="mb-2 pb-2 border-bottom">
                                <div class="fw-semibold">{{ $item['label'] }}</div>
                                <div class="text-muted">{{ $item['body'] }}</div>
                            </div>
                        @endforeach
                    </div>
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
    const filterContact = document.getElementById('filter_with_contact');
    const searchInput = document.getElementById('student-search');
    const classFilter = document.getElementById('class-filter');
    const form = document.getElementById('send-reminder-form');
    const templates = @json($templates);
    const maxBatch = {{ $maxBatch }};
    const minBatch = {{ $minBatch }};
    const rows = Array.from(document.querySelectorAll('.student-row'));
    const groupHeaders = Array.from(document.querySelectorAll('.class-group-header'));
    const checkboxes = Array.from(document.querySelectorAll('.student-checkbox'));
    const counter = document.getElementById('selection-counter');

    function selectedCount() {
        return checkboxes.filter(cb => cb.checked).length;
    }

    function selectedRows() {
        return checkboxes.filter(cb => cb.checked).map(cb => cb.closest('.student-row'));
    }

    function updateCounter() {
        const count = selectedCount();
        counter.textContent = count + ' / ' + maxBatch + ' selected';
        counter.className = 'badge ' + (count >= maxBatch ? 'text-bg-warning' : 'text-bg-primary');
        checkboxes.forEach(cb => {
            const row = cb.closest('.student-row');
            const hasContact = row?.dataset.hasContact === '1';
            cb.disabled = !hasContact || (!cb.checked && count >= maxBatch);
        });
    }

    function filterStudents() {
        const type = messageType.value;
        const query = (searchInput.value || '').trim().toLowerCase();
        const classValue = classFilter.value;
        let visible = 0;

        rows.forEach(function (row) {
            let show = true;
            if (filterUnpaid.checked && Number(row.dataset.balance || 0) <= 0) show = false;
            if (show && filterContact.checked && row.dataset.hasContact !== '1') show = false;
            if (show && classValue && row.dataset.class !== classValue) show = false;
            if (show && query && !(row.dataset.search || '').includes(query)) show = false;
            if (show && type !== 'auto' && type !== 'payment_received' && row.dataset.eventType !== type) {
                // keep visible; template mismatch is allowed so bursar can still pick students
            }
            row.style.display = show ? '' : 'none';
            if (!show) {
                const cb = row.querySelector('.student-checkbox');
                if (cb && cb.checked) cb.checked = false;
            }
            if (show) visible++;
        });

        groupHeaders.forEach(function (header) {
            const className = header.dataset.classGroup;
            const anyVisible = rows.some(row => row.dataset.class === className && row.style.display !== 'none');
            header.style.display = anyVisible ? '' : 'none';
        });

        document.getElementById('student-count').textContent = visible + ' listed';
        updateCounter();
        updatePreview();
    }

    function updatePreview() {
        const type = messageType.value === 'auto'
            ? (selectedRows()[0]?.dataset.eventType || 'fee_reminder')
            : messageType.value;
        const row = selectedRows()[0] || null;
        let template = templates[type] || '';
        if (row) {
            template = template
                .replaceAll('{student_name}', row.dataset.studentName || '')
                .replaceAll('{parent_name}', row.dataset.parentName || '')
                .replaceAll('{balance}', Number(row.dataset.balance || 0).toLocaleString())
                .replaceAll('{due_date}', row.dataset.due || 'N/A')
                .replaceAll('{days_until_due}', row.dataset.days || 'N/A')
                .replaceAll('{amount}', '0')
                .replaceAll('{receipt_no}', 'MANUAL');
        }
        templatePreview.textContent = template || 'Select a student to preview the message.';
        document.getElementById('template-hint').textContent = messageType.value === 'auto'
            ? 'Auto sends each selected student the template that matches their fee status.'
            : 'Using template: ' + (messageType.options[messageType.selectedIndex]?.text || type);
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked && selectedCount() > maxBatch) {
                this.checked = false;
                alert('Maximum ' + maxBatch + ' students per send.');
            }
            updateCounter();
            updatePreview();
        });
    });

    document.getElementById('clear-selection').addEventListener('click', function () {
        checkboxes.forEach(cb => { cb.checked = false; });
        updateCounter();
        updatePreview();
    });

    messageType.addEventListener('change', filterStudents);
    filterUnpaid.addEventListener('change', filterStudents);
    filterContact.addEventListener('change', filterStudents);
    searchInput.addEventListener('input', filterStudents);
    classFilter.addEventListener('change', filterStudents);
    filterStudents();

    form.addEventListener('submit', function (event) {
        const count = selectedCount();
        if (count < minBatch) {
            event.preventDefault();
            alert('Select at least ' + minBatch + ' student(s).');
            return;
        }
        if (count > maxBatch) {
            event.preventDefault();
            alert('Maximum ' + maxBatch + ' students per send.');
            return;
        }
        if (!confirm('Send to parents of ' + count + ' student(s) now?')) {
            event.preventDefault();
        }
    });
});
</script>
@endpush
@endsection
