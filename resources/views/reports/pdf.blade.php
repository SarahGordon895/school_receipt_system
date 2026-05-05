<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipts Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .summary {
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-item {
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .summary-item h3 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: #333;
        }
        .summary-item p {
            margin: 0;
            color: #666;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .breakdown {
            margin-top: 30px;
        }
        .breakdown-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .breakdown-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        .breakdown-item h4 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .breakdown-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Receipts Report</h1>
        <p><strong>Generated on:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        <p><strong>Date Range:</strong> 
            @if($request->date_range === 'custom')
                {{ $request->start_date }} to {{ $request->end_date }}
            @else
                {{ ucfirst(str_replace('_', ' ', $request->date_range)) }}
            @endif
        </p>
        @if($request->class_name || $request->payment_category_id || $request->payment_mode)
            <p><strong>Filters Applied:</strong>
                @if($request->class_name) Class: {{ $request->class_name }} @endif
                @if($request->payment_category_id) Category: {{ App\Models\PaymentCategory::find($request->payment_category_id)?->name }} @endif
                @if($request->payment_mode) Mode: {{ $request->payment_mode }} @endif
            </p>
        @endif
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item">
                <h3>{{ $summary['total_receipts'] }}</h3>
                <p>Total Receipts</p>
            </div>
            <div class="summary-item">
                <h3>{{ number_format($summary['total_amount'], 2) }}</h3>
                <p>Total Amount</p>
            </div>
            <div class="summary-item">
                <h3>{{ number_format($summary['average_amount'], 2) }}</h3>
                <p>Average Amount</p>
            </div>
            <div class="summary-item">
                <h3>{{ $receipts->count() }}</h3>
                <p>Records Found</p>
            </div>
        </div>
    </div>

    @if($receipts->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Receipt No</th>
                <th>Student Name</th>
                <th>Class</th>
                <th>Payment Category</th>
                <th class="text-right">Amount</th>
                <th>Payment Date</th>
                <th>Payment Mode</th>
                <th>Reference</th>
                <th>Created By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receipts as $receipt)
            <tr>
                <td>{{ $receipt->receipt_no }}</td>
                <td>{{ $receipt->student_name }}</td>
                <td>{{ $receipt->class_name ?? 'N/A' }}</td>
                <td>{{ $receipt->paymentCategories->pluck('name')->implode(', ') ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($receipt->amount, 2) }}</td>
                <td>{{ \Carbon\Carbon::parse($receipt->payment_date)->format('d/m/Y') }}</td>
                <td>{{ $receipt->payment_mode }}</td>
                <td>{{ $receipt->reference ?? 'N/A' }}</td>
                <td>{{ $receipt->user?->name ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right" style="background-color: #f2f2f2; font-weight: bold;">TOTAL AMOUNT:</th>
                <th class="text-right" style="background-color: #f2f2f2; font-weight: bold; font-size: 14px; color: #2c5282;">
                    {{ number_format($receipts->sum('amount'), 2) }}
                </th>
                <th colspan="4" style="background-color: #f2f2f2;"></th>
            </tr>
        </tfoot>
    </table>
    @else
    <p class="text-center">No receipts found matching the selected criteria.</p>
    @endif

    @if($summary['payment_modes']->count() > 0 || $summary['categories']->count() > 0)
    <div class="breakdown">
        <h3>Statistical Breakdown</h3>
        <div class="breakdown-grid">
            @if($summary['payment_modes']->count() > 0)
            <div class="breakdown-item">
                <h4>Payment Modes</h4>
                @foreach($summary['payment_modes'] as $mode => $count)
                <div class="breakdown-row">
                    <span>{{ $mode }}</span>
                    <span>{{ $count }}</span>
                </div>
                @endforeach
            </div>
            @endif

            @if($summary['categories']->count() > 0)
            <div class="breakdown-item">
                <h4>Payment Categories</h4>
                @foreach($summary['categories'] as $category => $count)
                <div class="breakdown-row">
                    <span>{{ $category }}</span>
                    <span>{{ $count }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Report generated by School Receipts Management System</p>
        <p>Page 1 of 1</p>
    </div>
</body>
</html>
