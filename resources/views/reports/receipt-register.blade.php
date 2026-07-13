@extends('layouts.app')
@section('title', 'Receipt Register Report')

@section('actions')
  @if($generated ?? false)
    <form method="POST" action="{{ route('reports.receipts.pdf') }}" class="d-inline">
      @csrf
      @foreach($request->all() as $key => $value)
        @if(is_scalar($value) && $value !== '')
          <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
      @endforeach
      <x-icon-btn type="submit" icon="bi-file-earmark-pdf" label="Export PDF" variant="danger" :iconOnly="false" />
    </form>
  @endif
  <x-icon-btn :href="route('reports.index')" icon="bi-arrow-left" label="Back to reports" variant="outline-primary" :iconOnly="false" />
@endsection

@section('content')
<p class="text-muted small mb-3">
  Official register of <strong>receipts recorded in the system</strong> — receipt number, student, amount, payment mode, and categories for the selected period.
</p>

<div class="card mb-3">
  <div class="card-header fw-semibold"><i class="bi bi-funnel me-2"></i>Generate receipt register</div>
  <div class="card-body">
    <form method="POST" action="{{ route('reports.receipts') }}" id="receiptRegisterForm">
      @csrf
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Payment period</label>
          <select name="date_range" id="dateRange" class="form-select" required>
            <option value="">Select period</option>
            @foreach(['today','yesterday','this_week','last_week','this_month','last_month','this_year','last_year','custom'] as $range)
              <option value="{{ $range }}" @selected(old('date_range', $request->date_range ?? '') === $range)>{{ ucfirst(str_replace('_', ' ', $range)) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4" id="customDateRange" style="display:none;">
          <label class="form-label">Start / End</label>
          <div class="input-group">
            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $request->start_date ?? '') }}">
            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $request->end_date ?? '') }}">
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label">Class</label>
          <input type="text" name="class_name" class="form-control" value="{{ old('class_name', $request->class_name ?? '') }}" placeholder="e.g. Form I">
        </div>
        <div class="col-md-4">
          <label class="form-label">Payment mode</label>
          <select name="payment_mode" class="form-select">
            <option value="">All</option>
            @foreach(['Cash','Bank','Mobile Money','Other'] as $mode)
              <option value="{{ $mode }}" @selected(old('payment_mode', $request->payment_mode ?? '') === $mode)>{{ $mode }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Category</label>
          <select name="payment_category_id" class="form-select">
            <option value="">All</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}" @selected((string) old('payment_category_id', $request->payment_category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-school-primary w-100"><i class="bi bi-search me-1"></i> Generate register</button>
        </div>
      </div>
    </form>
  </div>
</div>

@if($generated ?? false)
<div class="row mb-3 g-3">
  <div class="col-6 col-md-3">
    <div class="card h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Receipts</div>
      <div class="fs-4 fw-bold">{{ $summary['receipt_count'] }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 border-success"><div class="card-body text-center py-3">
      <div class="small text-muted">Total collected</div>
      <div class="fs-6 fw-bold text-success">Tsh {{ format_tzs($summary['total_collected']) }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Students paid</div>
      <div class="fs-4 fw-bold">{{ $summary['students_count'] }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100"><div class="card-body text-center py-3 small">
      <div>Cash: Tsh {{ format_tzs($summary['cash_total']) }}</div>
      <div>Bank: Tsh {{ format_tzs($summary['bank_total']) }}</div>
      <div>Mobile: Tsh {{ format_tzs($summary['mobile_total']) }}</div>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold"><i class="bi bi-receipt me-2"></i>Receipt register</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>S/N</th>
            <th>Receipt No</th>
            <th>Date</th>
            <th>Student</th>
            <th>Class</th>
            <th class="text-end">Amount (Tsh)</th>
            <th>Mode</th>
            <th>Categories</th>
            <th>Recorded by</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $index => $receipt)
            <tr>
              <td>{{ $index + 1 }}</td>
              <td class="fw-semibold">
                <a href="{{ route('receipts.show', $receipt) }}">{{ $receipt->receipt_no }}</a>
              </td>
              <td>{{ $receipt->payment_date?->format('d/m/Y') }}</td>
              <td>{{ $receipt->student_name ?? $receipt->student?->name }}</td>
              <td>{{ $receipt->class_name ?? $receipt->student?->class_name ?? '—' }}</td>
              <td class="text-end fw-semibold text-success">{{ format_tzs($receipt->amount) }}</td>
              <td>{{ $receipt->payment_mode }}</td>
              <td class="small">{{ $receipt->paymentCategories->pluck('name')->join(', ') ?: '—' }}</td>
              <td class="small">{{ $receipt->user?->name ?? '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No receipts found for this period.</td></tr>
          @endforelse
        </tbody>
        @if($rows->isNotEmpty())
          <tfoot class="table-light">
            <tr>
              <th colspan="5" class="text-end">TOTAL COLLECTED:</th>
              <th class="text-end">Tsh {{ format_tzs($summary['total_collected']) }}</th>
              <th colspan="3"></th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateRange = document.getElementById('dateRange');
    const customDateRange = document.getElementById('customDateRange');
    if (!dateRange) return;
    dateRange.addEventListener('change', function () {
        customDateRange.style.display = this.value === 'custom' ? 'block' : 'none';
    });
    if (dateRange.value === 'custom') customDateRange.style.display = 'block';
});
</script>
@endpush
