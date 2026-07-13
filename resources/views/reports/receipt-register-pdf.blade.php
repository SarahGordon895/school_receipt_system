<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt Register</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; margin: 20px; }
        .letterhead { text-align: center; border-bottom: 2px solid #1a365d; padding-bottom: 10px; margin-bottom: 14px; }
        .letterhead h1 { margin: 0; font-size: 17px; color: #1a365d; }
        .summary td { border: 1px solid #ccc; padding: 6px; text-align: center; }
        table.report { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.report th, table.report td { border: 1px solid #ccc; padding: 4px 3px; }
        table.report th { background: #edf2f7; }
        .amount { text-align: right; }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{ $setting->school_name ?? 'School' }}</h1>
        <p><strong>Official Receipt Register</strong></p>
        <p>Period: {{ $request->date_range === 'custom' ? $request->start_date.' to '.$request->end_date : ucfirst(str_replace('_', ' ', $request->date_range)) }}</p>
        <p>Generated: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table class="summary" style="width:100%; border-collapse:collapse;">
        <tr>
            <td><strong>{{ $summary['receipt_count'] }}</strong><br>Receipts</td>
            <td><strong>Tsh {{ format_tzs($summary['total_collected']) }}</strong><br>Collected</td>
            <td><strong>{{ $summary['students_count'] }}</strong><br>Students</td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th>#</th>
                <th>Receipt</th>
                <th>Date</th>
                <th>Student</th>
                <th>Class</th>
                <th class="amount">Amount</th>
                <th>Mode</th>
                <th>Categories</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $receipt)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $receipt->receipt_no }}</td>
                <td>{{ $receipt->payment_date?->format('d/m/Y') }}</td>
                <td>{{ $receipt->student_name ?? $receipt->student?->name }}</td>
                <td>{{ $receipt->class_name ?? '—' }}</td>
                <td class="amount">{{ format_tzs($receipt->amount) }}</td>
                <td>{{ $receipt->payment_mode }}</td>
                <td>{{ $receipt->paymentCategories->pluck('name')->join(', ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" style="text-align:right;">TOTAL</th>
                <th class="amount">{{ format_tzs($summary['total_collected']) }}</th>
                <th colspan="2"></th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
