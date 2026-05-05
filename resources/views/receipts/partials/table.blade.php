<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold">All Receipts</span>
    <a href="{{ route('receipts.create') }}" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-receipt-cutoff me-1"></i> Generate Receipt
    </a>
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
            <th>Date</th>
            <th>Mode</th>
            <th>Ref</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse($receipts as $r)
            <tr>
              <td class="fw-semibold">{{ $r->receipt_no }}</td>
              <td>{{ $r->student_name }}</td>
              <td>{{ $r->class_name ?? '—' }}</td>
              <td>Tsh {{ number_format($r->amount) }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($r->payment_date)->toDateString() }}</td>
              <td>{{ $r->payment_mode }}</td>
              <td>{{ $r->reference ?: '—' }}</td>
              <td class="text-end">
                <a href="{{ route('receipts.show',$r) }}" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">No receipts found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if(method_exists($receipts,'links'))
    <div class="card-footer">
      {{-- Note: pagination is server-driven; for AJAX you can intercept link clicks if desired --}}
      {{ $receipts->links() }}
    </div>
  @endif
</div>
