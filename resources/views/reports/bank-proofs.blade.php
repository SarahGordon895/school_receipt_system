@extends('layouts.app')
@section('title', 'Bank Payment Proofs Report')

@section('actions')
  @if($generated ?? false)
    <form method="POST" action="{{ route('reports.bank-proofs.pdf') }}" class="d-inline">
      @csrf
      @foreach($request->all() as $key => $value)
        @if(is_scalar($value) && $value !== '')
          <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
      @endforeach
      <x-icon-btn type="submit" icon="bi-file-earmark-pdf" label="Export PDF" variant="danger" :iconOnly="false" />
    </form>
  @endif
  <x-icon-btn :href="route('bank-payments.index')" icon="bi-bank" label="Review proofs" variant="outline-secondary" :iconOnly="false" />
  <x-icon-btn :href="route('reports.index')" icon="bi-arrow-left" label="Back to reports" variant="outline-primary" :iconOnly="false" />
@endsection

@section('content')
<p class="text-muted small mb-3">
  Official report of <strong>bank payment proof submissions</strong> uploaded by parents — verification status, amounts, and linked receipts.
</p>

<div class="card mb-3">
  <div class="card-header fw-semibold"><i class="bi bi-funnel me-2"></i>Generate bank proof report</div>
  <div class="card-body">
    <form method="POST" action="{{ route('reports.bank-proofs') }}">
      @csrf
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">From date</label>
          <input type="date" name="date_from" class="form-control" value="{{ old('date_from', $request->date_from ?? '') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">To date</label>
          <input type="date" name="date_to" class="form-control" value="{{ old('date_to', $request->date_to ?? '') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All</option>
            @foreach(['pending','verified','review','rejected'] as $status)
              <option value="{{ $status }}" @selected(old('status', $request->status ?? '') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Bank</label>
          <select name="bank" class="form-select">
            <option value="">All</option>
            <option value="nmb" @selected(old('bank', $request->bank ?? '') === 'nmb')>NMB</option>
            <option value="crdb" @selected(old('bank', $request->bank ?? '') === 'crdb')>CRDB</option>
          </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-school-primary w-100"><i class="bi bi-search me-1"></i> Generate report</button>
        </div>
      </div>
    </form>
  </div>
</div>

@if($generated ?? false)
<div class="row mb-3 g-3">
  <div class="col-6 col-md-2">
    <div class="card h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Total</div>
      <div class="fs-5 fw-bold">{{ $summary['total'] ?? 0 }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card border-success h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Verified</div>
      <div class="fs-5 fw-bold text-success">{{ $summary['verified'] ?? 0 }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card border-warning h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Pending / review</div>
      <div class="fs-5 fw-bold text-warning">{{ $summary['review'] ?? 0 }}</div>
    </div></div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card border-danger h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Rejected</div>
      <div class="fs-5 fw-bold text-danger">{{ $summary['rejected'] ?? 0 }}</div>
    </div></div>
  </div>
  <div class="col-12 col-md-4">
    <div class="card border-primary h-100"><div class="card-body text-center py-3">
      <div class="small text-muted">Verified amount</div>
      <div class="fs-5 fw-bold">Tsh {{ format_tzs($summary['amount_verified'] ?? 0) }}</div>
    </div></div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold"><i class="bi bi-bank me-2"></i>Bank payment proofs</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Submitted</th>
            <th>Student</th>
            <th>Parent</th>
            <th>Bank</th>
            <th class="text-end">Amount</th>
            <th>Reference</th>
            <th>Status</th>
            <th>Receipt</th>
            <th>Reviewed by</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $submission)
            <tr>
              <td>{{ $submission->created_at?->format('d/m/Y H:i') }}</td>
              <td>
                <div class="fw-semibold">{{ $submission->student?->name ?? '—' }}</div>
                <small class="text-muted">{{ $submission->student?->class_name ?? '' }}</small>
              </td>
              <td>{{ $submission->parentUser?->name ?? '—' }}</td>
              <td>{{ $submission->bankLabel() }}</td>
              <td class="text-end">Tsh {{ format_tzs($submission->extracted_amount ?? 0) }}</td>
              <td>{{ $submission->extracted_reference ?? '—' }}</td>
              <td><span class="badge text-bg-{{ $submission->statusBadge() }}">{{ $submission->statusLabel() }}</span></td>
              <td>{{ $submission->receipt?->receipt_no ?? '—' }}</td>
              <td>{{ $submission->reviewedBy?->name ?? '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No bank proofs match the selected filters.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif
@endsection
