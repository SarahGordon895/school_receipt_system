@extends('layouts.app')
@section('title', $student->name . ' — Fee Profile')

@section('actions')
  <div class="page-actions">
      <x-icon-btn :href="route('parent.dashboard')" icon="arrow-left" label="Back to my children" variant="outline-secondary" :iconOnly="false" />
    @if(!$student->isFullyPaid())
      <x-icon-btn :href="route('parent.bank-payments.index', ['student_id' => $student->id])" icon="bi-bank"
        label="Upload bank receipt" variant="primary" :iconOnly="false" />
    @endif
    @if($student->isFullyPaid())
      <x-icon-btn :href="route('parent.students.clearance-certificate', $student)" icon="bi-file-earmark-pdf"
        label="Download clearance certificate" variant="success" :iconOnly="false" />
    @endif
      <x-icon-btn type="button" icon="printer" label="Print statement" variant="outline-primary" :iconOnly="false" onclick="window.print()" />
  </div>
@endsection

@section('content')
<div class="card mb-3 border-primary-subtle">
  <div class="card-body">
    <div class="row g-3 align-items-center">
      <div class="col-md-8">
        <h2 class="h5 fw-semibold mb-1"><i class="bi bi-person-vcard me-2"></i>{{ $student->name }}</h2>
        <div class="text-muted small">
          Class: <strong>{{ $student->class_name ?? 'N/A' }}</strong>
          @if($student->admission_no) • Admission: <strong>{{ $student->admission_no }}</strong> @endif
        </div>
        @if($student->primaryParentLink)
          <div class="small mt-1">
            <i class="bi bi-shield-check text-success me-1"></i>
            Linked {{ $student->primaryParentLink->relationship }}:
            {{ $student->parentUser?->name ?? 'Guardian' }}
            @if($student->parentUser)({{ $student->parentUser->email }})@endif
          </div>
        @endif
      </div>
      <div class="col-md-4 text-md-end">
        @if($student->isFullyPaid())
          <span class="badge text-bg-success mb-2"><i class="bi bi-check-circle me-1"></i>Fully paid</span>
          <div class="small text-muted">Outstanding balance</div>
          <div class="fs-4 fw-bold text-success">Tsh 0</div>
          <div class="small text-success mt-1">All assigned fees have been cleared.</div>
        @else
          <div class="small text-muted">Outstanding balance</div>
          <div class="fs-4 fw-bold text-danger">Tsh {{ number_format($student->balance) }}</div>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="small text-muted">Expected (structures)</div>
        <div class="fw-semibold fs-5">Tsh {{ number_format($student->expected_amount) }}</div>
        <div class="small text-muted mt-1">Paid: Tsh {{ number_format($student->paid_amount) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="small text-muted">Fee due date</div>
        <div class="fw-semibold">{{ $student->fee_due_date?->format('Y-m-d') ?? 'Not set' }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="small text-muted">Contact on file</div>
        <div>{{ $student->parent_phone ?? 'No phone' }}</div>
        <div class="small text-muted">{{ $student->parent_email }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header fw-semibold"><i class="bi bi-cash-coin me-2"></i>Fee structures for this student</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Class</th>
            <th class="text-end">Amount</th>
            <th>Due date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($student->feeStructures as $structure)
            <tr>
              <td>{{ $structure->name }}</td>
              <td>{{ $structure->class_name ?? 'All' }}</td>
              <td class="text-end">Tsh {{ number_format($structure->amount) }}</td>
              <td>{{ $structure->due_date?->format('Y-m-d') ?? '—' }}</td>
              <td>
                <span class="badge {{ $structure->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                  {{ $structure->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-3">No fee structures assigned.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold"><i class="bi bi-receipt me-2"></i>Payment history</div>
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
            <tr><td colspan="6" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
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
