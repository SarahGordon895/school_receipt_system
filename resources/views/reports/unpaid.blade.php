@extends('layouts.app')
@section('title','Unpaid & Overdue Report')

@section('actions')
  <div class="page-actions d-flex flex-wrap gap-2">
    <form method="GET" action="{{ route('reports.unpaid.pdf') }}" class="d-inline">
      @if($classFilter ?? '')
        <input type="hidden" name="class_name" value="{{ $classFilter }}">
      @endif
      <x-icon-btn type="submit" icon="bi-file-earmark-pdf" label="Export PDF" variant="danger" :iconOnly="false" />
    </form>
    <x-icon-btn :href="route('notification-logs.send.create')" icon="bi-send" label="Manual send (1–5)" variant="outline-primary" :iconOnly="false" />
    <x-icon-btn :href="route('notification-logs.index')" icon="bi-bell" label="Notification logs" variant="outline-secondary" :iconOnly="false" />
    <x-icon-btn :href="route('reports.index')" icon="bi-arrow-left" label="Back to reports" variant="outline-primary" :iconOnly="false" />
  </div>
@endsection

@section('content')
@php
    $maxBatch = $maxBatchParents ?? 5;
    $minBatch = $minBatchParents ?? 1;
@endphp

<div class="alert alert-info small mb-3">
  <i class="bi bi-info-circle me-1"></i>
  Select <strong>{{ $minBatch }} to {{ $maxBatch }} students</strong> below, choose the event template, then send SMS/email to their parents.
  Students are grouped by class.
</div>

