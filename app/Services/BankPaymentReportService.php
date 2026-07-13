<?php

namespace App\Services;

use App\Models\BankPaymentSubmission;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BankPaymentReportService
{
    /**
     * Bank payment proof submissions recorded in the system.
     *
     * @return array{rows: Collection<int, BankPaymentSubmission>, summary: array<string, int|float>, setting: Setting}
     */
    public function build(Request $request): array
    {
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        if ($dateFrom && $dateTo && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $query = BankPaymentSubmission::query()
            ->with([
                'student:id,name,admission_no,class_name',
                'parentUser:id,name,phone,email',
                'receipt:id,receipt_no,amount,payment_date',
                'reviewedBy:id,name',
            ])
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('bank'), fn ($q) => $q->where('bank', $request->bank));

        $rows = (clone $query)->latest()->get();

        $summary = [
            'total' => $rows->count(),
            'verified' => $rows->where('status', 'verified')->count(),
            'pending' => $rows->where('status', 'pending')->count(),
            'review' => $rows->whereIn('status', ['review', 'pending'])->count(),
            'rejected' => $rows->where('status', 'rejected')->count(),
            'amount_verified' => (int) $rows->where('status', 'verified')->sum('extracted_amount'),
        ];

        return [
            'rows' => $rows,
            'summary' => $summary,
            'setting' => Setting::current() ?? new Setting(['school_name' => config('app.name')]),
        ];
    }
}
