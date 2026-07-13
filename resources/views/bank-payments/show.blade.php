@extends('layouts.app')
@section('title', 'Bank Payment Review')

@section('actions')
  <div class="page-actions">
    <x-icon-btn :href="route('bank-payments.index')" icon="arrow-left" label="Back" variant="outline-secondary" :iconOnly="false" />
    <x-icon-btn :href="route('bank-payments.download', $submission)" icon="download" label="Download PDF" variant="outline-primary" :iconOnly="false" />
  </div>
@endsection

@section('content')
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Submission details</span>
        <span class="badge text-bg-{{ $submission->statusBadge() }}">{{ $submission->statusLabel() }}</span>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Parent</dt>
          <dd class="col-sm-8">{{ $submission->parentUser?->name }} ({{ $submission->parentUser?->phone }})</dd>
          <dt class="col-sm-4">Student</dt>
          <dd class="col-sm-8">{{ $submission->student?->name }} — {{ $submission->student?->admission_no }}</dd>
          <dt class="col-sm-4">Bank detected</dt>
          <dd class="col-sm-8">{{ $submission->bankLabel() }}</dd>
          <dt class="col-sm-4">Amount extracted</dt>
          <dd class="col-sm-8">{{ $submission->extracted_amount ? 'Tsh '.number_format($submission->extracted_amount) : '—' }}</dd>
          <dt class="col-sm-4">Reference</dt>
          <dd class="col-sm-8">{{ $submission->extracted_reference ?: '—' }}</dd>
          <dt class="col-sm-4">Payment date</dt>
          <dd class="col-sm-8">{{ $submission->extracted_payment_date?->format('Y-m-d') ?? '—' }}</dd>
          <dt class="col-sm-4">Account on receipt</dt>
          <dd class="col-sm-8">{{ $submission->extracted_account_number ?: '—' }}</dd>
          <dt class="col-sm-4">Student balance</dt>
          <dd class="col-sm-8">Tsh {{ number_format($submission->student?->balance ?? 0) }}</dd>
          <dt class="col-sm-4">School receipt</dt>
          <dd class="col-sm-8">
            @if($submission->receipt)
              <a href="{{ route('receipts.show', $submission->receipt) }}">{{ $submission->receipt->receipt_no }}</a>
            @else
              —
            @endif
          </dd>
          <dt class="col-sm-4">Message</dt>
          <dd class="col-sm-8">{{ $submission->verification_message ?: '—' }}</dd>
        </dl>
      </div>
    </div>

    @if($submission->extracted_raw_text)
      <div class="card">
        <div class="card-header fw-semibold">Extracted receipt text</div>
        <div class="card-body">
          <pre class="small mb-0 text-wrap" style="white-space: pre-wrap;">{{ Str::limit($submission->extracted_raw_text, 4000) }}</pre>
        </div>
      </div>
    @endif
  </div>

  <div class="col-lg-5">
    @if($submission->status !== 'verified')
      <div class="card mb-3 border-success">
        <div class="card-header fw-semibold text-success">Approve payment</div>
        <div class="card-body">
          <p class="small text-muted">Confirm that the bank receipt is genuine and record the payment as a school receipt for this student.</p>
          <form method="POST" action="{{ route('bank-payments.approve', $submission) }}">
            @csrf
            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Record this bank payment as verified?')">
              <i class="bi bi-check-circle me-1"></i> Approve &amp; create receipt
            </button>
          </form>
        </div>
      </div>

      <div class="card border-danger">
        <div class="card-header fw-semibold text-danger">Reject submission</div>
        <div class="card-body">
          <form method="POST" action="{{ route('bank-payments.reject', $submission) }}">
            @csrf
            <div class="mb-3">
              <label for="reason" class="form-label">Reason for parent</label>
              <textarea name="reason" id="reason" class="form-control" rows="3" required>{{ old('reason') }}</textarea>
              @error('reason')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-outline-danger w-100">Reject</button>
          </form>
        </div>
      </div>
    @else
      <div class="alert alert-success">
        <i class="bi bi-check-circle me-1"></i>
        Verified {{ $submission->reviewed_at?->format('Y-m-d H:i') ?? '' }}
        @if($submission->reviewedBy) by {{ $submission->reviewedBy->name }} @endif
      </div>
    @endif
  </div>
</div>
@endsection
