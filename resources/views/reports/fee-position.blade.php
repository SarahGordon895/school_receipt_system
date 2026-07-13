@extends('layouts.app')
@section('title', 'School Fee Position Report')

@section('actions')
  <div class="page-actions d-flex flex-wrap gap-2">
    <form method="GET" action="{{ route('reports.fee-position.pdf') }}" class="d-inline">
      @if($classFilter)<input type="hidden" name="class_name" value="{{ $classFilter }}">@endif
      @if($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
      <x-icon-btn type="submit" icon="bi-file-earmark-pdf" label="Export PDF" variant="danger" :iconOnly="false" />
    </form>
    <x-icon-btn :href="route('reports.index')" icon="bi-arrow-left" label="Back to reports" variant="outline-primary" :iconOnly="false" />
  </div>
@endsection

@section('content')
<p class="text-muted small mb-3">
  Official fee position built from <strong>assigned fee structures</strong> and <strong>recorded receipts</strong> in the system.
  Expected fees, amounts paid, balances, and receipt references match bursar records.
</p>

<div class="row mb-3 g-3">
  <div class="col-6 col-md-3">
    <div class="card border-primary h-100">
      <div class="card-body text-center py-3">
        <div class="small text-muted">Students listed</div>
        <div class="fs-4 fw-bold">{{ $summary['students_count'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-secondary h-100">
      <div class="card-body text-center py-3">
        <div class="small text-muted">Expected (Tsh)</div>
        <div class="fs-6 fw-bold">{{ format_tzs($summary['total_expected']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-success h-100">
      <div class="card-body text-center py-3">
        <div class="small text-muted">Collected (Tsh)</div>
        <div class="fs-6 fw-bold text-success">{{ format_tzs($summary['total_collected']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-danger h-100">
      <div class="card-body text-center py-3">
        <div class="small text-muted">Outstanding (Tsh)</div>
        <div class="fs-6 fw-bold text-danger">{{ format_tzs($summary['total_outstanding']) }}</div>
      </div>
    </div>
  </div>
</div>

<form method="GET" class="card mb-3">
  <div class="card-body row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Class</label>
      <select name="class_name" class="form-select" onchange="this.form.submit()">
        <option value="">All classes</option>
        @foreach($classes as $class)
          <option value="{{ $class }}" @selected($classFilter === $class)>{{ $class }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Status</label>
      <select name="status" class="form-select" onchange="this.form.submit()">
        <option value="">All students</option>
        <option value="unpaid" @selected($statusFilter === 'unpaid')>Outstanding balance</option>
        <option value="partial" @selected($statusFilter === 'partial')>Partially paid</option>
        <option value="cleared" @selected($statusFilter === 'cleared')>Fully paid</option>
      </select>
    </div>
    <div class="col-md-4 small text-muted">
      {{ $summary['receipt_count'] }} receipt(s) on file • {{ $summary['fully_paid_count'] }} fully paid school-wide • {{ $summary['unpaid_count'] }} with balance
    </div>
  </div>
</form>

<div class="card">
  <div class="card-header fw-semibold"><i class="bi bi-table me-2"></i>Fee position by student</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>S/N</th>
            <th>Admission</th>
            <th>Student</th>
            <th>Class</th>
            <th class="text-end">Expected</th>
            <th class="text-end">Paid</th>
            <th class="text-end">Balance</th>
            <th>Receipts</th>
            <th>Last receipt</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $index => $row)
            @php $student = $row['student']; @endphp
            <tr>
              <td>{{ $index + 1 }}</td>
              <td>{{ $student->admission_no ?? '—' }}</td>
              <td class="fw-semibold">{{ $student->name }}</td>
              <td>{{ $student->class_name ?? '—' }}</td>
              <td class="text-end">Tsh {{ format_tzs($row['expected']) }}</td>
              <td class="text-end text-success">Tsh {{ format_tzs($row['paid']) }}</td>
              <td class="text-end {{ $row['balance'] > 0 ? 'text-danger fw-semibold' : '' }}">Tsh {{ format_tzs($row['balance']) }}</td>
              <td>{{ $row['receipt_count'] }}</td>
              <td>
                @if($row['last_receipt_no'])
                  <div class="small fw-semibold">{{ $row['last_receipt_no'] }}</div>
                  <div class="small text-muted">{{ $row['last_payment_date'] ? \Carbon\Carbon::parse($row['last_payment_date'])->format('d/m/Y') : '' }}</div>
                @else
                  —
                @endif
              </td>
              <td><span class="badge text-bg-{{ $row['status_badge'] }}">{{ $row['status'] }}</span></td>
            </tr>
          @empty
            <tr><td colspan="10" class="text-center text-muted py-4">No students match the selected filters.</td></tr>
          @endforelse
        </tbody>
        @if($rows->isNotEmpty())
          <tfoot class="table-light">
            <tr>
              <th colspan="4" class="text-end">TOTALS:</th>
              <th class="text-end">Tsh {{ format_tzs($summary['total_expected']) }}</th>
              <th class="text-end">Tsh {{ format_tzs($summary['total_collected']) }}</th>
              <th class="text-end">Tsh {{ format_tzs($summary['total_outstanding']) }}</th>
              <th colspan="3"></th>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>
</div>
@endsection
