<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Payment Proofs Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #222; margin: 18px; }
        .letterhead { text-align: center; border-bottom: 2px solid #1a365d; padding-bottom: 10px; margin-bottom: 12px; }
        .letterhead h1 { margin: 0; font-size: 16px; color: #1a365d; text-transform: uppercase; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary td { border: 1px solid #cbd5e0; padding: 6px; text-align: center; }
        .summary .val { font-size: 12px; font-weight: bold; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #cbd5e0; padding: 3px 2px; }
        table.report th { background: #edf2f7; }
        .amount { text-align: right; }
        .footer { margin-top: 16px; font-size: 8px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{ $setting->school_name ?? 'School' }}</h1>
        <p><strong>Bank Payment Proof Submissions Report</strong></p>
        <p>Generated: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table class="summary">
        <tr>
            <td><div class="val">{{ $summary['total'] }}</div><div>Total</div></td>
            <td><div class="val">{{ $summary['verified'] }}</div><div>Verified</div></td>
            <td><div class="val">{{ $summary['review'] }}</div><div>Pending</div></td>
            <td><div class="val">{{ $summary['rejected'] }}</div><div>Rejected</div></td>
            <td><div class="val">Tsh {{ format_tzs($summary['amount_verified']) }}</div><div>Verified amount</div></td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th>#</th>
                <th>Submitted</th>
                <th>Student</th>
                <th>Parent</th>
                <th>Bank</th>
                <th class="amount">Amount</th>
                <th>Reference</th>
                <th>Status</th>
                <th>Receipt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $submission)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $submission->created_at?->format('d/m/Y') }}</td>
                <td>{{ $submission->student?->name ?? '—' }}</td>
                <td>{{ $submission->parentUser?->name ?? '—' }}</td>
                <td>{{ $submission->bankLabel() }}</td>
                <td class="amount">Tsh {{ format_tzs($submission->extracted_amount ?? 0) }}</td>
                <td>{{ $submission->extracted_reference ?? '—' }}</td>
                <td>{{ $submission->statusLabel() }}</td>
                <td>{{ $submission->receipt?->receipt_no ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Generated from parent bank payment proof submissions in the system.</div>
</body>
</html>
