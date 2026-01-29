@extends('layouts.app')

@section('title', 'Report Results - School Receipts')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h5 class="card-title text-primary">Total Receipts</h5>
                        <h3 class="mb-0">{{ $summary['total_receipts'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h5 class="card-title text-success">Total Amount</h5>
                        <h3 class="mb-0">{{ number_format($summary['total_amount'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h5 class="card-title text-info">Average Amount</h5>
                        <h3 class="mb-0">{{ number_format($summary['average_amount'], 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h5 class="card-title text-warning">Date Range</h5>
                        <p class="mb-0 small">
                            @if($request->date_range === 'custom')
                                {{ $request->start_date }} to {{ $request->end_date }}
                            @else
                                {{ ucfirst(str_replace('_', ' ', $request->date_range)) }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="{{ route('reports.export.excel') }}" class="d-inline-block">
                    @csrf
                    @foreach($request->all() as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-file-earmark-excel me-2"></i>Export to Excel
                    </button>
                </form>
                
                <form method="POST" action="{{ route('reports.export.pdf') }}" class="d-inline-block ms-2">
                    @csrf
                    @foreach($request->all() as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Export to PDF
                    </button>
                </form>

                <a href="{{ route('reports.index') }}" class="btn btn-secondary ms-2">
                    <i class="bi bi-arrow-left me-2"></i>Back to Filters
                </a>
            </div>
        </div>

        <!-- Receipts Table -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-table me-2"></i>Receipt Details ({{ $receipts->count() }} records)
                </h5>
            </div>
            <div class="card-body">
                @if($receipts->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Receipt No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Stream</th>
                                    <th>Payment Category</th>
                                    <th>Amount</th>
                                    <th>Payment Date</th>
                                    <th>Payment Mode</th>
                                    <th>Reference</th>
                                    <th>Created By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($receipts as $receipt)
                                <tr>
                                    <td>
                                        <a href="{{ route('receipts.show', $receipt) }}" class="text-decoration-none">
                                            {{ $receipt->receipt_no }}
                                        </a>
                                    </td>
                                    <td>{{ $receipt->student_name }}</td>
                                    <td>{{ $receipt->classRoom?->name ?? 'N/A' }}</td>
                                    <td>{{ $receipt->stream?->name ?? 'N/A' }}</td>
                                    <td>{{ $receipt->paymentCategories->pluck('name')->implode(', ') ?? 'N/A' }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($receipt->amount, 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($receipt->payment_date)->format('d/m/Y') }}</td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $receipt->payment_mode }}</span>
                                    </td>
                                    <td>{{ $receipt->reference ?? 'N/A' }}</td>
                                    <td>{{ $receipt->user?->name ?? 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr style="background-color: #e3f2fd; border-top: 2px solid #1976d2;">
                                    <th colspan="5" class="text-end fw-bold text-primary">TOTAL AMOUNT:</th>
                                    <th class="text-end fw-bold" style="font-size: 18px; color: #1976d2;">
                                        {{ number_format($receipts->sum('amount'), 2) }}
                                    </th>
                                    <th colspan="4"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No receipts found matching the selected criteria.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Additional Statistics -->
        @if($summary['payment_modes']->count() > 0 || $summary['categories']->count() > 0)
        <div class="row mt-4">
            @if($summary['payment_modes']->count() > 0)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Payment Modes Breakdown</h6>
                    </div>
                    <div class="card-body">
                        @foreach($summary['payment_modes'] as $mode => $count)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>{{ $mode }}</span>
                            <span class="badge bg-primary">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            @if($summary['categories']->count() > 0)
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Payment Categories Breakdown</h6>
                    </div>
                    <div class="card-body">
                        @foreach($summary['categories'] as $category => $count)
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>{{ $category }}</span>
                            <span class="badge bg-success">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
