<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Term Fee Clearance Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; margin: 24px; }
        .letterhead { text-align: center; border-bottom: 2px solid #1a365d; padding-bottom: 12px; margin-bottom: 16px; }
        .letterhead h1 { margin: 0; font-size: 18px; color: #1a365d; text-transform: uppercase; }
        .letterhead p { margin: 3px 0; color: #555; }
        .meta { margin-bottom: 14px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .summary td { border: 1px solid #cbd5e0; padding: 8px; text-align: center; width: 33%; }
        .summary .val { font-size: 14px; font-weight: bold; color: #1a365d; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #cbd5e0; padding: 5px 4px; font-size: 10px; }
        table.report th { background: #edf2f7; }
        .footer { margin-top: 24px; font-size: 9px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{ $setting->school_name ?? 'School' }}</h1>
        <p><strong>Term Fee Clearance Report</strong></p>
        @if($setting->reg_number)<p>Reg. No: {{ $setting->reg_number }}</p>@endif
        <p>Generated: {{ now()->format('d/m/Y H:i') }}</p>
        @if($classFilter)<p>Class filter: {{ $classFilter }}</p>@endif
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="val">{{ $summary['cleared_count'] }}</div>
                <div>Students Cleared</div>
            </td>
            <td>
                <div class="val">Tsh {{ format_tzs($summary['total_collected']) }}</div>
                <div>Total Collected</div>
            </td>
            <td>
                <div class="val">{{ $summary['classes_count'] }}</div>
                <div>Classes</div>
            </td>
        </tr>
    </table>

    @if($rows->count() > 0)
    <table class="report">
        <thead>
            <tr>
                <th>S/N</th>
                <th>Adm No</th>
                <th>Student</th>
                <th>Class</th>
                <th>Parent</th>
                <th>Paid (Tsh)</th>
                <th>Last Payment</th>
                <th>Receipt</th>
                <th>Clearance Ref</th>
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
                <td>{{ $student->parent_name ?? '—' }}</td>
                <td>{{ format_tzs($row['paid']) }}</td>
                <td>{{ $row['last_payment_date'] ? \Carbon\Carbon::parse($row['last_payment_date'])->format('d/m/Y') : '—' }}</td>
                <td>{{ $row['last_receipt_no'] ?? '—' }}</td>
                <td>{{ $row['clearance_ref'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="text-align:center;">No fully paid students found.</p>
    @endif

    <div class="footer">
        Students listed have zero outstanding balance. Official bursar report — {{ $setting->school_name ?? 'School' }} FTRS.
    </div>
</body>
</html>
