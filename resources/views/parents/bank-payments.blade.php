@extends('layouts.app')
@section('title', 'Pay School Fees by Bank')

@section('actions')
  <x-icon-btn :href="route('parent.dashboard')" icon="arrow-left" label="Back to portal" variant="outline-secondary" :iconOnly="false" />
@endsection

@section('content')
@if($setting && ($setting->bank_nmb_account_number || $setting->bank_crdb_account_number))
  <div class="card mb-3 border-primary-subtle">
    <div class="card-header fw-semibold"><i class="bi bi-bank me-2"></i>Pay fees to the school bank account</div>
    <div class="card-body">
      <p class="small text-muted mb-3">Deposit school fees at NMB or CRDB, then upload the bank receipt PDF below. If you have more than one child, choose which student the payment is for.</p>
      <div class="row g-3">
        @if($setting->bank_nmb_account_number)
          <div class="col-md-6">
            <div class="p-3 rounded border bg-light h-100">
              <div class="fw-semibold text-school-primary">NMB Bank</div>
              <div>{{ $setting->bank_nmb_account_name ?: $setting->school_name }}</div>
              <div class="fs-5 fw-bold mt-1">{{ $setting->bank_nmb_account_number }}</div>
            </div>
          </div>
        @endif
        @if($setting->bank_crdb_account_number)
          <div class="col-md-6">
            <div class="p-3 rounded border bg-light h-100">
              <div class="fw-semibold text-school-primary">CRDB Bank</div>
              <div>{{ $setting->bank_crdb_account_name ?: $setting->school_name }}</div>
              <div class="fs-5 fw-bold mt-1">{{ $setting->bank_crdb_account_number }}</div>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
@endif

<div class="card mb-3">
  <div class="card-header fw-semibold"><i class="bi bi-cloud-upload me-2"></i>Upload bank payment receipt (PDF)</div>
  <div class="card-body">
  @if($students->isEmpty())
    <p class="text-muted mb-0">No linked students found on your account.</p>
  @else
    <form method="POST" action="{{ route('parent.bank-payments.store') }}" enctype="multipart/form-data" class="row g-3">
      @csrf
      <div class="col-md-6">
        <label for="student_id" class="form-label">Student this payment is for</label>
        <select name="student_id" id="student_id" class="form-select" required>
          <option value="">Select student…</option>
            @foreach($students as $student)
            <option value="{{ $student->id }}" @selected((string) ($selectedStudentId ?? '') === (string) $student->id)>
              {{ $student->name }} ({{ $student->class_name }}) — Balance Tsh {{ number_format($student->balance) }}
            </option>
          @endforeach
        </select>
        @error('student_id')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-6">
        <label for="receipt_pdf" class="form-label">Bank receipt PDF (NMB or CRDB)</label>
        <input type="file" name="receipt_pdf" id="receipt_pdf" class="form-control" accept="application/pdf" required>
        <div class="form-text">Max 5 MB. Use the official receipt PDF from your bank after payment.</div>
        @error('receipt_pdf')<div class="text-danger small">{{ $message }}</div>@enderror
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-school-primary">
          <i class="bi bi-shield-check me-1"></i> Upload &amp; verify payment
        </button>
      </div>
    </form>
  @endif
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold"><i class="bi bi-clock-history me-2"></i>Your bank payment uploads</div>
  <div class="card-body p-0">
    <div class="mobile-card-list p-3">
      @forelse($submissions as $submission)
        <div class="mobile-data-card">
          <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div>
              <div class="fw-semibold">{{ $submission->student?->name }}</div>
              <div class="small text-muted">{{ $submission->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <span class="badge text-bg-{{ $submission->statusBadge() }}">{{ $submission->statusLabel() }}</span>
          </div>
          <div class="mobile-data-row">
            <span class="mobile-data-label">Bank</span>
            <span class="mobile-data-value">{{ $submission->bankLabel() }}</span>
          </div>
          <div class="mobile-data-row">
            <span class="mobile-data-label">Amount</span>
            <span class="mobile-data-value">{{ $submission->extracted_amount ? 'Tsh '.number_format($submission->extracted_amount) : '—' }}</span>
          </div>
          <div class="mobile-data-row">
            <span class="mobile-data-label">Reference</span>
            <span class="mobile-data-value">{{ $submission->extracted_reference ?: '—' }}</span>
          </div>
          @if($submission->verification_message)
            <div class="small text-muted mt-1">{{ Str::limit($submission->verification_message, 100) }}</div>
          @endif
          <div class="small text-muted mt-1">Receipt: {{ $submission->receipt?->receipt_no ?? '—' }}</div>
        </div>
      @empty
        <p class="text-center text-muted py-4 mb-0">No bank receipts uploaded yet.</p>
      @endforelse
    </div>
    <div class="table-responsive table-responsive-desktop">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Submitted</th>
            <th>Student</th>
            <th>Bank</th>
            <th class="text-end">Amount</th>
            <th>Reference</th>
            <th>Status</th>
            <th>School receipt</th>
          </tr>
        </thead>
        <tbody>
          @forelse($submissions as $submission)
            <tr>
              <td>{{ $submission->created_at->format('Y-m-d H:i') }}</td>
              <td>{{ $submission->student?->name }}</td>
              <td>{{ $submission->bankLabel() }}</td>
              <td class="text-end">{{ $submission->extracted_amount ? 'Tsh '.number_format($submission->extracted_amount) : '—' }}</td>
              <td>{{ $submission->extracted_reference ?: '—' }}</td>
              <td>
                <span class="badge text-bg-{{ $submission->statusBadge() }}">{{ $submission->statusLabel() }}</span>
                @if($submission->verification_message)
                  <div class="small text-muted mt-1">{{ Str::limit($submission->verification_message, 80) }}</div>
                @endif
              </td>
              <td>{{ $submission->receipt?->receipt_no ?? '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No bank receipts uploaded yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($submissions->hasPages())
    <div class="card-footer">{{ $submissions->links() }}</div>
  @endif
</div>
@endsection
