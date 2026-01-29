@extends('layouts.app')

@section('title', 'Reports - School Receipts')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-graph-up me-2"></i>Generate Reports
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('reports.generate') }}" id="reportForm">
                    @csrf
                    
                    <!-- Date Range Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date Range</label>
                            <select name="date_range" id="dateRange" class="form-select" required>
                                <option value="">Select Date Range</option>
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

                    <!-- Custom Date Range (Hidden by default) -->
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

                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Class</label>
                            <select name="class_id" id="classFilter" class="form-select">
                                <option value="">All Classes</option>
                                @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Stream</label>
                            <select name="stream_id" id="streamFilter" class="form-select">
                                <option value="">All Streams</option>
                            </select>
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

                    <!-- Amount Range -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Minimum Amount</label>
                            <input type="number" name="min_amount" class="form-control" placeholder="0.00" step="0.01" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Maximum Amount</label>
                            <input type="number" name="max_amount" class="form-control" placeholder="0.00" step="0.01" min="0">
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-2"></i>Generate Report
                            </button>
                            <button type="submit" formaction="{{ route('reports.export.excel') }}" class="btn btn-success ms-2">
                                <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                            </button>
                            <button type="submit" formaction="{{ route('reports.export.pdf') }}" class="btn btn-danger ms-2">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Export to PDF
                            </button>
                            <button type="button" class="btn btn-secondary ms-2" onclick="resetForm()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reset
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Results Section (Hidden initially) -->
<div id="resultsSection" style="display: none;">
    <!-- Results will be loaded here via AJAX or full page reload -->
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const dateRange = document.getElementById('dateRange');
    const customDateRange = document.getElementById('customDateRange');
    const classFilter = document.getElementById('classFilter');
    const streamFilter = document.getElementById('streamFilter');

    // Show/hide custom date range
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

    // Load streams when class changes
    classFilter.addEventListener('change', function() {
        const classId = this.value;
        
        // Clear existing options
        streamFilter.innerHTML = '<option value="">All Streams</option>';
        
        if (classId) {
            fetch(`/api/classes/${classId}/streams`)
                .then(response => response.json())
                .then(streams => {
                    streams.forEach(stream => {
                        const option = document.createElement('option');
                        option.value = stream.id;
                        option.textContent = stream.name;
                        streamFilter.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading streams:', error));
        }
    });

    // Set today's date as default for custom range
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('startDate').value = today;
    document.getElementById('endDate').value = today;
});

function resetForm() {
    document.getElementById('reportForm').reset();
    document.getElementById('customDateRange').style.display = 'none';
    document.getElementById('streamFilter').innerHTML = '<option value="">All Streams</option>';
}
</script>
@endpush
