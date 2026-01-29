@extends('layouts.app')
@section('title','Dashboard')

@section('content')
<div class="row g-3">
  {{-- KPI Cards --}}
  <div class="col-12 col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Total Collected</div>
        <div class="fs-4 fw-semibold mt-1">Tsh {{ $metrics['total_collected'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Today (Amount)</div>
        <div class="fs-4 fw-semibold mt-1">Tsh {{ $metrics['today_total'] }}</div>
        <div class="small text-muted">{{ $metrics['today_count'] }} receipts</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">This Month (Amount)</div>
        <div class="fs-4 fw-semibold mt-1">Tsh {{ $metrics['month_total'] }}</div>
        <div class="small text-muted">{{ $metrics['month_count'] }} receipts</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-md-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="text-muted small">Quick Actions</div>
        <div class="d-grid mt-2">
          <a href="{{ route('receipts.create') }}" class="btn btn-primary">
            <i class="bi bi-receipt-cutoff me-1"></i> Generate Receipt
          </a>
        </div>
      </div>
    </div>
  </div>

  {{-- By Mode (This Month) --}}
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold">This Month by Mode</div>
      <div class="card-body">
        @forelse($byMode as $row)
          <div class="d-flex justify-content-between">
            <div>{{ $row->payment_mode }}</div>
            <div class="text-muted">Tsh {{ number_format($row->s ?? 0) }} • {{ $row->c }}</div>
          </div>
          <div class="progress my-2" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
            @php
              $max = max(1, $byMode->max('s'));
              $pct = (int) round(($row->s ?? 0) / $max * 100);
            @endphp
            <div class="progress-bar" style="width: {{ $pct }}%"></div>
          </div>
        @empty
          <div class="text-muted">No data yet.</div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- Top Classes (Year) --}}
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold">Top Classes (This Year)</div>
      <div class="card-body">
        @forelse($topClasses as $row)
          <div class="d-flex justify-content-between py-1 border-bottom">
            <div class="fw-semibold">{{ $row->classRoom->name ?? '—' }}</div>
            <div>Tsh {{ number_format($row->s ?? 0) }}</div>
          </div>
        @empty
          <div class="text-muted">No data yet.</div>
        @endforelse
      </div>
    </div>
  </div>

  {{-- Recent Receipts --}}
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold">Recent Receipts</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th><th>Student</th><th>Class</th><th>Amt</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recent as $r)
                <tr>
                  <td><a href="{{ route('receipts.show',$r) }}" class="text-decoration-none">{{ $r->receipt_no }}</a></td>
                  <td>{{ $r->student_name }}</td>
                  <td>{{ $r->classRoom->name ?? '' }}/{{ $r->stream->name ?? '' }}</td>
                  <td>Tsh {{ number_format($r->amount) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No receipts yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer text-end">
        <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary btn-sm">View All</a>
      </div>
    </div>
  </div>
  <hr class="my-4">

<h6 class="text-muted fw-semibold mb-2">Totals by Payment Category (This Month)</h6>

@php $maxCat = max(1, $byCategory->max('s')); @endphp

<div class="row g-3">
  @forelse($byCategory as $cat)
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div class="fw-semibold">{{ $cat->name }}</div>
            <span class="badge text-bg-light">{{ $cat->c }}</span>
          </div>
          <div class="fs-5 fw-bold mt-1">Tsh {{ number_format($cat->s ?? 0) }}</div>
          @php $pct = (int) round(($cat->s ?? 0) / $maxCat * 100); @endphp
          <div class="progress mt-2" style="height:6px;"> 
            <div class="progress-bar" style="width: {{ $pct }}%"></div>
          </div>
        </div>
      </div>
    </div>
  @empty
    <div class="col-12"><div class="text-muted">No category data yet.</div></div>
  @endforelse
</div>

</div>
@endsection
