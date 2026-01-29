@extends('layouts.app')
@section('title','Payment Categories')

@section('actions')
  <a href="{{ route('payment-categories.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Category</a>
@endsection

@section('content')
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2">
      <div class="col-12 col-md-6">
        <label class="form-label small text-muted mb-1">Search</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Category name">
        </div>
      </div>
      <div class="col-12 col-md-2 d-grid">
        <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i> Go</button>
      </div>
      <div class="col-12 col-md-2 d-grid">
        <a href="{{ route('payment-categories.index') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr>
          <th>Name</th><th>Default Amount (Tsh)</th><th class="text-end">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($cats as $c)
          <tr>
            <td class="fw-semibold">{{ $c->name }}</td>
            <td>{{ $c->default_amount ? number_format($c->default_amount) : '—' }}</td>
            <td class="text-end">
              <a href="{{ route('payment-categories.edit',$c) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
              <form action="{{ route('payment-categories.destroy',$c) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete category?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="3" class="text-center text-muted py-4">No categories yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
  <div class="card-footer">{{ $cats->links() }}</div>
</div>
@endsection
