@extends('layouts.app')

@section('title', 'Fee Collection Report')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h5 class="card-title text-primary">Students Who Paid</h5>
                        <h3 class="mb-0">{{ $summary['students_count'] }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h5 class="card-title text-success">Total Collected (Tsh)</h5>
                        <h3 class="mb-0">{{ format_tzs($summary['total_collected']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h5 class="card-title text-info">Lowest Paid (Tsh)</h5>
                        <h3 class="mb-0">{{ format_tzs($summary['lowest_paid']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h5 class="card-title text-warning">Highest Paid (Tsh)</h5>
                        <h3 class="mb-0">{{ format_tzs($summary['highest_paid']) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted small me-2">
                    Period:
                    @if($request->date_range === 'custom')
                        {{ $request->start_date }} to {{ $request->end_date }}
                    @else
                        {{ ucfirst(str_replace('_', ' ', $request->date_range)) }}
                    @endif
                    • Sorted lowest to highest amount paid
                </span>

                <form method="POST" action="{{ route('reports.export.excel') }}" class="d-inline-block">
                    @csrf
                    @foreach($request->all() as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <x-icon-btn type="submit" icon="bi-file-earmark-excel" label="Export Excel" variant="success" :iconOnly="false" />
                </form>

                <form method="POST" action="{{ route('reports.export.pdf') }}" class="d-inline-block">
                    @csrf
                    @foreach($request->all() as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <x-icon-btn type="submit" icon="bi-file-earmark-pdf" label="Export PDF" variant="danger" :iconOnly="false" />
                </form>

                <x-icon-btn :href="route('reports.index')" icon="bi-arrow-left" label="Back to filters" variant="outline-secondary" :iconOnly="false" />
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-table me-2"></i>Students Who Paid School Fees ({{ $rows->count() }})
                </h5>
            </div>
            <div class="card-body">
                @if($rows->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>S/N</th>
                                    <th>Admission No</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Parent</th>
                                    <th class="text-end">Expected (Tsh)</th>
                                    <th class="text-end">Paid in Period (Tsh)</th>
                                    <th class="text-end">Total Paid (Tsh)</th>
                                    <th class="text-end">Balance (Tsh)</th>
                                    <th>Last Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $index => $row)
                                @php $student = $row['student']; @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->admission_no ?? 'N/A' }}</td>
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $student->class_name ?? 'N/A' }}</td>
                                    <td>
                                        <div>{{ $student->parent_name ?? 'N/A' }}</div>
                                        <small class="text-muted">{{ $student->resolveParentPhone() ?? 'No phone' }}</small>
                                    </td>
                                    <td class="text-end">{{ format_tzs($row['expected']) }}</td>
                                    <td class="text-end fw-semibold text-success">{{ format_tzs($row['period_paid']) }}</td>
                                    <td class="text-end">{{ format_tzs($row['total_paid']) }}</td>
                                    <td class="text-end">{{ format_tzs($row['balance']) }}</td>
                                    <td>{{ $row['last_payment_date'] ? \Carbon\Carbon::parse($row['last_payment_date'])->format('d/m/Y') : 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="6" class="text-end">TOTAL COLLECTED IN PERIOD:</th>
                                    <th class="text-end fw-bold text-primary">{{ format_tzs($summary['total_collected']) }}</th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-2">No students with fee payments found for the selected criteria.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
