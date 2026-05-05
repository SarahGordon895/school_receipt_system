@extends('layouts.app')
@section('title','Unpaid & Overdue Report')

@section('actions')
  <a href="{{ route('notification-logs.index') }}" class="btn btn-outline-secondary">
    <i class="bi bi-bell me-1"></i> Notification logs
  </a>
  <a href="{{ route('reports.index') }}" class="btn btn-outline-primary">
    <i class="bi bi-arrow-left me-1"></i> Reports
  </a>
@endsection

@section('content')
<div class="row mb-3">
  <div class="col-md-4">
    <div class="card border-primary">
      <div class="card-body text-center">
        <div class="text-muted">Students with Outstanding</div>
        <div class="fs-4 fw-bold">{{ $summary['students_with_balance'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-danger">
      <div class="card-body text-center">
        <div class="text-muted">Overdue Students</div>
        <div class="fs-4 fw-bold text-danger">{{ $summary['overdue_count'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-warning">
      <div class="card-body text-center">
        <div class="text-muted">Total Outstanding</div>
        <div class="fs-4 fw-bold">Tsh {{ number_format($summary['total_outstanding']) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold">Students with Balance Due</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Student</th>
            <th>Class</th>
            <th>Parent Contact</th>
            <th class="text-end">Expected</th>
            <th class="text-end">Paid</th>
            <th class="text-end">Balance</th>
            <th>Due Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($students as $row)
            <tr>
              <td class="fw-semibold">{{ $row['student']->name }}</td>
              <td>{{ $row['student']->class_name ?? 'N/A' }}</td>
              <td>{{ $row['student']->parent_phone ?? 'No phone' }}{{ $row['student']->parent_email ? ' • '.$row['student']->parent_email : '' }}</td>
              <td class="text-end">Tsh {{ number_format($row['expected']) }}</td>
              <td class="text-end">Tsh {{ number_format($row['paid']) }}</td>
              <td class="text-end fw-semibold text-danger">Tsh {{ number_format($row['balance']) }}</td>
              <td>{{ $row['student']->fee_due_date?->format('Y-m-d') ?? 'Not set' }}</td>
              <td>
                @if($row['is_overdue'])
                  <span class="badge text-bg-danger">Overdue</span>
                @else
                  <span class="badge text-bg-warning">Pending</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted py-4">No outstanding balances.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
