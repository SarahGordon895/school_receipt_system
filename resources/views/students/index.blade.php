@extends('layouts.app')
@section('title','Students')

@section('actions')
  <a href="{{ route('students.import.form') }}" class="btn btn-outline-primary"><i class="bi bi-file-earmark-arrow-up me-1"></i> Import</a>
  <a href="{{ route('students.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Student</a>
@endsection

@section('content')
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2">
      <div class="col-12 col-md-6">
        <label class="form-label small text-muted mb-1">Search</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Student name">
        </div>
      </div>
      <div class="col-12 col-md-2 d-grid">
        <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Go</button>
      </div>
      <div class="col-12 col-md-2 d-grid">
        <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr><th>Name</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          @forelse($students as $s)
            <tr>
              <td class="fw-semibold">{{ $s->name }}</td>
              <td class="text-end">
                <a href="{{ route('students.edit',$s) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                <form action="{{ route('students.destroy',$s) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete student?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="2" class="text-center text-muted py-4">No students yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">{{ $students->links() }}</div>
</div>
@endsection
