@extends('layouts.app')
@section('title','Student Payment History')

@section('actions')
  <a href="{{ route('parent.dashboard') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Back to Parent Portal
  </a>
  <button type="button" class="btn btn-outline-primary" onclick="window.print()">
    <i class="bi bi-printer me-1"></i> Print Statement
  </button>
@endsection

@section('content')
<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="small text-muted">Student</div>
        <div class="fw-semibold fs-5">{{ $student->name }}</div>
        <div class="small text-muted">{{ $student->class_name ?? 'Class not set' }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="small text-muted">Expected / Paid</div>
        <div class="fw-semibold">Tsh {{ number_format($student->expected_amount) }}</div>
        <div class="small text-muted">Paid: Tsh {{ number_format($student->paid_amount) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="small text-muted">Outstanding Balance</div>
        <div class="fw-semibold {{ $student->balance > 0 ? 'text-danger' : 'text-success' }}">
          Tsh {{ number_format($student->balance) }}
        </div>
        <div class="small text-muted">Due: {{ $student->fee_due_date?->format('Y-m-d') ?? 'Not set' }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold">Payment History</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Receipt #</th>
            <th>Date</th>
            <th>Mode</th>
            <th>Categories</th>
            <th class="text-end">Amount</th>
            <th>Reference</th>
          </tr>
        </thead>
        <tbody>
          @forelse($receipts as $receipt)
            <tr>
              <td class="fw-semibold">{{ $receipt->receipt_no }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($receipt->payment_date)->format('Y-m-d') }}</td>
              <td>{{ $receipt->payment_mode }}</td>
              <td>{{ $receipt->paymentCategories->pluck('name')->implode(', ') ?: '—' }}</td>
              <td class="text-end">Tsh {{ number_format($receipt->amount) }}</td>
              <td>{{ $receipt->reference ?: '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No payment history yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if(method_exists($receipts, 'links'))
    <div class="card-footer">{{ $receipts->links() }}</div>
  @endif
</div>
@endsection
