<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $receipt->receipt_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .muted { color: #666; font-size: 11px; }
    </style>
</head>
<body>
    @php $s = \App\Models\Setting::first(); @endphp
    <h1>{{ $s->school_name ?? 'School' }}</h1>
    <div class="muted">{{ $s->address ?? '' }}</div>
    <div class="muted">{{ $s->contact_phone ?? '' }} @if(!empty($s->contact_email)) • {{ $s->contact_email }} @endif</div>

    <h2 style="margin-top:16px;font-size:14px;">Fee receipt</h2>
    <table>
        <tr><th>Receipt #</th><td>{{ $receipt->receipt_no }}</td></tr>
        <tr><th>Student</th><td>{{ $receipt->student_name }}</td></tr>
        <tr><th>Class</th><td>{{ $receipt->class_name ?? '—' }}</td></tr>
        <tr><th>Payment date</th><td>{{ $receipt->payment_date }}</td></tr>
        <tr><th>Mode</th><td>{{ $receipt->payment_mode }}</td></tr>
        <tr><th>Reference</th><td>{{ $receipt->reference ?: '—' }}</td></tr>
    </table>

    @if($receipt->relationLoaded('paymentCategories') && $receipt->paymentCategories->isNotEmpty())
        <table>
            <thead>
                <tr><th>Category</th><th class="right">Amount (Tsh)</th></tr>
            </thead>
            <tbody>
                @foreach($receipt->paymentCategories as $c)
                    <tr>
                        <td>{{ $c->name }}</td>
                        <td class="right">{{ number_format($c->pivot->amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p style="margin-top:16px;"><strong>Total: Tsh {{ number_format($receipt->amount) }}</strong></p>
    @if($receipt->note)
        <p class="muted">Note: {{ $receipt->note }}</p>
    @endif
</body>
</html>
