<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fee Clearance Certificate</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1a1a1a; margin: 36px; }
        .letterhead { text-align: center; border-bottom: 3px double #1a365d; padding-bottom: 16px; margin-bottom: 24px; }
        .letterhead h1 { margin: 0; font-size: 22px; color: #1a365d; text-transform: uppercase; letter-spacing: 1px; }
        .letterhead p { margin: 4px 0; color: #444; }
        .title { text-align: center; margin: 28px 0; }
        .title h2 { font-size: 18px; color: #1a365d; margin: 0 0 6px; text-transform: uppercase; }
        .title .ref { font-size: 11px; color: #666; }
        .body-text { line-height: 1.8; margin: 24px 0; text-align: justify; }
        .highlight { font-weight: bold; color: #1a365d; }
        table.details { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.details th, table.details td { border: 1px solid #cbd5e0; padding: 8px 10px; }
        table.details th { background: #edf2f7; text-align: left; width: 38%; }
        .stamp-box { border: 2px solid #1a365d; border-radius: 4px; padding: 14px; text-align: center; margin-top: 32px; }
        .stamp-box .paid { font-size: 20px; font-weight: bold; color: #276749; letter-spacing: 2px; }
        .signatures { margin-top: 48px; width: 100%; }
        .signatures td { width: 50%; padding-top: 40px; vertical-align: top; }
        .signatures .line { border-top: 1px solid #333; width: 75%; padding-top: 6px; font-size: 11px; }
        .footer { margin-top: 36px; font-size: 9px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{ $setting->school_name ?? 'School' }}</h1>
        @if($setting->reg_number)<p>Reg. No: {{ $setting->reg_number }}</p>@endif
        @if($setting->address)<p>{{ $setting->address }}</p>@endif
        @if($setting->contact_phone || $setting->contact_email)
            <p>
                @if($setting->contact_phone) Tel: {{ $setting->contact_phone }} @endif
                @if($setting->contact_email) • {{ $setting->contact_email }} @endif
            </p>
        @endif
    </div>

    <div class="title">
        <h2>Certificate of Fee Clearance</h2>
        <div class="ref">Reference: {{ $clearanceRef }}</div>
        <div class="ref">Issued: {{ now()->format('d/m/Y') }}</div>
    </div>

    <div class="body-text">
        This is to certify that <span class="highlight">{{ $student->name }}</span>
        @if($student->admission_no)(Admission No: <span class="highlight">{{ $student->admission_no }}</span>)@endif
        @if($student->class_name), Class <span class="highlight">{{ $student->class_name }}</span>@endif,
        has <strong>fully paid</strong> all assigned school fees for the current term/period.
        The student has no outstanding fee balance on the school records as of this date.
    </div>

    <table class="details">
        <tr>
            <th>Parent / Guardian</th>
            <td>{{ $student->parent_name ?? ($student->parentUser?->name ?? 'N/A') }}</td>
        </tr>
        <tr>
            <th>Total fees expected</th>
            <td>Tsh {{ format_tzs($student->expected_amount) }}</td>
        </tr>
        <tr>
            <th>Total amount paid</th>
            <td>Tsh {{ format_tzs($student->paid_amount) }}</td>
        </tr>
        <tr>
            <th>Outstanding balance</th>
            <td><strong style="color:#276749;">Tsh 0 — Fully paid</strong></td>
        </tr>
        @if($lastPayment)
        <tr>
            <th>Last payment</th>
            <td>{{ \Carbon\Carbon::parse($lastPayment->payment_date)->format('d/m/Y') }} — Receipt {{ $lastPayment->receipt_no }} (Tsh {{ format_tzs($lastPayment->amount) }})</td>
        </tr>
        @endif
        @if($student->fee_due_date)
        <tr>
            <th>Fee due date (on record)</th>
            <td>{{ $student->fee_due_date->format('d/m/Y') }}</td>
        </tr>
        @endif
    </table>

    @if($student->feeStructures->isNotEmpty())
    <p style="font-weight:bold; margin-bottom:6px;">Fee structures cleared:</p>
    <table class="details">
        <thead>
            <tr>
                <th>Structure</th>
                <th>Class</th>
                <th style="text-align:right;">Amount (Tsh)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($student->feeStructures as $structure)
            <tr>
                <td>{{ $structure->name }}</td>
                <td>{{ $structure->class_name ?? 'All' }}</td>
                <td style="text-align:right;">{{ format_tzs($structure->amount) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="stamp-box">
        <div class="paid">✓ FEES CLEARED</div>
        <div style="margin-top:6px; font-size:11px;">Balance: Tsh 0</div>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="line">Bursar / Accounts Officer</div>
            </td>
            <td style="text-align:right;">
                <div class="line" style="margin-left:auto;">Head Teacher</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        This certificate is generated by {{ $setting->school_name ?? 'School' }} Fee Tracking &amp; Receipt System.
        Verify authenticity using reference {{ $clearanceRef }}.
    </div>
</body>
</html>
