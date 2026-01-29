@extends('layouts.app')
@section('title','Receipts')

@section('actions')
  <a href="{{ route('receipts.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i> New Receipt
  </a>
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
        <label for="class_id" class="form-label small text-muted mb-1">Filter by Class</label>
        <select id="class_id" name="class_id" class="form-select">
          <option value="">All classes</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" @selected((string)$classId === (string)$c->id)>{{ $c->name }}</option>
          @endforeach
        </select>
      </div>

      {{-- NEW: Payment Category --}}
      <div class="col-12 col-md-3">
        <label for="payment_category_id" class="form-label small text-muted mb-1">Filter by Category</label>
        <select id="payment_category_id" name="payment_category_id" class="form-select">
          <option value="">All categories</option>
          @foreach($categories as $pc)
            <option value="{{ $pc->id }}" @selected((string)($categoryId ?? '') === (string)$pc->id)>{{ $pc->name }}</option>
          @endforeach
        </select>
      </div>

      <div class="col-12 col-md-3 d-flex gap-2">
        <button class="btn btn-primary flex-grow-1">
          <i class="bi bi-funnel me-1"></i> Apply
        </button>
        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>


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
            <th>Class / Stream</th>
            <th>Amount</th>
            <th>Category</th>
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
              <td>{{ $r->classRoom->name ?? '' }} / {{ $r->stream->name ?? '' }}</td>
              <td>Tsh {{ number_format($r->amount) }}</td>
              <td>{{ $r->paymentCategories->pluck('name')->implode(', ') ?: '—' }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($r->payment_date)->toDateString() }}</td>
              <td>{{ $r->payment_mode }}</td>
              <td>{{ $r->reference ?: '—' }}</td>
              <td class="text-end">
                <div class="btn-group" role="group">
                  <a href="{{ route('receipts.show',$r) }}" class="btn btn-sm btn-outline-secondary" title="View">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('receipts.edit',$r) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form action="{{ route('receipts.destroy',$r) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this receipt? This action cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
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

@push('scripts')
  <script>
  // Debounce helper
  const debounce = (fn, d=300) => { let t; return (...a) => { clearTimeout(t); t=setTimeout(()=>fn(...a), d); }; };

  const form   = document.getElementById('filtersForm');
  const q      = document.getElementById('q');
  const classS = document.getElementById('class_id');
  const streamS= document.getElementById('stream_id');
  const from   = document.getElementById('from');
  const to     = document.getElementById('to');
  const results= document.getElementById('receiptsResults');

  async function refreshResults() {
    const params = new URLSearchParams(new FormData(form)).toString();
    const url = `{{ route('receipts.partial') }}?${params}`;
    const res = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
    results.innerHTML = await res.text();
  }

  // Instant search & filters
  q.addEventListener('input', debounce(refreshResults, 250));
  from.addEventListener('change', refreshResults);
  to.addEventListener('change', refreshResults);
  classS.addEventListener('change', async () => {
    // Load streams for selected class
    const cid = classS.value;
    streamS.innerHTML = '<option value="">All</option>';
    if (cid) {
      try {
        const res = await fetch(`{{ url('/api/classes') }}/${cid}/streams`);
        const data = await res.json();
        data.forEach(s => {
          const opt = document.createElement('option');
          opt.value = s.id; opt.textContent = s.name;
          streamS.appendChild(opt);
        });
      } catch (e) {}
    }
    refreshResults();
  });
  streamS.addEventListener('change', refreshResults);

  // Prevent full-page submit if user hits enter
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    refreshResults();
  });
</script>
@endpush
