@extends('layouts.app')
@section('title', 'Term Fee Clearance Report')

@section('actions')
  <div class="page-actions d-flex flex-wrap gap-2">
    <form method="GET" action="{{ route('reports.clearance.pdf') }}" class="d-inline">
      @if($classFilter)
        <input type="hidden" name="class_name" value="{{ $classFilter }}">
      @endif
      <x-icon-btn type="submit" icon="bi-file-earmark-pdf" label="Export PDF" variant="danger" :iconOnly="false" />
    </form>
    <x-icon-btn :href="route('reports.index')" icon="bi-arrow-left" label="Back to reports" variant="outline-primary" :iconOnly="false" />
  </div>
@endsection

@section('content')
<p class="text-muted small mb-3">
  Official list of students who have <strong>fully paid</strong> all assigned school fees (balance Tsh 0).
  Use this report for bursar records and term clearance. Individual clearance certificates can be downloaded per student.
</p>

<div class="row mb-3 g-3">
  <div class="col-md-4">
    <div class="card border-success h-100">
      <div class="card-body text-center">
        <div class="text-muted">Students cleared</div>
        <div class="fs-4 fw-bold text-success">{{ $summary['cleared_count'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-primary h-100">
      <div class="card-body text-center">
        <div class="text-muted">Total collected (cleared)</div>
        <div class="fs-5 fw-bold">Tsh {{ format_tzs($summary['total_collected']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-secondary h-100">
      <div class="card-body text-center">
        <div class="text-muted">Classes represented</div>
        <div class="fs-4 fw-bold">{{ $summary['classes_count'] }}</div>
      </div>
    </div>
  </div>
</div>

<form method="GET" class="card mb-3">
  <div class="card-body row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label">Filter by class</label>
      <select name="class_name" class="form-select" onchange="this.form.submit()">
        <option value="">All classes</option>
        @foreach($classes as $class)
          <option value="{{ $class }}" @selected($classFilter === $class)>{{ $class }}</option>
        @endforeach
      </select>
    </div>
  </div>
</form>

<div class="card">
  <div class="card-header fw-semibold"><i class="bi bi-patch-check me-2"></i>Students Cleared for Term</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>S/N</th>
            <th>Admission No</th>
            <th>Student</th>
            <th>Class</th>
            <th>Parent</th>
            <th class="text-end">Paid (Tsh)</th>
            <th>Last payment</th>
            <th>Clearance ref</th>
            <th class="text-end">Certificate</th>
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
              <td>{{ $student->parent_name ?? '—' }}</td>
              <td class="text-end text-success fw-semibold">{{ format_tzs($row['paid']) }}</td>
              <td>
                @if($row['last_payment_date'])
                  {{ \Carbon\Carbon::parse($row['last_payment_date'])->format('d/m/Y') }}
                  <div class="small text-muted">{{ $row['last_receipt_no'] }}</div>
                @else
                  —
                @endif
              </td>
              <td><code class="small">{{ $row['clearance_ref'] }}</code></td>
              <td class="text-end">
                <x-icon-btn :href="route('students.clearance-certificate', $student)" icon="bi-file-earmark-pdf"
                  label="PDF" variant="outline-success" size="sm" />
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center text-muted py-4">
                No fully paid students found{{ $classFilter ? ' for class '.$classFilter : '' }}.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
