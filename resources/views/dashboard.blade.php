@extends('layouts.app')
@section('title','Dashboard')

@section('content')
<div class="page-hero-school">
  <p class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Fee collection overview</p>
</div>

<div class="row g-3">
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 stat-card-school">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;letter-spacing:0.06em;">
          <i class="bi bi-cash-stack text-school-primary"></i> Total Collected
        </div>
        <div class="fs-4 fw-bold mt-2 stat-value text-school-primary">Tsh {{ $metrics['total_collected'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 stat-card-school">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;letter-spacing:0.06em;">
          <i class="bi bi-calendar-day text-school-primary"></i> Today
        </div>
        <div class="fs-4 fw-bold mt-2 stat-value text-school-primary">Tsh {{ $metrics['today_total'] }}</div>
        <div class="small text-muted mt-1">{{ $metrics['today_count'] }} receipts</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 stat-card-school">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;letter-spacing:0.06em;">
          <i class="bi bi-calendar-month text-school-primary"></i> This Month
        </div>
        <div class="fs-4 fw-bold mt-2 stat-value text-school-primary">Tsh {{ $metrics['month_total'] }}</div>
        <div class="small text-muted mt-1">{{ $metrics['month_count'] }} receipts</div>
      </div>
    </div>
  </div>
  <div class="col-12 col-sm-6 col-xl-3">
    <div class="card h-100 border-danger-subtle">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 text-muted small text-uppercase fw-semibold" style="font-size:0.7rem;letter-spacing:0.06em;">
          <i class="bi bi-exclamation-circle text-danger"></i> Outstanding
        </div>
        <div class="fs-4 fw-bold mt-2 text-danger">Tsh {{ $metrics['outstanding_total'] }}</div>
        <div class="small text-muted mt-1">{{ $metrics['outstanding_students'] }} students • {{ $metrics['overdue_count'] }} overdue</div>
        <div class="mt-2">
          <x-icon-btn :href="route('reports.unpaid')" icon="bi-list-check" label="View unpaid report" variant="outline-danger" size="sm" :iconOnly="false" />
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="toolbar-icon-group">
      <x-icon-btn :href="route('receipts.create')" icon="bi-receipt-cutoff" label="Generate receipt" variant="primary" :iconOnly="false" />
      <x-icon-btn :href="route('students.create')" icon="bi-person-plus" label="Register student" variant="outline-primary" :iconOnly="false" />
      <x-icon-btn :href="route('reports.index')" icon="bi-graph-up" label="Reports" variant="outline-secondary" :iconOnly="false" />
      <x-icon-btn :href="route('notification-logs.index')" icon="bi-bell" label="Notifications" variant="outline-secondary" :iconOnly="false" />
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold"><i class="bi bi-pie-chart me-2"></i>This Month by Mode</div>
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

  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold"><i class="bi bi-mortarboard me-2"></i>Top Classes (This Year)</div>
      <div class="card-body">
        @forelse($topClasses as $row)
          <div class="d-flex justify-content-between py-1 border-bottom">
            <div class="fw-semibold">{{ $row->class_name ?? '—' }}</div>
            <div>Tsh {{ number_format($row->s ?? 0) }}</div>
          </div>
        @empty
          <div class="text-muted">No data yet.</div>
        @endforelse
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock-history me-2"></i>Recent Receipts</span>
        <x-icon-btn :href="route('receipts.index')" icon="bi-arrow-right" label="View all receipts" variant="outline-secondary" size="sm" />
      </div>
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
                  <td>{{ $r->class_name ?? '' }}</td>
                  <td>Tsh {{ number_format($r->amount) }}</td>
                </tr>
              @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No receipts yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12">
    <h6 class="text-muted fw-semibold mb-2"><i class="bi bi-tags me-2"></i>Totals by Payment Category (This Month)</h6>
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
</div>
@endsection
