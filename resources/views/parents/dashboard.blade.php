@extends('layouts.app')
@section('title','Parent Portal')

@section('content')
<div class="parent-welcome-card">
  <div class="d-flex align-items-center gap-3">
    <span class="guest-feature-icon" style="background:var(--school-primary-soft);color:var(--school-primary);width:3rem;height:3rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.35rem;">
      <i class="bi bi-person-heart"></i>
    </span>
    <div>
      <div class="welcome-name">{{ $parent->name }}</div>
      <div class="small text-muted">{{ $parent->login_identifier }}</div>
    </div>
  </div>
</div>

@if($portfolio['balance_total'] <= 0 && $portfolio['students_count'] > 0)
  <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div>
      <strong>All school fees are paid in full.</strong>
      No outstanding balance on your account. You will receive a confirmation SMS/email when future payments are recorded.
    </div>
  </div>
@endif

@if($portfolio['balance_total'] > 0)
  <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
      <i class="bi bi-bank me-1"></i>
      <strong>Pay by bank?</strong> Deposit fees to the school NMB/CRDB account and upload your bank receipt PDF for automatic verification.
    </div>
    <a href="{{ route('parent.bank-payments.index') }}" class="btn btn-sm btn-school-primary">Upload bank receipt</a>
  </div>
@endif

<div class="row g-3 mb-3">
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted"><i class="bi bi-people me-1"></i> My Children</div>
        <div class="fs-5 fw-semibold">{{ $portfolio['students_count'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted"><i class="bi bi-cash-stack me-1"></i> Expected</div>
        <div class="fs-6 fw-semibold">Tsh {{ number_format($portfolio['expected_total']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted"><i class="bi bi-check-circle me-1"></i> Paid</div>
        <div class="fs-6 fw-semibold text-success">Tsh {{ number_format($portfolio['paid_total']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted"><i class="bi bi-exclamation-circle me-1"></i> Outstanding</div>
        <div class="fs-6 fw-semibold text-danger">Tsh {{ number_format($portfolio['balance_total']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted"><i class="bi bi-calendar-event me-1"></i> Due in 7 days</div>
        <div class="fs-5 fw-semibold text-warning">{{ $portfolio['due_soon_count'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted"><i class="bi bi-alarm me-1"></i> Overdue</div>
        <div class="fs-5 fw-semibold text-danger">{{ $portfolio['overdue_count'] }}</div>
      </div>
    </div>
  </div>
</div>

@forelse($students as $student)
  <div class="card mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div>
        <span class="fw-semibold"><i class="bi bi-mortarboard me-2"></i>{{ $student->name }}</span>
        <span class="badge text-bg-light ms-2">{{ $student->class_name ?? 'Class N/A' }}</span>
        @if($student->admission_no)
          <span class="small text-muted ms-1">#{{ $student->admission_no }}</span>
        @endif
        @if($student->isFullyPaid())
          <span class="badge text-bg-success ms-2"><i class="bi bi-check-lg me-1"></i>Fully paid</span>
        @elseif($student->hasOutstandingBalance())
          <span class="badge text-bg-{{ $student->paymentStatusBadge() }} ms-2">{{ $student->paymentStatusLabel() }}</span>
        @endif
      </div>
      <x-icon-btn :href="route('parent.students.show', $student)" icon="eye" label="View full profile" variant="primary" size="sm" :iconOnly="false" />
      @if($student->isFullyPaid())
        <x-icon-btn :href="route('parent.students.clearance-certificate', $student)" icon="bi-file-earmark-pdf"
          label="Clearance certificate" variant="outline-success" size="sm" :iconOnly="false" />
      @endif
    </div>
    <div class="card-body">
      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <div class="small text-muted">Expected fees</div>
          <div class="fw-semibold">Tsh {{ number_format($student->expected_amount) }}</div>
        </div>
        <div class="col-md-3">
          <div class="small text-muted">Paid</div>
          <div class="fw-semibold text-success">Tsh {{ number_format($student->paid_amount) }}</div>
        </div>
        <div class="col-md-3">
          <div class="small text-muted">Balance</div>
          <div class="fw-semibold {{ $student->balance > 0 ? 'text-danger' : 'text-success' }}">
            Tsh {{ number_format($student->balance) }}
          </div>
        </div>
        <div class="col-md-3">
          <div class="small text-muted">Fee due date</div>
          <div class="fw-semibold">{{ $student->resolveFeeDueDate()->format('Y-m-d') }}</div>
        </div>
      </div>

      <h6 class="small text-muted text-uppercase fw-semibold mb-2"><i class="bi bi-cash-coin me-1"></i> Assigned fee structures</h6>
      @if($student->feeStructures->isNotEmpty())
        <div class="d-md-none small mb-0">
          @foreach($student->feeStructures as $structure)
            <div class="mobile-data-card py-2 px-3 mb-2">
              <div class="fw-semibold">{{ $structure->name }}</div>
              <div class="text-muted">{{ $structure->class_name ?? 'All classes' }} · Tsh {{ number_format($structure->amount) }}</div>
              <div class="text-muted">Due: {{ $structure->due_date?->format('Y-m-d') ?? '—' }}</div>
            </div>
          @endforeach
        </div>
        <div class="table-responsive d-none d-md-block">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Structure</th>
                <th>Class</th>
                <th class="text-end">Amount</th>
                <th>Due date</th>
              </tr>
            </thead>
            <tbody>
              @foreach($student->feeStructures as $structure)
                <tr>
                  <td>{{ $structure->name }}</td>
                  <td>{{ $structure->class_name ?? 'All' }}</td>
                  <td class="text-end">Tsh {{ number_format($structure->amount) }}</td>
                  <td>{{ $structure->due_date?->format('Y-m-d') ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="small text-muted mb-0">No fee structure assigned yet. Contact the school office.</p>
      @endif
    </div>
  </div>
@empty
  <div class="card">
    <div class="card-body text-center text-muted py-5">
      <i class="bi bi-person-x display-6 d-block mb-2"></i>
      <p class="mb-0">No student is linked to your account yet.</p>
      <p class="small">Contact the school office to link your child to this account.</p>
    </div>
  </div>
@endforelse
@endsection
