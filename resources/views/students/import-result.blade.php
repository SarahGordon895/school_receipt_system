@extends('layouts.app')
@section('title','Import Results')

@section('actions')
  <div class="page-actions">
    <x-icon-btn :href="route('students.import.form')" icon="bi-file-earmark-arrow-up" label="Import another file" variant="outline-primary" :iconOnly="false" />
    <x-icon-btn :href="route('students.index')" icon="bi-people" label="All students" variant="primary" :iconOnly="false" />
  </div>
@endsection

@section('content')
<div class="alert alert-success d-flex align-items-start gap-2 mb-3">
  <i class="bi bi-check-circle-fill fs-5 mt-1"></i>
  <div>
    <strong>Import complete.</strong>
  File <code>{{ $result->filename }}</code> processed —
  <strong>{{ $result->totalRows() }}</strong> student row(s):
  {{ $result->createdCount() }} registered,
  {{ $result->updatedCount() }} updated,
  {{ $result->skippedCount() }} unchanged.
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="card h-100"><div class="card-body py-3">
      <div class="small text-muted">Rows in file</div>
      <div class="fs-4 fw-semibold">{{ $result->totalRows() }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 border-success"><div class="card-body py-3">
      <div class="small text-muted">Newly registered</div>
      <div class="fs-4 fw-semibold text-success">{{ $result->createdCount() }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 border-info"><div class="card-body py-3">
      <div class="small text-muted">Updated</div>
      <div class="fs-4 fw-semibold text-info">{{ $result->updatedCount() }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100"><div class="card-body py-3">
      <div class="small text-muted">Unchanged</div>
      <div class="fs-4 fw-semibold">{{ $result->skippedCount() }}</div>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold">
    <i class="bi bi-list-ol me-2"></i>Students from uploaded file ({{ $result->totalRows() }})
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:3rem">#</th>
            <th>Student name</th>
            <th>Admission no</th>
            <th>Class</th>
            <th>Parent / guardian</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @foreach($result->rows as $row)
            <tr>
              <td class="text-muted">{{ $row->rowNumber }}</td>
              <td class="fw-semibold">{{ $row->name }}</td>
              <td>{{ $row->admissionNo ?: '—' }}</td>
              <td>{{ $row->className ?: '—' }}</td>
              <td>{{ $row->parentName ?: '—' }}</td>
              <td>{{ $row->parentPhone ?: '—' }}</td>
              <td>{{ $row->parentEmail ?: '—' }}</td>
              <td>
                <span class="badge text-bg-{{ $row->statusBadge() }}">{{ $row->statusLabel() }}</span>
                @if($row->message)
                  <div class="small text-muted mt-1">{{ $row->message }}</div>
                @endif
              </td>
              <td class="text-end">
                @if($row->studentId)
                  <a href="{{ route('students.edit', $row->studentId) }}" class="btn btn-sm btn-outline-primary">Open</a>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<p class="small text-muted mt-3 mb-0">
  Next step: open each new student to assign fee structures and link a parent portal account.
</p>
@endsection
