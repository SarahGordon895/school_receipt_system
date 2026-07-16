<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ strtoupper($bank) }} Payment Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; color: #111; margin: 40px; }
        h1 { font-size: 18pt; margin-bottom: 4px; }
        .muted { color: #444; font-size: 10pt; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 8px 4px; border-bottom: 1px solid #ddd; vertical-align: top; }
        td.label { width: 45%; font-weight: bold; }
        .footer { margin-top: 28px; font-size: 9pt; color: #555; }
    </style>
</head>
<body>
    @if ($bank === 'crdb')
        <h1>CRDB Bank Plc</h1>
        <div class="muted">Transfer Confirmation — Mbonea Secondary School fee payment (demo)</div>
        <p>CRDB Bank Transfer Confirmation</p>
        <table>
            <tr>
                <td class="label">Ref No:</td>
                <td>{{ $reference }}</td>
            </tr>
            <tr>
                <td class="label">Credit Account Number:</td>
                <td>{{ $accountNumber }}</td>
            </tr>
            <tr>
                <td class="label">Beneficiary Name:</td>
                <td>{{ $accountName }}</td>
            </tr>
            <tr>
                <td class="label">Payer:</td>
                <td>{{ $payerName }}</td>
            </tr>
            <tr>
                <td class="label">Student:</td>
                <td>{{ $studentName }} ({{ $admissionNo }})</td>
            </tr>
            <tr>
                <td class="label">Transaction Amount TSH</td>
                <td>{{ number_format($amount) }}</td>
            </tr>
            <tr>
                <td class="label">Date:</td>
                <td>{{ $paymentDate }}</td>
            </tr>
        </table>
    @else
        <h1>NMB Bank Plc</h1>
        <div class="muted">Payment Receipt — Mbonea Secondary School fee payment (demo)</div>
        <p>NMB Bank Payment Receipt</p>
        <table>
            <tr>
                <td class="label">Transaction Reference:</td>
                <td>{{ $reference }}</td>
            </tr>
            <tr>
                <td class="label">Beneficiary Account Number:</td>
                <td>{{ $accountNumber }}</td>
            </tr>
            <tr>
                <td class="label">Beneficiary Name:</td>
                <td>{{ $accountName }}</td>
            </tr>
            <tr>
                <td class="label">Payer:</td>
                <td>{{ $payerName }}</td>
            </tr>
            <tr>
                <td class="label">Student:</td>
                <td>{{ $studentName }} ({{ $admissionNo }})</td>
            </tr>
            <tr>
                <td class="label">Amount:</td>
                <td>TZS {{ number_format($amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Payment Date:</td>
                <td>{{ $paymentDate }}</td>
            </tr>
        </table>
    @endif

    <p class="footer">
        This is a demonstration receipt for Fee Tracking &amp; Receipt System (FTRS) bank-upload testing.
        @if ($bank === 'crdb')
            Parser-readable fields above match school CRDB verification rules.
        @else
            Parser-readable fields above match school NMB verification rules.
        @endif
    </p>
</body>
</html>
