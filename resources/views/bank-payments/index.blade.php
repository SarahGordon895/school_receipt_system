@extends('layouts.app')
@section('title', 'Bank Payment Receipts')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
  <a href="{{ route('bank-payments.index') }}" class="btn btn-sm {{ empty($status) ? 'btn-school-primary' : 'btn-outline-secondary' }}">All</a>
  <a href="{{ route('bank-payments.index', ['status' => 'review']) }}" class="btn btn-sm {{ $status === 'review' ? 'btn-warning' : 'btn-outline-secondary' }}">
    Needs review ({{ $counts['review'] ?? 0 }})
  </a>
  <a href="{{ route('bank-payments.index', ['status' => 'pending']) }}" class="btn btn-sm {{ $status === 'pending' ? 'btn-secondary' : 'btn-outline-secondary' }}">
    Pending ({{ $counts['pending'] ?? 0 }})
  </a>
  <a href="{{ route('bank-payments.index', ['status' => 'verified']) }}" class="btn btn-sm {{ $status === 'verified' ? 'btn-success' : 'btn-outline-secondary' }}">
    Verified ({{ $counts['verified'] ?? 0 }})
  </a>
  <a href="{{ route('bank-payments.index', ['status' => 'rejected']) }}" class="btn btn-sm {{ $status === 'rejected' ? 'btn-danger' : 'btn-outline-secondary' }}">
    Rejected ({{ $counts['rejected'] ?? 0 }})
  </a>
</div>

<div class="card">
  <div class="card-header fw-semibold"><i class="bi bi-bank me-2"></i>Parent bank receipt uploads</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Parent</th>
            <th>Student</th>
            <th>Bank</th>
            <th class="text-end">Amount</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($submissions as $submission)
            <tr>
              <td>{{ $submission->created_at->format('Y-m-d H:i') }}</td>
              <td>
                <div>{{ $submission->parentUser?->name }}</div>
                <div class="small text-muted">{{ $submission->parentUser?->phone }}</div>
              </td>
              <td>{{ $submission->student?->name }}</td>
              <td>{{ $submission->bankLabel() }}</td>
              <td class="text-end">{{ $submission->extracted_amount ? 'Tsh '.number_format($submission->extracted_amount) : '—' }}</td>
              <td><span class="badge text-bg-{{ $submission->statusBadge() }}">{{ $submission->statusLabel() }}</span></td>
              <td class="text-end">
                <a href="{{ route('bank-payments.show', $submission) }}" class="btn btn-sm btn-outline-primary">Review</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No bank payment uploads yet.</td></tr>
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
