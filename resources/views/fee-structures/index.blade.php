@extends('layouts.app')
@section('title','Fee Structures')

@section('actions')
  <x-icon-btn :href="route('fee-structures.create')" icon="bi-plus-lg" label="Add fee structure" variant="primary"
    :iconOnly="false" />
@endsection

@section('content')
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-8">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search fee structure name">
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="filter-bar-actions">
          <x-icon-btn type="submit" icon="bi-funnel-fill" label="Search" variant="primary" />
          <x-icon-btn :href="route('fee-structures.index')" icon="bi-arrow-counterclockwise" label="Reset"
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
                <x-table-actions
                  :edit="route('fee-structures.edit', $feeStructure)"
                  :delete="route('fee-structures.destroy', $feeStructure)"
                  deleteConfirm="Delete this fee structure?" />
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
