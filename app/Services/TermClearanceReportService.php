<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Student;
use Illuminate\Support\Collection;

class TermClearanceReportService
{
    /** @return array{rows: Collection<int, array<string, mixed>>, summary: array<string, int>, setting: Setting, classes: Collection<int, string>} */
    public function build(?string $classFilter = null): array
    {
        $students = Student::query()
            ->withSum('receipts', 'amount')
            ->with(['feeStructures', 'parentUser', 'primaryParentLink'])
            ->with(['receipts' => fn ($q) => $q->latest('payment_date')->limit(1)])
            ->when(filled($classFilter), fn ($q) => $q->where('class_name', 'like', '%'.$classFilter.'%'))
            ->orderBy('class_name')
            ->orderBy('name')
            ->get()
            ->filter(fn (Student $student) => $student->isFullyPaid())
            ->values();

        $rows = $students->map(function (Student $student) {
            $lastReceipt = $student->receipts->first();

            return [
                'student' => $student,
                'expected' => $student->expected_amount,
                'paid' => $student->paid_amount,
                'last_payment_date' => $lastReceipt?->payment_date,
                'last_receipt_no' => $lastReceipt?->receipt_no,
                'clearance_ref' => $this->clearanceReference($student),
            ];
        });

        $classes = Student::query()
            ->whereNotNull('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return [
            'rows' => $rows,
            'summary' => [
                'cleared_count' => $rows->count(),
                'total_collected' => $rows->sum('paid'),
                'classes_count' => $rows->pluck('student.class_name')->filter()->unique()->count(),
            ],
            'setting' => Setting::query()->first() ?? new Setting(['school_name' => config('app.name')]),
            'classes' => $classes,
        ];
    }

    public function clearanceReference(Student $student): string
    {
        $id = $student->admission_no ?: ('STU-'.$student->id);

        return 'CLR-'.preg_replace('/[^A-Za-z0-9\-]/', '', $id).'-'.now()->format('Ymd');
    }
}
