@extends('layouts.app')
@section('title','Unpaid & Overdue Report')

@section('actions')
  <div class="page-actions">
    <form method="POST" action="{{ route('reports.unpaid.send-reminders') }}" class="d-inline"
      onsubmit="return confirm('Send fee reminder SMS and email to eligible parents now?')">
      @csrf
      <input type="hidden" name="days" value="3">
      <x-icon-btn type="submit" icon="bi-send" label="Send reminders now" variant="primary" :iconOnly="false" />
    </form>
    <x-icon-btn :href="route('notification-logs.index')" icon="bi-bell" label="Notification logs" variant="outline-secondary" :iconOnly="false" />
    <x-icon-btn :href="route('reports.index')" icon="bi-arrow-left" label="Back to reports" variant="outline-primary" :iconOnly="false" />
  </div>
@endsection

@section('content')
<div class="row mb-3 g-3">
  <div class="col-md-4">
    <div class="card border-primary h-100">
      <div class="card-body text-center">
        <i class="bi bi-people fs-4 text-primary"></i>
        <div class="text-muted mt-2">Students with Outstanding</div>
        <div class="fs-4 fw-bold">{{ $summary['students_with_balance'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-danger h-100">
      <div class="card-body text-center">
        <i class="bi bi-alarm fs-4 text-danger"></i>
        <div class="text-muted mt-2">Overdue Students</div>
        <div class="fs-4 fw-bold text-danger">{{ $summary['overdue_count'] }}</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card border-warning h-100">
      <div class="card-body text-center">
        <i class="bi bi-cash-coin fs-4 text-warning"></i>
        <div class="text-muted mt-2">Total Outstanding</div>
        <div class="fs-4 fw-bold">Tsh {{ number_format($summary['total_outstanding']) }}</div>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header fw-semibold"><i class="bi bi-exclamation-triangle me-2"></i>Students with Balance Due</div>
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
            <th class="text-end">Actions</th>
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
              <td class="text-end">
                <div class="table-actions justify-content-end">
                  <x-icon-btn :href="route('notification-logs.send.create', ['student_id' => $row['student']->id])"
                    icon="bi-send" label="Send reminder" variant="outline-primary" size="sm" />
                  <form method="POST" action="{{ route('students.send-reminder', $row['student']) }}" class="d-inline"
                    onsubmit="return confirm('Send SMS and email reminder to this parent now?')">
                    @csrf
                    <input type="hidden" name="send_sms" value="1">
                    <input type="hidden" name="send_email" value="1">
                    <x-icon-btn type="submit" icon="bi-lightning" label="Quick send" variant="outline-success" size="sm" />
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No outstanding balances.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
