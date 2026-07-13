@extends('layouts.app')
@section('title','Students')

@section('actions')
  <div class="page-actions">
    <x-icon-btn :href="route('students.import.form')" icon="bi-file-earmark-arrow-up" label="Import students"
      variant="outline-primary" :iconOnly="false" />
    <x-icon-btn :href="route('students.create')" icon="bi-person-plus" label="Add student" variant="primary"
      :iconOnly="false" />
  </div>
@endsection

@section('content')
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-8">
        <label class="form-label small text-muted mb-1">Search</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Student name or admission no">
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="filter-bar-actions">
          <x-icon-btn type="submit" icon="bi-funnel-fill" label="Apply filters" variant="primary" />
          <x-icon-btn :href="route('students.index')" icon="bi-arrow-counterclockwise" label="Reset filters"
            variant="outline-secondary" />
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>Student</th><th>Class</th><th>Parent / Guardian</th><th class="text-end">Balance</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          @forelse($students as $s)
            <tr>
              <td>
                <div class="fw-semibold">{{ $s->name }}</div>
                <div class="small text-muted">{{ $s->admission_no ?? 'No admission no' }}</div>
              </td>
              <td>{{ $s->class_name ?? '—' }}</td>
              <td>
                <div>{{ $s->parent_name ?? 'N/A' }}</div>
                <div class="small text-muted">
                  <span class="text-success"><i class="bi bi-phone me-1"></i>{{ $s->parent_phone ?? 'No phone' }}</span>
                </div>
                @if($s->parentUser)
                  <div class="small"><i class="bi bi-box-arrow-in-right me-1"></i>Portal: {{ $s->parentUser->email }}</div>
                @endif
                @if($s->parent_email && $s->parent_email !== $s->parentUser?->email)
                  <div class="small"><i class="bi bi-envelope me-1"></i>Alerts: {{ $s->parent_email }}</div>
                @elseif($s->parent_email)
                  <div class="small"><i class="bi bi-envelope me-1"></i>Alerts: {{ $s->parent_email }}</div>
                @endif
              </td>
              <td class="text-end">
                <span class="badge text-bg-{{ $s->paymentStatusBadge() }} mb-1">{{ $s->paymentStatusLabel() }}</span>
                <div class="{{ $s->balance > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' }}">
                  Tsh {{ number_format($s->balance) }}
                </div>
              </td>
              <td class="text-end">
                <x-table-actions
                  :edit="route('students.edit', $s)"
                  :delete="route('students.destroy', $s)"
                  deleteConfirm="Delete this student and related records?">
                  @if($s->hasOutstandingBalance())
                    <x-icon-btn :href="route('notification-logs.send.create', ['student_id' => $s->id])"
                      icon="bi-send" label="Send reminder" variant="outline-success" size="sm" />
                  @elseif($s->isFullyPaid())
                    <x-icon-btn :href="route('students.clearance-certificate', $s)" icon="bi-file-earmark-pdf"
                      label="Clearance PDF" variant="outline-success" size="sm" />
                  @endif
                </x-table-actions>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No students yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">{{ $students->links() }}</div>
</div>
@endsection
