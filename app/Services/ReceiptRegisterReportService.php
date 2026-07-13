<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReceiptRegisterReportService
{
    public function __construct(private FeeCollectionReportService $feeCollectionReportService)
    {
    }

    /**
     * Official receipt register for payments recorded in the system.
     *
     * @return array{rows: Collection<int, Receipt>, summary: array<string, int|float>, setting: Setting}
     */
    public function build(Request $request): array
    {
        $query = Receipt::query()
            ->with([
                'student:id,name,admission_no,class_name,parent_name,parent_phone',
                'paymentCategories:id,name',
                'user:id,name',
            ])
            ->whereNotNull('student_id');

        $this->applyDateRange($query, $request);

        $query->when($request->class_name, fn ($q) => $q->where('class_name', 'like', '%'.$request->class_name.'%'))
            ->when($request->payment_mode, fn ($q) => $q->where('payment_mode', $request->payment_mode))
            ->when($request->payment_category_id, fn ($q) => $q->whereHas(
                'paymentCategories',
                fn ($pc) => $pc->where('payment_categories.id', $request->payment_category_id)
            ));

        $rows = (clone $query)
            ->orderBy('payment_date')
            ->orderBy('receipt_no')
            ->get();

        $summary = [
            'receipt_count' => $rows->count(),
            'total_collected' => (int) $rows->sum('amount'),
            'cash_total' => (int) $rows->where('payment_mode', 'Cash')->sum('amount'),
            'bank_total' => (int) $rows->where('payment_mode', 'Bank')->sum('amount'),
            'mobile_total' => (int) $rows->where('payment_mode', 'Mobile Money')->sum('amount'),
            'students_count' => $rows->pluck('student_id')->unique()->count(),
        ];

        return [
            'rows' => $rows,
            'summary' => $summary,
            'setting' => Setting::current() ?? new Setting(['school_name' => config('app.name')]),
        ];
    }

    private function applyDateRange($query, Request $request): void
    {
        if ($request->date_range === 'custom') {
            $query->whereDate('payment_date', '>=', $request->start_date)
                ->whereDate('payment_date', '<=', $request->end_date);

            return;
        }

        [$start, $end] = $this->feeCollectionReportService->getDateRange((string) $request->date_range);
        $query->whereDate('payment_date', '>=', $start)
            ->whereDate('payment_date', '<=', $end);
    }
}
