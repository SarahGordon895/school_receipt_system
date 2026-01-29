@extends('layouts.app')
@section('title', 'Receipt ' . $receipt->receipt_no)

@section('actions')
    <a href="{{ route('receipts.create') }}" class="btn btn-primary">
        <i class="bi bi-receipt-cutoff me-1"></i> Generate Receipt
    </a>
@endsection

@section('content')
    {{-- Standard card preview --}}
    <div class="card mb-4 fs-6">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Receipt Preview</span>
            <div class="d-flex gap-2">
                <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <button class="btn btn-dark btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body">
            @php $s = \App\Models\Setting::first(); @endphp
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded h-100">
                        <div class="fw-semibold">{{ $s->school_name ?? 'School' }}</div>
                        <div class="small">{{ $s->address }}</div>
                        <div class="small">{{ $s->contact_phone }} • {{ $s->contact_email }}</div>
                        <div class="small">Reg: {{ $s->reg_number }}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 border rounded h-100">
                        <div class="row">
                            <div class="col-6 small text-muted">Receipt #</div>
                            <div class="col-6 text-end fw-semibold">{{ $receipt->receipt_no }}</div>

                            <div class="col-6 small text-muted mt-2">Payment Date</div>
                            <div class="col-6 text-end">
                                {{ \Illuminate\Support\Carbon::parse($receipt->payment_date)->toDateString() }}</div>

                            <div class="col-6 small text-muted mt-2">Receipt Generated</div>
                            <div class="col-6 text-end">
                                {{ \Illuminate\Support\Carbon::parse($receipt->created_at)->toDateString() }}</div>

                            <div class="col-6 small text-muted mt-2">Mode</div>
                            <div class="col-6 text-end">{{ $receipt->payment_mode }}</div>

                            @if ($receipt->paymentCategories->count() > 0)
                                <div class="col-12 mt-3">
                                    <div class="small text-muted">Payment Breakdown:</div>
                                    @foreach ($receipt->paymentCategories as $category)
                                        <div class="d-flex justify-content-between mt-1">
                                            <span class="small">{{ $category->name }}</span>
                                            <span class="small fw-semibold">Tsh {{ number_format($category->pivot->amount) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="col-6 small text-muted mt-2">Reference</div>
                            <div class="col-6 text-end">{{ $receipt->reference ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-md-8">
                    <div class="card bg-white border-0 shadow-sm">
                        <div class="card-body">
                            <div class="mb-2">
                                <span class="text-muted small">Student</span>
                                <div class="fs-5 fw-semibold">{{ $receipt->student_name }}</div>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small">Class / Stream</span>
                                <div class="fw-semibold">{{ $receipt->classRoom->name ?? '' }} /
                                    {{ $receipt->stream->name ?? '' }}</div>
                            </div>
                            @if ($receipt->note)
                                <div class="mb-2">
                                    <span class="text-muted small">Note</span>
                                    <div class="fw-normal">{{ $receipt->note }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-muted small">Amount Paid</div>
                            <div class="display-6 fw-bold">Tsh {{ number_format($receipt->amount) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- THERMAL 80mm layout (print-target) --}}
    <div id="thermal-root" class="d-none d-print-block">
        <div class="thermal-receipt">
            <div class="tr-header text-center">
                <div class="p-3 bg-light rounded h-100 text-center">
                    @if ($s?->logo_path)
                        <img src="{{ asset('/public/storage/' . $s->logo_path) }}" alt="Logo"
                            class="img-fluid d-block mx-auto mb-2" style="max-height:60px;width:auto;">
                    @endif

                    <div class="fw-semibold">{{ $s->school_name ?? 'School' }}</div>
                    <div class="small">{{ $s->address }}</div>
                    <div class="small">{{ $s->contact_phone }} {{ $s->contact_email ? ' • ' . $s->contact_email : '' }}
                    </div>
                    <div class="small">Reg: {{ $s->reg_number }}</div>
                </div>

            </div>

            <div class="tr-section">
                <div class="tr-row"><span>Receipt #</span><span class="fw-bold">{{ $receipt->receipt_no }}</span></div>
                <div class="tr-row">
                    <span>Payment Date</span><span>{{ \Illuminate\Support\Carbon::parse($receipt->payment_date)->format('Y-m-d') }}</span>
                </div>
                <div class="tr-row">
                    <span>Receipt Generated</span><span>{{ \Illuminate\Support\Carbon::parse($receipt->created_at)->format('Y-m-d') }}</span>
                </div>
                <div class="tr-row"><span>Mode</span><span>{{ $receipt->payment_mode }}</span></div>
                @if ($receipt->reference)
                    <div class="tr-row"><span>Ref</span><span>{{ $receipt->reference }}</span></div>
                @endif
            </div>

            @if ($receipt->paymentCategories->count() > 0)
                <div class="tr-section">
                    <div class="tr-row"><span>Payment for:</span></div>
                    @foreach ($receipt->paymentCategories as $category)
                        <div class="tr-row">
                            <span class="ms-3">{{ $category->name }}</span>
                            <span>Tsh {{ number_format($category->pivot->amount) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="tr-section">
                <div class="tr-row"><span>Student</span><span>{{ $receipt->student_name }}</span></div>
                <div class="tr-row"><span>Class/Stream</span><span>{{ $receipt->classRoom->name ?? '' }} /
                        {{ $receipt->stream->name ?? '' }}</span></div>
            </div>
            

            <div class="tr-total">
                <div class="tr-row">
                    <span class="fw-bold">TOTAL</span>
                    <span class="fw-bold">Tsh {{ number_format($receipt->amount) }}</span>
                </div>
            </div>

            @if ($receipt->note)
                <div class="tr-note">Note: {{ $receipt->note }}</div>
            @endif

            <div class="tr-footer text-center">
                <div>My Talent My Future</div>

            </div>
        </div>
    </div>
@endsection

@push('head')
    <style>
        /* Thermal 80mm */
        @media print {
            @page {
                size: 80mm auto;
                margin: 3mm;
            }

            body {
                background: #fff;
            }

            nav,
            .sidebar,
            .content-wrap>.container-fluid>h4,
            .card,
            .btn,
            .alert {
                display: none !important;
            }

            #thermal-root {
                display: block !important;
            }
        }

        .thermal-receipt {
            width: 72mm;
            margin: 0 auto;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 14.5px;
            color: #111;
        }

        .tr-header {
            margin-bottom: 6px;
        }

        .tr-title {
            font-weight: 700;
            font-size: 17px;
        }

        .tr-meta {
            color: #555;
        }

        .tr-section {
            border-top: 1px dashed #999;
            padding-top: 6px;
            margin-top: 6px;
        }

        .tr-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .tr-total {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 0;
            margin: 6px 0;
        }

        .tr-total .tr-row span {
            font-size: 15px;
        }

        .tr-note {
            margin-top: 6px;
            white-space: pre-wrap;
        }

        .tr-footer {
            border-top: 1px dashed #999;
            margin-top: 6px;
            padding-top: 6px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Auto-print when arriving with ?print=1
        const params = new URLSearchParams(window.location.search);
        if (params.get('print') === '1') {
            window.addEventListener('load', () => setTimeout(() => window.print(), 250));
        }
    </script>
@endpush
