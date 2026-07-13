<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SMS & Email History Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #222; margin: 18px; }
        .letterhead { text-align: center; border-bottom: 2px solid #1a365d; padding-bottom: 10px; margin-bottom: 12px; }
        .letterhead h1 { margin: 0; font-size: 16px; color: #1a365d; text-transform: uppercase; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .summary td { border: 1px solid #cbd5e0; padding: 6px; text-align: center; }
        .summary .val { font-size: 12px; font-weight: bold; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #cbd5e0; padding: 3px 2px; vertical-align: top; }
        table.report th { background: #edf2f7; }
        .footer { margin-top: 16px; font-size: 8px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{ $setting->school_name ?? 'School' }}</h1>
        <p><strong>SMS &amp; Email Message History Report</strong></p>
        <p>Generated: {{ now()->format('d/m/Y H:i') }}</p>
        @if($request->date_from || $request->date_to)
            <p>Period: {{ $request->date_from ?? '…' }} — {{ $request->date_to ?? '…' }}</p>
        @endif
    </div>

    <table class="summary">
        <tr>
            <td><div class="val">{{ $summary['total'] }}</div><div>Total</div></td>
            <td><div class="val">{{ $summary['sent'] }}</div><div>Sent</div></td>
            <td><div class="val">{{ $summary['failed'] }}</div><div>Failed</div></td>
            <td><div class="val">{{ $summary['skipped'] }}</div><div>Skipped</div></td>
            <td><div class="val">{{ $summary['sms'] }}</div><div>SMS</div></td>
            <td><div class="val">{{ $summary['email'] }}</div><div>Email</div></td>
        </tr>
    </table>

    <table class="report">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Student</th>
                <th>Channel</th>
                <th>Event</th>
                <th>Status</th>
                <th>Message</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $index => $log)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $log->sent_on?->format('d/m/Y') ?? '—' }}</td>
                <td>{{ $log->student?->name ?? '—' }}</td>
                <td>{{ strtoupper($log->channel) }}</td>
                <td>{{ $log->event_type ?? '—' }}</td>
                <td>{{ $log->statusLabel() }}</td>
                <td>{{ $log->message }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Generated from notification logs — manual sends and automated fee reminders.</div>
</body>
</html>
