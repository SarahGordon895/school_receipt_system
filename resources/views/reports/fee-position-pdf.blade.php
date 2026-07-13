<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>School Fee Position Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #222; margin: 20px; }
        .letterhead { text-align: center; border-bottom: 2px solid #1a365d; padding-bottom: 10px; margin-bottom: 14px; }
        .letterhead h1 { margin: 0; font-size: 17px; color: #1a365d; text-transform: uppercase; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary td { border: 1px solid #cbd5e0; padding: 7px; text-align: center; width: 25%; }
        .summary .val { font-size: 13px; font-weight: bold; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #cbd5e0; padding: 4px 3px; }
        table.report th { background: #edf2f7; font-size: 9px; }
        .amount { text-align: right; }
        .footer { margin-top: 20px; font-size: 8px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{ $setting->school_name ?? 'School' }}</h1>
        <p><strong>Official School Fee Position Report</strong></p>
        <p>Generated: {{ now()->format('d/m/Y H:i') }}</p>
        @if($classFilter)<p>Class: {{ $classFilter }}</p>@endif
        @if($statusFilter)<p>Status: {{ ucfirst($statusFilter) }}</p>@endif
    </div>

    <table class="summary">
        <tr>
            <td><div class="val">{{ $summary['students_count'] }}</div><div>Students</div></td>
            <td><div class="val">Tsh {{ format_tzs($summary['total_expected']) }}</div><div>Expected</div></td>
            <td><div class="val">Tsh {{ format_tzs($summary['total_collected']) }}</div><div>Collected</div></td>
            <td><div class="val">Tsh {{ format_tzs($summary['total_outstanding']) }}</div><div>Outstanding</div></td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th>#</th>
                <th>Adm</th>
                <th>Student</th>
                <th>Class</th>
                <th class="amount">Expected</th>
                <th class="amount">Paid</th>
                <th class="amount">Balance</th>
                <th>Rcpts</th>
                <th>Last receipt</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $row)
            @php $student = $row['student']; @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->admission_no ?? '—' }}</td>
                <td>{{ $student->name }}</td>
                <td>{{ $student->class_name ?? '—' }}</td>
                <td class="amount">{{ format_tzs($row['expected']) }}</td>
                <td class="amount">{{ format_tzs($row['paid']) }}</td>
                <td class="amount">{{ format_tzs($row['balance']) }}</td>
                <td>{{ $row['receipt_count'] }}</td>
                <td>{{ $row['last_receipt_no'] ?? '—' }}</td>
                <td>{{ $row['status'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align:right;">TOTALS</th>
                <th class="amount">{{ format_tzs($summary['total_expected']) }}</th>
                <th class="amount">{{ format_tzs($summary['total_collected']) }}</th>
                <th class="amount">{{ format_tzs($summary['total_outstanding']) }}</th>
                <th colspan="3"></th>
            </tr>
        </tfoot>
    </table>

    <div class="footer">Figures from assigned fee structures and recorded receipts in FTRS.</div>
</body>
</html>
