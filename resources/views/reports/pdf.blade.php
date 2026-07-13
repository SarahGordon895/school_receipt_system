<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fee Collection Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; margin: 24px; color: #222; }
        .letterhead { text-align: center; border-bottom: 2px solid #1a365d; padding-bottom: 14px; margin-bottom: 18px; }
        .letterhead h1 { margin: 0; font-size: 20px; color: #1a365d; text-transform: uppercase; }
        .letterhead h2 { margin: 6px 0 0; font-size: 14px; font-weight: normal; color: #444; }
        .letterhead p { margin: 3px 0; color: #555; }
        .meta { margin-bottom: 16px; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .meta .label { font-weight: bold; width: 140px; }
        .summary-grid { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .summary-grid td { border: 1px solid #cbd5e0; padding: 10px; text-align: center; width: 25%; }
        .summary-grid .value { font-size: 16px; font-weight: bold; color: #1a365d; }
        .summary-grid .label { font-size: 10px; color: #666; text-transform: uppercase; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #cbd5e0; padding: 6px 5px; }
        table.report th { background: #edf2f7; font-size: 10px; text-transform: uppercase; }
        table.report td.amount { text-align: right; }
        table.report tfoot th { background: #e2e8f0; }
        .footer { margin-top: 28px; font-size: 9px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
        .signature { margin-top: 36px; }
        .signature td { width: 50%; padding-top: 30px; }
        .signature .line { border-top: 1px solid #333; width: 70%; margin-top: 40px; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="letterhead">
        <h1>{{ $setting->school_name ?? 'School' }}</h1>
        <h2>Official Fee Collection Report</h2>
        @if($setting->reg_number)
            <p>Registration No: {{ $setting->reg_number }}</p>
        @endif
        @if($setting->address)
            <p>{{ $setting->address }}</p>
        @endif
        @if($setting->contact_phone || $setting->contact_email)
            <p>
                @if($setting->contact_phone) Tel: {{ $setting->contact_phone }} @endif
                @if($setting->contact_email) • Email: {{ $setting->contact_email }} @endif
            </p>
        @endif
    </div>

    <div class="meta">
        <table>
            <tr>
                <td class="label">Report Period:</td>
                <td>
                    @if($request->date_range === 'custom')
                        {{ \Carbon\Carbon::parse($request->start_date)->format('d/m/Y') }}
                        — {{ \Carbon\Carbon::parse($request->end_date)->format('d/m/Y') }}
                    @else
                        {{ ucfirst(str_replace('_', ' ', $request->date_range)) }}
                    @endif
                </td>
                <td class="label">Generated:</td>
                <td>{{ now()->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Sorting:</td>
                <td>Amount paid (lowest to highest)</td>
                <td class="label">Currency:</td>
                <td>Tanzanian Shillings (whole numbers)</td>
            </tr>
            @if($request->class_name || $request->payment_category_id || $request->payment_mode)
            <tr>
                <td class="label">Filters:</td>
                <td colspan="3">
                    @if($request->class_name) Class: {{ $request->class_name }}; @endif
                    @if($request->payment_category_id) Category: {{ \App\Models\PaymentCategory::find($request->payment_category_id)?->name }}; @endif
                    @if($request->payment_mode) Mode: {{ $request->payment_mode }}; @endif
                </td>
            </tr>
            @endif
        </table>
    </div>

    <table class="summary-grid">
        <tr>
            <td>
                <div class="value">{{ $summary['students_count'] }}</div>
                <div class="label">Students Who Paid</div>
            </td>
            <td>
                <div class="value">Tsh {{ format_tzs($summary['total_collected']) }}</div>
                <div class="label">Total Collected</div>
            </td>
            <td>
                <div class="value">Tsh {{ format_tzs($summary['lowest_paid']) }}</div>
                <div class="label">Lowest Paid</div>
            </td>
            <td>
                <div class="value">Tsh {{ format_tzs($summary['highest_paid']) }}</div>
                <div class="label">Highest Paid</div>
            </td>
        </tr>
    </table>

    @if($rows->count() > 0)
    <table class="report">
        <thead>
            <tr>
                <th>S/N</th>
                <th>Adm No</th>
                <th>Student Name</th>
                <th>Class</th>
                <th>Parent / Phone</th>
                <th class="amount">Expected</th>
                <th class="amount">Paid (Period)</th>
                <th class="amount">Total Paid</th>
                <th class="amount">Balance</th>
                <th>Last Payment</th>
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
                <td>
                    {{ $student->parent_name ?? '—' }}
                    @if($student->resolveParentPhone())
                        <br><small>{{ $student->resolveParentPhone() }}</small>
                    @endif
                </td>
                <td class="amount">{{ format_tzs($row['expected']) }}</td>
                <td class="amount">{{ format_tzs($row['period_paid']) }}</td>
                <td class="amount">{{ format_tzs($row['total_paid']) }}</td>
                <td class="amount">{{ format_tzs($row['balance']) }}</td>
                <td>{{ $row['last_payment_date'] ? \Carbon\Carbon::parse($row['last_payment_date'])->format('d/m/Y') : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" style="text-align:right;">TOTAL COLLECTED IN PERIOD (Tsh)</th>
                <th class="amount">{{ format_tzs($summary['total_collected']) }}</th>
                <th colspan="3"></th>
            </tr>
        </tfoot>
    </table>
    @else
    <p style="text-align:center; padding: 20px;">No students with fee payments found for the selected criteria.</p>
    @endif

    <table class="signature">
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
        <p>This is an official school fee collection report generated by {{ $setting->school_name ?? 'School' }} FTRS.</p>
        <p>Amounts are shown in whole Tanzanian Shillings (Tsh) without decimals.</p>
    </div>
</body>
</html>
