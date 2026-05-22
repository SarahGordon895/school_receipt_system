@extends('layouts.app')
@section('title','Payment Categories')

@section('actions')
  <x-icon-btn :href="route('payment-categories.create')" icon="bi-plus-lg" label="Add category" variant="primary"
    :iconOnly="false" />
@endsection

@section('content')
<div class="card mb-3">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-8">
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Search category">
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="filter-bar-actions">
          <x-icon-btn type="submit" icon="bi-funnel-fill" label="Search" variant="primary" />
          <x-icon-btn :href="route('payment-categories.index')" icon="bi-arrow-counterclockwise" label="Reset"
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
          <tr><th>Name</th><th>Default Amount</th><th class="text-end">Actions</th></tr>
        </thead>
        <tbody>
          @forelse($cats as $c)
            <tr>
              <td class="fw-semibold">{{ $c->name }}</td>
              <td>{{ $c->default_amount ? 'Tsh '.number_format($c->default_amount) : '—' }}</td>
              <td class="text-end">
                <x-table-actions
                  :edit="route('payment-categories.edit', $c)"
                  :delete="route('payment-categories.destroy', $c)"
                  deleteConfirm="Delete this payment category?" />
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
