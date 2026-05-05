@extends('layouts.app')
@section('title','Fee Structures')

@section('actions')
  <a href="{{ route('fee-structures.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Fee Structure</a>
@endsection

@section('content')
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2">
      <div class="col-12 col-md-6">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search fee structure name">
        </div>
      </div>
      <div class="col-6 col-md-2 d-grid">
        <button class="btn btn-primary">Go</button>
      </div>
      <div class="col-6 col-md-2 d-grid">
        <a href="{{ route('fee-structures.index') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Class</th>
            <th>Amount</th>
            <th>Due Date</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($feeStructures as $feeStructure)
            <tr>
              <td class="fw-semibold">{{ $feeStructure->name }}</td>
              <td>{{ $feeStructure->class_name ?? 'All' }}</td>
              <td>Tsh {{ number_format($feeStructure->amount) }}</td>
              <td>{{ $feeStructure->due_date?->format('Y-m-d') ?? 'Not set' }}</td>
              <td>
                <span class="badge {{ $feeStructure->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                  {{ $feeStructure->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="text-end">
                <a href="{{ route('fee-structures.edit', $feeStructure) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                <form action="{{ route('fee-structures.destroy', $feeStructure) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete fee structure?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No fee structures yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">{{ $feeStructures->links() }}</div>
</div>
@endsection
