<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Student;
use Illuminate\Support\Collection;

class SchoolFeePositionReportService
{
    /**
     * Full school fee position from assigned fee structures and recorded receipts.
     *
     * @return array{rows: Collection<int, array<string, mixed>>, summary: array<string, int|float>, setting: Setting, classes: Collection<int, string>}
     */
    public function build(?string $classFilter = null, ?string $statusFilter = null): array
    {
        $students = Student::query()
            ->with(['feeStructures', 'parentUser', 'primaryParentLink'])
            ->withSum('receipts', 'amount')
            ->withCount('receipts')
            ->with(['receipts' => fn ($q) => $q->latest('payment_date')->latest('id')])
            ->when(filled($classFilter), fn ($q) => $q->where('class_name', 'like', '%'.$classFilter.'%'))
            ->orderBy('class_name')
            ->orderBy('name')
            ->get();

        $rows = $students->map(function (Student $student) {
            $lastReceipt = $student->receipts->first();

            return [
                'student' => $student,
                'expected' => $student->expected_amount,
                'paid' => $student->paid_amount,
                'balance' => $student->balance,
                'receipt_count' => (int) ($student->receipts_count ?? 0),
                'last_payment_date' => $lastReceipt?->payment_date,
                'last_receipt_no' => $lastReceipt?->receipt_no,
                'status' => $student->paymentStatusLabel(),
                'status_badge' => $student->paymentStatusBadge(),
            ];
        });

        $rows = match ($statusFilter) {
            'unpaid' => $rows->filter(fn (array $row) => $row['balance'] > 0)->values(),
            'cleared' => $rows->filter(fn (array $row) => $row['student']->isFullyPaid())->values(),
            'partial' => $rows->filter(fn (array $row) => $row['paid'] > 0 && $row['balance'] > 0)->values(),
            default => $rows->values(),
        };

        $allStudents = $students;

        $summary = [
            'students_count' => $rows->count(),
            'total_expected' => $rows->sum('expected'),
            'total_collected' => $rows->sum('paid'),
            'total_outstanding' => $rows->sum('balance'),
            'fully_paid_count' => $allStudents->filter(fn (Student $s) => $s->isFullyPaid())->count(),
            'unpaid_count' => $allStudents->filter(fn (Student $s) => $s->balance > 0)->count(),
            'receipt_count' => $rows->sum('receipt_count'),
        ];

        $classes = Student::query()
            ->whereNotNull('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return [
            'rows' => $rows,
            'summary' => $summary,
            'setting' => Setting::current() ?? new Setting(['school_name' => config('app.name')]),
            'classes' => $classes,
        ];
    }
}
