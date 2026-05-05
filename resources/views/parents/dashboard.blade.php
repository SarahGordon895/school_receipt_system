@extends('layouts.app')
@section('title','Parent Portal')

@section('content')
<div class="row g-3 mb-3">
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Students</div>
        <div class="fs-5 fw-semibold">{{ $portfolio['students_count'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Expected</div>
        <div class="fs-6 fw-semibold">Tsh {{ number_format($portfolio['expected_total']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Paid</div>
        <div class="fs-6 fw-semibold text-success">Tsh {{ number_format($portfolio['paid_total']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Outstanding</div>
        <div class="fs-6 fw-semibold text-danger">Tsh {{ number_format($portfolio['balance_total']) }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Due in 7 days</div>
        <div class="fs-5 fw-semibold text-warning">{{ $portfolio['due_soon_count'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Overdue</div>
        <div class="fs-5 fw-semibold text-danger">{{ $portfolio['overdue_count'] }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold">My Students Fee Status</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Student</th>
            <th>Class</th>
            <th>Expected</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Due Date</th>
            <th class="text-end">Details</th>
          </tr>
        </thead>
        <tbody>
          @forelse($students as $student)
            <tr>
              <td class="fw-semibold">{{ $student->name }}</td>
              <td>{{ $student->class_name ?? 'N/A' }}</td>
              <td>Tsh {{ number_format($student->expected_amount) }}</td>
              <td>Tsh {{ number_format($student->paid_amount) }}</td>
              <td class="{{ $student->balance > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' }}">
                Tsh {{ number_format($student->balance) }}
              </td>
              <td>{{ $student->fee_due_date?->format('Y-m-d') ?? 'Not set' }}</td>
              <td class="text-end">
                <a href="{{ route('parent.students.show', $student) }}" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-eye me-1"></i> View History
                </a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No linked students found for your account email.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
