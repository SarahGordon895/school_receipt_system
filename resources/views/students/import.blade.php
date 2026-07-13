@extends('layouts.app')
@section('title','Import Students')

@section('actions')
  <x-icon-btn :href="route('students.index')" icon="arrow-left" label="Back to students" variant="outline-secondary" :iconOnly="false" />
@endsection

@section('content')
<div class="card mb-3">
  <div class="card-header fw-semibold"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import student register (Excel / CSV)</div>
  <div class="card-body">
    <p class="text-muted mb-3">
      Upload the school student list. After import you will see a <strong>numbered table of every student</strong> from the file — not the document itself.
      School staff can import; link each student to a parent portal account later when editing the student.
    </p>

    <div class="table-responsive mb-4">
      <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th>Student Name</th>
            <th>Admission No</th>
            <th>Class</th>
            <th>Parent Name</th>
            <th>Parent Phone</th>
            <th>Parent Email</th>
          </tr>
        </thead>
        <tbody>
          <tr class="text-muted small">
            <td>Innocent Richard Mkumbo</td>
            <td>MBN-2026-101</td>
            <td>Form I</td>
            <td>John Mkumbo</td>
            <td>+255712000001</td>
            <td>parent@example.com</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p class="small text-muted mb-3">Use the header row above, or omit headers and keep the same column order.</p>

    <form method="POST" action="{{ route('students.import.store') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
      @csrf
      <div class="col-md-8">
        <label for="file" class="form-label">Student list file</label>
        <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv,.txt" class="form-control" required>
        @error('file')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <x-icon-btn type="submit" icon="bi-upload" label="Import &amp; show list" variant="primary" :iconOnly="false" />
      </div>
    </form>
  </div>
</div>
@endsection
