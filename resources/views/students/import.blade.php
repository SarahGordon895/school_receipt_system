@extends('layouts.app')
@section('title','Import Students')

@section('content')
<div class="card">
  <div class="card-header fw-semibold">Import from Excel / CSV</div>
  <div class="card-body">
    <p class="text-muted mb-2">Upload a file with a single column: the student names (no header required).</p>
    <form method="POST" action="{{ route('students.import.store') }}" enctype="multipart/form-data" class="d-flex gap-2">
      @csrf
      <input type="file" name="file" accept=".xlsx,.csv,.txt" class="form-control" required>
      <button class="btn btn-primary"><i class="bi bi-upload me-1"></i> Import</button>
    </form>
  </div>
</div>
@endsection
