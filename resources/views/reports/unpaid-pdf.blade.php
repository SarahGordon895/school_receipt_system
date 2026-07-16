<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Unpaid Balances Report</title>
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
        <p><strong>Unpaid &amp; Outstanding Balances Report</strong></p>
        <p>Generated: {{ now()->format('d/m/Y H:i') }}</p>
        @if($classFilter)<p>Class: {{ $classFilter }}</p>@endif
    </div>

    <table class="summary">
        <tr>
            <td><div class="val">{{ $summary['students_with_balance'] }}</div><div>Students</div></td>
            <td><div class="val">Tsh {{ format_tzs($summary['total_outstanding']) }}</div><div>Outstanding</div></td>
            <td><div class="val">{{ $summary['overdue_count'] }}</div><div>Overdue</div></td>
            <td><div class="val">{{ $summary['due_in_14_days'] }}</div><div>Due in 14 days</div></td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>Class</th>
                <th>Parent</th>
                <th class="amount">Expected</th>
                <th class="amount">Paid</th>
                <th class="amount">Balance</th>
                <th>Due date</th>
                <th>Stage</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $row)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row['student']->name }}</td>
                <td>{{ $row['student']->class_name ?? '—' }}</td>
                <td>{{ $row['student']->parent_name ?? '—' }}</td>
                <td class="amount">Tsh {{ format_tzs($row['expected']) }}</td>
                <td class="amount">Tsh {{ format_tzs($row['paid']) }}</td>
                <td class="amount">Tsh {{ format_tzs($row['balance']) }}</td>
                <td>{{ $row['student']->resolveFeeDueDate()->format('d/m/Y') }}</td>
                <td>{{ $row['milestone'] }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;">No outstanding balances.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Official bursar report — generated from recorded fee structures and receipts.</div>
</body>
</html>