<div class="row mb-3 g-3">
  <div class="col-6 col-md-3">
    <div class="card border-primary h-100">
      <div class="card-body text-center">
        <div class="text-muted">Outstanding Students</div>
        <div class="fs-4 fw-bold">{{ $summary['students_with_balance'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-danger h-100">
      <div class="card-body text-center">
        <div class="text-muted">Overdue</div>
        <div class="fs-4 fw-bold text-danger">{{ $summary['overdue_count'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-warning h-100">
      <div class="card-body text-center">
        <div class="text-muted">Due in 14 Days</div>
        <div class="fs-4 fw-bold text-warning">{{ $summary['due_in_14_days'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-secondary h-100">
      <div class="card-body text-center">
        <div class="text-muted">Total Outstanding</div>
        <div class="fs-5 fw-bold">Tsh {{ format_tzs($summary['total_outstanding']) }}</div>
      </div>
    </div>
  </div>
</div>

<form method="GET" class="card mb-3">
  <div class="card-body row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Filter by class</label>
      <select name="class_name" class="form-select" onchange="this.form.submit()">
        <option value="">All classes</option>
        @foreach($classes as $class)
          <option value="{{ $class }}" @selected($classFilter === $class)>{{ $class }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-5">
      <label class="form-label" for="unpaid-search">Search in list</label>
      <input type="search" id="unpaid-search" class="form-control" placeholder="Student, admission no, parent, phone…">
    </div>
    <div class="col-md-3">
      <div class="form-text mb-0">Showing unpaid students only, grouped by class.</div>
    </div>
  </div>
</form>

<form method="POST" action="{{ route('reports.unpaid.send-reminders') }}" id="unpaid-send-form" class="card mb-3">
  @csrf
  <div class="card-header fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span><i class="bi bi-send me-2"></i>Send SMS / email to selected students’ parents</span>
    <span class="badge text-bg-primary" id="unpaid-selection-counter">0 / {{ $maxBatch }} selected</span>
  </div>
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label for="message_type" class="form-label">Event template</label>
        <select name="message_type" id="message_type" class="form-select">
          <option value="">Auto (match each student's stage)</option>
          @foreach(\App\Services\NotificationTemplateService::manualSendEventTypes() as $type)
            @continue($type === 'payment_received')
            <option value="{{ $type }}">{{ app(\App\Services\NotificationTemplateService::class)->eventLabel($type) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label d-block">Channels</label>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="send_sms" id="send_sms" value="1" checked>
          <label class="form-check-label" for="send_sms">SMS</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" name="send_email" id="send_email" value="1" checked>
          <label class="form-check-label" for="send_email">Email</label>
        </div>
      </div>
      <div class="col-md-4">
        <button type="submit" class="btn btn-school-primary w-100">
          <i class="bi bi-send me-1"></i> Send to selected (max {{ $maxBatch }})
        </button>
        <div class="form-text">Select {{ $minBatch }}–{{ $maxBatch }} students in the table below.</div>
      </div>
    </div>
  </div>
</form>

<div class="card">
  <div class="card-header fw-semibold d-flex justify-content-between align-items-center gap-2">
    <span><i class="bi bi-exclamation-triangle me-2"></i>Students with Outstanding Fees</span>
    <span class="text-muted small" id="unpaid-visible-count">{{ $students->count() }} listed</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light sticky-top">
          <tr>
            <th style="width:2.5rem">
              <span class="visually-hidden">Select</span>
            </th>
            <th>Student</th>
            <th>Parent Contact</th>
            <th class="text-end">Expected</th>
            <th class="text-end">Paid</th>
            <th class="text-end">Balance</th>
            <th>Due Date</th>
            <th>Reminder Stage</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($studentsByClass as $className => $rows)
            <tr class="class-group-header table-secondary" data-class-group="{{ $className }}">
              <td colspan="9" class="fw-semibold py-2">
                <i class="bi bi-collection me-1"></i>{{ $className }}
                <span class="text-muted fw-normal">({{ $rows->count() }})</span>
              </td>
            </tr>
            @foreach($rows as $row)
              <tr class="unpaid-student-row"
                  data-class="{{ $row['class_name'] }}"
                  data-search="{{ strtolower(trim(($row['student']->name ?? '').' '.($row['student']->admission_no ?? '').' '.($row['parent_name'] ?? '').' '.($row['parent_phone'] ?? '').' '.$row['class_name'])) }}">
                <td>
                  <input type="checkbox" class="form-check-input unpaid-student-checkbox"
                    name="student_ids[]" value="{{ $row['student']->id }}"
                    form="unpaid-send-form"
                    aria-label="Select {{ $row['student']->name }}"
                    @disabled(! $row['has_contact'])>
                </td>
                <td>
                  <div class="fw-semibold">{{ $row['student']->name }}</div>
                  <div class="small text-muted">{{ $row['student']->admission_no ?: 'No admission no' }}</div>
                </td>
                <td>
                  <div>{{ $row['parent_name'] }}</div>
                  <small class="text-muted">{{ $row['parent_phone'] ?: 'No phone' }}</small>
                  @unless($row['has_contact'])
                    <div><span class="badge text-bg-warning">No contact</span></div>
                  @endunless
                </td>
                <td class="text-end">Tsh {{ format_tzs($row['expected']) }}</td>
                <td class="text-end">Tsh {{ format_tzs($row['paid']) }}</td>
                <td class="text-end fw-semibold text-danger">Tsh {{ format_tzs($row['balance']) }}</td>
                <td>{{ $row['student']->resolveFeeDueDate()->format('d/m/Y') }}</td>
                <td>
                  @if($row['is_overdue'])
                    <span class="badge text-bg-danger">{{ $row['milestone'] }}</span>
                  @elseif(in_array($row['days_until_due'], [14, 7, 3, 0], true))
                    <span class="badge text-bg-warning">{{ $row['milestone'] }}</span>
                  @else
                    <span class="badge text-bg-secondary">{{ $row['milestone'] }}</span>
                  @endif
                </td>
                <td class="text-end">
                  <x-icon-btn :href="route('notification-logs.send.create', ['student_id' => $row['student']->id])"
                    icon="bi-send" label="Send" variant="outline-primary" size="sm" />
                </td>
              </tr>
            @endforeach
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No outstanding balances.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const maxBatch = {{ $maxBatch }};
    const minBatch = {{ $minBatch }};
    const checkboxes = Array.from(document.querySelectorAll('.unpaid-student-checkbox'));
    const rows = Array.from(document.querySelectorAll('.unpaid-student-row'));
    const groupHeaders = Array.from(document.querySelectorAll('.class-group-header'));
    const counter = document.getElementById('unpaid-selection-counter');
    const searchInput = document.getElementById('unpaid-search');
    const form = document.getElementById('unpaid-send-form');

    function selectedCount() {
        return checkboxes.filter(cb => cb.checked).length;
    }

    function updateCounter() {
        const count = selectedCount();
        counter.textContent = count + ' / ' + maxBatch + ' selected';
        counter.className = 'badge ' + (count >= maxBatch ? 'text-bg-warning' : 'text-bg-primary');
        checkboxes.forEach(cb => {
            if (cb.disabled && !cb.checked) return;
            cb.disabled = (!cb.checked && count >= maxBatch) || cb.hasAttribute('data-no-contact');
        });
    }

    function filterRows() {
        const query = (searchInput.value || '').trim().toLowerCase();
        let visible = 0;
        rows.forEach(function (row) {
            const show = !query || (row.dataset.search || '').includes(query);
            row.style.display = show ? '' : 'none';
            if (!show) {
                const cb = row.querySelector('.unpaid-student-checkbox');
                if (cb && cb.checked) cb.checked = false;
            }
            if (show) visible++;
        });
        groupHeaders.forEach(function (header) {
            const className = header.dataset.classGroup;
            const anyVisible = rows.some(row => row.dataset.class === className && row.style.display !== 'none');
            header.style.display = anyVisible ? '' : 'none';
        });
        document.getElementById('unpaid-visible-count').textContent = visible + ' listed';
        updateCounter();
    }

    checkboxes.forEach(cb => {
        if (cb.disabled) cb.setAttribute('data-no-contact', '1');
        cb.addEventListener('change', function () {
            if (this.checked && selectedCount() > maxBatch) {
                this.checked = false;
                alert('Maximum ' + maxBatch + ' students per send.');
            }
            updateCounter();
        });
    });

    searchInput.addEventListener('input', filterRows);
    filterRows();

    form.addEventListener('submit', function (event) {
        const count = selectedCount();
        if (count < minBatch) {
            event.preventDefault();
            alert('Select at least ' + minBatch + ' student(s) from the table.');
            return;
        }
        if (count > maxBatch) {
            event.preventDefault();
            alert('Maximum ' + maxBatch + ' students per send.');
            return;
        }
        if (!confirm('Send SMS/email for ' + count + ' selected student(s)?')) {
            event.preventDefault();
        }
    });
});
</script>
@endpush
@endsection
