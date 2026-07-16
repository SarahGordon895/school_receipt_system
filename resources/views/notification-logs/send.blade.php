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
    $oldSelected = collect(old('parent_user_ids', $selectedParentId ? [$selectedParentId] : []))->map(fn ($id) => (int) $id)->all();
@endphp

<div class="alert alert-info mb-3">
  <i class="bi bi-info-circle me-2"></i>
  <strong>Manual send:</strong> choose the <strong>event template</strong> (recommended by fee status), select
  <strong>{{ $minBatch }} to {{ $maxBatch }} parents</strong>, then send by <strong>SMS</strong> and/or <strong>email</strong>.
  Newly registered parents appear here automatically after student admission.
</div>

<div class="card">
    <div class="card-header fw-semibold"><i class="bi bi-send me-2"></i>Send to parents using template</div>
    <div class="card-body">
        <form method="POST" action="{{ route('notification-logs.send.store') }}" id="send-reminder-form">
            @csrf

            <div class="row g-3">
                <div class="col-md-7">
                    <label for="message_type" class="form-label">Event / message template</label>
                    <select name="message_type" id="message_type" class="form-select @error('message_type') is-invalid @enderror" required>
                        @foreach($eventTypes as $type)
                            <option value="{{ $type }}"
                                @selected(old('message_type', $selectedMessageType ?? 'auto') === $type)
                                data-recommended="{{ $loop->iteration === 2 ? '1' : '0' }}">
                                {{ $eventLabels[$type] ?? $type }}
                                @if($type !== 'auto' && $loop->iteration === 2) — recommended @endif
                            </option>
                        @endforeach
                    </select>
                    @error('message_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text" id="template-hint">
                        Templates are listed by event. The first status-based option matches the selected parent’s fee balance.
                    </div>
                </div>

                <div class="col-md-5 d-flex align-items-end flex-wrap gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="filter_unpaid_only" checked>
                        <label class="form-check-label" for="filter_unpaid_only">Show unpaid only</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="filter_match_template" checked>
                        <label class="form-check-label" for="filter_match_template">Match selected template status</label>
                    </div>
                </div>

                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <label class="form-label mb-0">
                            Select parents
                            <span class="text-muted" id="parent-count">({{ $parents->count() }} registered)</span>
                        </label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge text-bg-primary" id="selection-counter">0 / {{ $maxBatch }} selected</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-selection">Clear</button>
                        </div>
                    </div>

                    <div class="border rounded p-2 bg-light" style="max-height: 24rem; overflow-y: auto;" id="parent-picker">
                        @forelse($parents as $row)
                            @php
                                $parent = $row['parent'];
                                $focus = $row['focus_student'];
                            @endphp
                            <div class="form-check parent-row py-2 border-bottom"
                                data-balance="{{ $row['balance'] }}"
                                data-due="{{ $row['due_date'] }}"
                                data-days="{{ $row['days_until'] }}"
                                data-event-type="{{ $row['suggested_type'] }}"
                                data-parent-name="{{ $parent->name }}"
                                data-student-name="{{ $focus?->name }}">
                                <input class="form-check-input parent-checkbox" type="checkbox"
                                    name="parent_user_ids[]" value="{{ $parent->id }}" id="parent_{{ $parent->id }}"
                                    @checked(in_array((int) $parent->id, $oldSelected, true))>
                                <label class="form-check-label w-100" for="parent_{{ $parent->id }}">
                                    <div class="d-flex flex-wrap justify-content-between gap-2">
                                        <div>
                                            <span class="fw-semibold">{{ $parent->name }}</span>
                                            <span class="text-muted small">
                                                — {{ $parent->phone ?: 'No phone' }}
                                                @if($parent->email)
                                                    · {{ $parent->email }}
                                                @endif
                                            </span>
                                        </div>
                                        <div>
                                            <span class="badge text-bg-{{ $row['suggested_type'] === 'overdue' ? 'danger' : ($row['balance'] <= 0 ? 'success' : 'warning') }}">
                                                {{ $row['suggested_label'] }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($focus)
                                        <div class="small text-muted mt-1">
                                            Student: <strong>{{ $focus->name }}</strong>
                                            @if($focus->admission_no) ({{ $focus->admission_no }}) @endif
                                            — {{ $focus->class_name ?? 'Class N/A' }}
                                            — Balance Tsh {{ format_tzs($row['balance']) }}
                                            — Due {{ $row['due_date'] }}
                                        </div>
                                    @endif
                                    @if($row['students']->count() > 1)
                                        <div class="small text-muted">Also linked: {{ $row['students']->pluck('name')->join(', ') }}</div>
                                    @endif
                                </label>
                            </div>
                        @empty
                            <div class="p-3 text-muted">No registered parents with contact details yet. Admit a student with a parent account to populate this list.</div>
                        @endforelse
                    </div>
                    @error('parent_user_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    <div class="form-text">Choose {{ $minBatch }}–{{ $maxBatch }} parents only. Overdue and near-due parents are listed first.</div>
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
    const filterMatch = document.getElementById('filter_match_template');
    const form = document.getElementById('send-reminder-form');
    const templates = @json($templates);
    const maxBatch = {{ $maxBatch }};
    const minBatch = {{ $minBatch }};
    const rows = Array.from(document.querySelectorAll('.parent-row'));
    const checkboxes = Array.from(document.querySelectorAll('.parent-checkbox'));
    const counter = document.getElementById('selection-counter');
    let userPickedTemplate = {{ old('message_type', request()->query('message_type')) ? 'true' : 'false' }};

    function selectedCount() {
        return checkboxes.filter(cb => cb.checked).length;
    }

    function selectedRows() {
        return checkboxes.filter(cb => cb.checked).map(cb => cb.closest('.parent-row'));
    }

    function updateCounter() {
        const count = selectedCount();
        counter.textContent = count + ' / ' + maxBatch + ' selected';
        counter.className = 'badge ' + (count >= maxBatch ? 'text-bg-warning' : 'text-bg-primary');
        checkboxes.forEach(cb => {
            cb.disabled = !cb.checked && count >= maxBatch;
        });
    }

    function reorderTemplateOptions(suggestedType) {
        if (!suggestedType || userPickedTemplate) return;
        const options = Array.from(messageType.options);
        const auto = options.find(o => o.value === 'auto');
        const suggested = options.find(o => o.value === suggestedType);
        const rest = options.filter(o => o.value !== 'auto' && o.value !== suggestedType);

        messageType.innerHTML = '';
        if (auto) messageType.appendChild(auto);
        if (suggested) {
            suggested.textContent = suggested.textContent.replace(/\s— recommended$/, '') + ' — recommended';
            messageType.appendChild(suggested);
        }
        rest.forEach(o => {
            o.textContent = o.textContent.replace(/\s— recommended$/, '');
            messageType.appendChild(o);
        });
        messageType.value = suggestedType || 'auto';
    }

    function syncTemplateFromSelection() {
        const selected = selectedRows();
        if (selected.length === 0) {
            updatePreview();
            return;
        }
        const suggested = selected[0].dataset.eventType;
        reorderTemplateOptions(suggested);
        updatePreview();
        filterParents();
    }

    function eligibleForType(row, type) {
        if (type === 'auto' || type === 'payment_received') return true;
        return row.dataset.eventType === type;
    }

    function filterParents() {
        const type = messageType.value;
        let visible = 0;
        rows.forEach(function (row) {
            let show = true;
            if (filterUnpaid.checked && Number(row.dataset.balance || 0) <= 0) show = false;
            if (show && filterMatch.checked && type !== 'auto') show = eligibleForType(row, type);
            row.style.display = show ? '' : 'none';
            if (!show) {
                const cb = row.querySelector('.parent-checkbox');
                if (cb && cb.checked) cb.checked = false;
            }
            if (show) visible++;
        });
        document.getElementById('parent-count').textContent = '(' + visible + ' visible)';
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
        templatePreview.textContent = template || 'Select an event template to preview.';
        document.getElementById('template-hint').textContent = messageType.value === 'auto'
            ? 'Auto sends each parent the template that matches their student fee status.'
            : 'Using template: ' + (messageType.options[messageType.selectedIndex]?.text || type);
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function () {
            if (this.checked && selectedCount() > maxBatch) {
                this.checked = false;
                alert('Maximum ' + maxBatch + ' parents per send.');
            }
            updateCounter();
            syncTemplateFromSelection();
        });
    });

    document.getElementById('clear-selection').addEventListener('click', function () {
        checkboxes.forEach(cb => { cb.checked = false; cb.disabled = false; });
        updateCounter();
        updatePreview();
    });

    messageType.addEventListener('change', function () {
        userPickedTemplate = true;
        filterParents();
    });
    filterUnpaid.addEventListener('change', filterParents);
    filterMatch.addEventListener('change', filterParents);
    filterParents();
    if (selectedCount() > 0) syncTemplateFromSelection();

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
