@extends('layouts.app')

@section('title', 'Reports - School Receipts')

@section('content')
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card h-100 border-primary-subtle">
      <div class="card-body">
        <div class="small text-muted text-uppercase fw-semibold">School fee position</div>
        <div class="mt-2"><span class="text-muted">Expected:</span> <strong>Tsh {{ number_format($bursarSummary['expected_fees']) }}</strong></div>
        <div><span class="text-muted">Collected:</span> <strong class="text-success">Tsh {{ number_format($bursarSummary['collected']) }}</strong></div>
        <div><span class="text-muted">Outstanding:</span> <strong class="text-danger">Tsh {{ number_format($bursarSummary['outstanding']) }}</strong></div>
        <div class="small text-muted mt-2">{{ $bursarSummary['unpaid_count'] }} unpaid • {{ $bursarSummary['fully_paid'] }} cleared • {{ $bursarSummary['overdue'] }} overdue</div>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-header fw-semibold"><i class="bi bi-journal-check me-2"></i>Bursar report shortcuts</div>
      <div class="card-body d-flex flex-wrap gap-2">
        <x-icon-btn :href="route('reports.fee-position')" icon="bi-clipboard-data" label="Full fee position" variant="outline-primary" :iconOnly="false" />
        <x-icon-btn :href="route('reports.receipts')" icon="bi-receipt" label="Receipt register" variant="outline-primary" :iconOnly="false" />
        <x-icon-btn :href="route('reports.unpaid')" icon="bi-exclamation-triangle" label="Unpaid balances" variant="outline-danger" :iconOnly="false" />
        <x-icon-btn :href="route('reports.paid')" icon="bi-patch-check" label="Paid / clearance" variant="outline-success" :iconOnly="false" />
        <x-icon-btn :href="route('reports.messages')" icon="bi-chat-dots" label="SMS &amp; email history" variant="outline-primary" :iconOnly="false" />
        <x-icon-btn :href="route('reports.bank-proofs')" icon="bi-bank" label="Bank proof report" variant="outline-secondary" :iconOnly="false" />
        <x-icon-btn :href="route('messages.index')" icon="bi-send" label="Send reminders (1–5)" variant="outline-primary" :iconOnly="false" />
        <x-icon-btn :href="route('bank-payments.index')" icon="bi-folder-check" label="Review bank proofs" variant="outline-secondary" :iconOnly="false" />
      </div>
    </div>
  </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>School Fee Collection Report
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-4">
                    Generate an official fee collection report listing students who paid school fees in the selected period.
                    Amounts come from recorded receipts. For the full school fee position (expected vs paid vs balance), use
                    <a href="{{ route('reports.fee-position') }}">Full fee position</a> or
                    <a href="{{ route('reports.receipts') }}">Receipt register</a>.
                </p>

                <form method="POST" action="{{ route('reports.generate') }}" id="reportForm">
                    @csrf

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Payment Period</label>
                            <select name="date_range" id="dateRange" class="form-select" required>
                                <option value="">Select period</option>
                                <option value="today">Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="this_week">This Week</option>
                                <option value="last_week">Last Week</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_year">This Year</option>
                                <option value="last_year">Last Year</option>
                                <option value="custom">Custom Range</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4" id="customDateRange" style="display: none;">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="startDate" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" id="endDate" class="form-control">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Class</label>
                            <input type="text" name="class_name" class="form-control" placeholder="e.g. Form I">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Payment Category</label>
                            <select name="payment_category_id" class="form-select">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Payment Mode</label>
                            <select name="payment_mode" class="form-select">
                                <option value="">All Modes</option>
                                <option value="Cash">Cash</option>
                                <option value="Bank">Bank</option>
                                <option value="Mobile Money">Mobile Money</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Minimum Amount Paid (Tsh)</label>
                            <input type="number" name="min_amount" class="form-control" placeholder="0" step="1" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Maximum Amount Paid (Tsh)</label>
                            <input type="number" name="max_amount" class="form-control" placeholder="0" step="1" min="0">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="toolbar-icon-group">
                                <x-icon-btn :href="route('reports.unpaid')" icon="bi-exclamation-triangle" label="Unpaid report"
                                    variant="outline-dark" :iconOnly="false" />
                                <x-icon-btn :href="route('reports.clearance')" icon="bi-patch-check" label="Term clearance"
                                    variant="outline-success" :iconOnly="false" />
                                <x-icon-btn type="submit" icon="bi-search" label="Generate report" variant="primary" :iconOnly="false" />
                                <x-icon-btn type="submit" icon="bi-file-earmark-excel" label="Export Excel" variant="success"
                                    :iconOnly="false" formaction="{{ route('reports.export.excel') }}" />
                                <x-icon-btn type="submit" icon="bi-file-earmark-pdf" label="Export PDF" variant="danger"
                                    :iconOnly="false" formaction="{{ route('reports.export.pdf') }}" />
                                <x-icon-btn type="button" icon="bi-arrow-counterclockwise" label="Reset form" variant="outline-secondary"
                                    onclick="resetForm()" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateRange = document.getElementById('dateRange');
    const customDateRange = document.getElementById('customDateRange');

    dateRange.addEventListener('change', function() {
        if (this.value === 'custom') {
            customDateRange.style.display = 'block';
            document.getElementById('startDate').required = true;
            document.getElementById('endDate').required = true;
        } else {
            customDateRange.style.display = 'none';
            document.getElementById('startDate').required = false;
            document.getElementById('endDate').required = false;
        }
    });

    const today = new Date().toISOString().split('T')[0];
    document.getElementById('startDate').value = today;
    document.getElementById('endDate').value = today;
});

function resetForm() {
    document.getElementById('reportForm').reset();
    document.getElementById('customDateRange').style.display = 'none';
}
</script>
@endpush
