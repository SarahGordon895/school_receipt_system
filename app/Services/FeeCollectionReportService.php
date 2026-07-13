<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\Setting;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FeeCollectionReportService
{
    /** @return array{rows: Collection<int, array<string, mixed>>, summary: array<string, int|float>, setting: Setting} */
    public function build(Request $request): array
    {
        $receiptQuery = Receipt::query()->whereNotNull('student_id');

        $this->applyDateRange($receiptQuery, $request);
        $this->applyReceiptFilters($receiptQuery, $request);

        $periodTotals = (clone $receiptQuery)
            ->selectRaw('student_id, SUM(amount) as period_paid, COUNT(*) as receipt_count, MAX(payment_date) as last_payment_date')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $studentIds = $periodTotals->keys();

        $students = Student::query()
            ->whereIn('id', $studentIds)
            ->with(['feeStructures'])
            ->withSum('receipts', 'amount')
            ->orderBy('name')
            ->get();

        $rows = $students->map(function (Student $student) use ($periodTotals) {
            $period = $periodTotals->get($student->id);

            return [
                'student' => $student,
                'period_paid' => (int) ($period->period_paid ?? 0),
                'receipt_count' => (int) ($period->receipt_count ?? 0),
                'last_payment_date' => $period->last_payment_date ?? null,
                'total_paid' => $student->paid_amount,
                'expected' => $student->expected_amount,
                'balance' => $student->balance,
            ];
        })
            ->when($request->filled('min_amount'), fn (Collection $c) => $c->filter(
                fn (array $row) => $row['period_paid'] >= (int) $request->min_amount
            ))
            ->when($request->filled('max_amount'), fn (Collection $c) => $c->filter(
                fn (array $row) => $row['period_paid'] <= (int) $request->max_amount
            ))
            ->sortBy('period_paid')
            ->values();

        $summary = [
            'students_count' => $rows->count(),
            'total_collected' => $rows->sum('period_paid'),
            'lowest_paid' => $rows->min('period_paid') ?? 0,
            'highest_paid' => $rows->max('period_paid') ?? 0,
            'total_receipts' => $rows->sum('receipt_count'),
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

        [$start, $end] = $this->getDateRange((string) $request->date_range);
        $query->whereDate('payment_date', '>=', $start)
            ->whereDate('payment_date', '<=', $end);
    }

    private function applyReceiptFilters($query, Request $request): void
    {
        $query->when($request->class_name, fn ($q) => $q->where('class_name', 'like', '%'.$request->class_name.'%'))
            ->when($request->payment_category_id, fn ($q) => $q->whereHas(
                'paymentCategories',
                fn ($pc) => $pc->where('payment_categories.id', $request->payment_category_id)
            ))
            ->when($request->payment_mode, fn ($q) => $q->where('payment_mode', $request->payment_mode));
    }

    /** @return array{0: string, 1: string} */
    public function getDateRange(string $range): array
    {
        $now = now();

        return match ($range) {
            'today' => [$now->toDateString(), $now->toDateString()],
            'yesterday' => [$now->copy()->subDay()->toDateString(), $now->copy()->subDay()->toDateString()],
            'this_week' => [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()],
            'last_week' => [$now->copy()->subWeek()->startOfWeek()->toDateString(), $now->copy()->subWeek()->endOfWeek()->toDateString()],
            'this_month' => [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()],
            'last_month' => [$now->copy()->subMonth()->startOfMonth()->toDateString(), $now->copy()->subMonth()->endOfMonth()->toDateString()],
            'this_year' => [$now->copy()->startOfYear()->toDateString(), $now->copy()->endOfYear()->toDateString()],
            'last_year' => [$now->copy()->subYear()->startOfYear()->toDateString(), $now->copy()->subYear()->endOfYear()->toDateString()],
            default => [$now->toDateString(), $now->toDateString()],
        };
    }
}
