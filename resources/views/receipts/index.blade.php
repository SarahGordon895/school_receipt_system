@extends('layouts.app')
@section('title','Receipts')

@section('actions')
  <x-icon-btn :href="route('receipts.create')" icon="bi-receipt-cutoff" label="New receipt" variant="primary"
    :iconOnly="false" />
@endsection

@section('content')
<div class="card mb-3">
  <div class="card-body">
    <form id="filtersForm" method="GET" action="{{ route('receipts.index') }}" class="row g-2 align-items-end">
      <div class="col-12 col-md-3">
        <label for="q" class="form-label small text-muted mb-1">Search</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-search"></i></span>
          <input type="text" id="q" name="q" value="{{ $q ?? '' }}" class="form-control"
                 placeholder="Student name or receipt #">
        </div>
      </div>

      <div class="col-12 col-md-3">
        <label for="class_name" class="form-label small text-muted mb-1">Class</label>
        <input type="text" id="class_name" name="class_name" value="{{ $className ?? '' }}" class="form-control"
               placeholder="e.g. Form I">
      </div>

      <div class="col-12 col-md-3">
        <label for="payment_category_id" class="form-label small text-muted mb-1">Category</label>
        <select id="payment_category_id" name="payment_category_id" class="form-select">
          <option value="">All categories</option>
          @foreach($categories as $pc)
            <option value="{{ $pc->id }}" @selected((string)($categoryId ?? '') === (string)$pc->id)>{{ $pc->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-12 col-md-3">
        <div class="filter-bar-actions">
          <x-icon-btn type="submit" icon="bi-funnel-fill" label="Apply filters" variant="primary" />
          <x-icon-btn :href="route('receipts.index')" icon="bi-arrow-counterclockwise" label="Reset filters"
            variant="outline-secondary" />
        </div>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">All Receipts</span>
    <x-icon-btn :href="route('receipts.create')" icon="bi-plus-lg" label="Generate receipt" variant="outline-primary" size="sm" />
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Receipt #</th>
            <th>Student</th>
            <th>Class</th>
            <th>Amount</th>
            <th>Category</th>
            <th>Date</th>
            <th>Mode</th>
            <th>Ref</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($receipts as $r)
            <tr>
              <td class="fw-semibold">{{ $r->receipt_no }}</td>
              <td>{{ $r->student_name }}</td>
              <td>{{ $r->class_name ?? '—' }}</td>
              <td>Tsh {{ number_format($r->amount) }}</td>
              <td>{{ $r->paymentCategories->pluck('name')->implode(', ') ?: '—' }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($r->payment_date)->toDateString() }}</td>
              <td>{{ $r->payment_mode }}</td>
              <td>{{ $r->reference ?: '—' }}</td>
              <td class="text-end">
                <x-table-actions
                  :view="route('receipts.show', $r)"
                  :edit="route('receipts.edit', $r)"
                  :delete="route('receipts.destroy', $r)"
                  deleteConfirm="Delete this receipt permanently?" />
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No receipts found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if(method_exists($receipts, 'links'))
    <div class="card-footer">
      {{ $receipts->links() }}
    </div>
  @endif
</div>
@endsection
