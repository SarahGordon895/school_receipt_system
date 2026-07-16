<?php

namespace App\Http\Controllers;

use App\Exports\FeeCollectionReportExport;
use App\Http\Requests\BatchParentReminderRequest;
use App\Models\PaymentCategory;
use App\Models\Student;
use App\Services\BankPaymentReportService;
use App\Services\FeeCollectionReportService;
use App\Services\MessageHistoryReportService;
use App\Services\ParentReminderService;
use App\Services\ReceiptRegisterReportService;
use App\Services\SchoolFeePositionReportService;
use App\Services\TermClearanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(
        private FeeCollectionReportService $reportService,
        private TermClearanceReportService $clearanceService,
        private SchoolFeePositionReportService $feePositionService,
        private ReceiptRegisterReportService $receiptRegisterService,
        private MessageHistoryReportService $messageHistoryService,
        private BankPaymentReportService $bankPaymentReportService,
        private ParentReminderService $parentReminderService
    ) {
    }

    public function index()
    {
        $categories = PaymentCategory::orderBy('name')->get(['id', 'name']);

        $students = Student::query()->with(['feeStructures'])->withSum('receipts', 'amount')->get();
        $withBalance = $students->filter(fn (Student $s) => $s->balance > 0);

        $bursarSummary = [
            'total_students' => $students->count(),
            'expected_fees' => $students->sum(fn (Student $s) => $s->expected_amount),
            'collected' => $students->sum(fn (Student $s) => $s->paid_amount),
            'outstanding' => $withBalance->sum(fn (Student $s) => $s->balance),
            'unpaid_count' => $withBalance->count(),
            'fully_paid' => $students->filter(fn (Student $s) => $s->isFullyPaid())->count(),
            'overdue' => $withBalance->filter(fn (Student $s) => $s->isFeeOverdue())->count(),
        ];

        return view('reports.index', compact('categories', 'bursarSummary'));
    }

    public function generate(Request $request)
    {
        $this->validateReportRequest($request);

        $report = $this->reportService->build($request);

        return view('reports.results', [
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'setting' => $report['setting'],
            'request' => $request,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $this->validateReportRequest($request);

        $filename = 'fee_collection_report_'.now()->format('Y_m_d_His').'.xlsx';

        return Excel::download(new FeeCollectionReportExport($request->all()), $filename);
    }

    public function exportPdf(Request $request)
    {
        $this->validateReportRequest($request);

        $report = $this->reportService->build($request);
        $pdf = Pdf::loadView('reports.pdf', [
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'setting' => $report['setting'],
            'request' => $request,
        ]);
        $filename = 'fee_collection_report_'.now()->format('Y_m_d_His').'.pdf';

        return $pdf->download($filename);
    }

    public function unpaid(Request $request)
    {
        $report = $this->buildUnpaidReport($request);

        return view('reports.unpaid', [
            'students' => $report['students'],
            'summary' => $report['summary'],
            'classes' => $report['classes'],
            'classFilter' => $report['classFilter'],
            'maxBatchParents' => (int) config('notifications.max_batch_parents', 5),
            'minBatchParents' => (int) config('notifications.min_batch_parents', 1),
        ]);
    }

    public function unpaidPdf(Request $request)
    {
        $report = $this->buildUnpaidReport($request);

        $pdf = Pdf::loadView('reports.unpaid-pdf', [
            'students' => $report['students'],
            'summary' => $report['summary'],
            'setting' => $report['setting'],
            'classFilter' => $report['classFilter'],
        ]);

        return $pdf->download('unpaid_balances_report_'.now()->format('Y_m_d_His').'.pdf');
    }

    public function paid(Request $request): RedirectResponse
    {
        return redirect()->route('reports.clearance', $request->query());
    }

    public function sendReminders(BatchParentReminderRequest $request)
    {
        $maxBatch = (int) config('notifications.max_batch_parents', 5);
        $data = $request->validated();

        $students = Student::query()
            ->with(['parentUser', 'primaryParentLink'])
            ->withSum('receipts', 'amount')
            ->whereIn('id', $data['student_ids'])
            ->get();

        $messages = $this->parentReminderService->sendBatchToStudents(
            $students,
            $request->sendSms(),
            $request->sendEmail(),
            $data['message_type'] ?? null
        );

        return back()->with('status', 'Sent to '.count($students).' parent(s) (max '.$maxBatch.' per batch). '.implode(' | ', $messages));
    }

    public function clearance(Request $request)
    {
        $classFilter = trim((string) $request->get('class_name', ''));
        $report = $this->clearanceService->build($classFilter ?: null);

        return view('reports.clearance', [
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'classes' => $report['classes'],
            'classFilter' => $classFilter,
        ]);
    }

    public function clearancePdf(Request $request)
    {
        $classFilter = trim((string) $request->get('class_name', ''));
        $report = $this->clearanceService->build($classFilter ?: null);

        $pdf = Pdf::loadView('reports.clearance-pdf', [
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'setting' => $report['setting'],
            'classFilter' => $classFilter,
        ]);

        $filename = 'term_clearance_report_'.now()->format('Y_m_d_His').'.pdf';

        return $pdf->download($filename);
    }

    public function feePosition(Request $request)
    {
        $classFilter = trim((string) $request->get('class_name', ''));
        $statusFilter = trim((string) $request->get('status', ''));

        $report = $this->feePositionService->build(
            $classFilter !== '' ? $classFilter : null,
            $statusFilter !== '' ? $statusFilter : null
        );

        return view('reports.fee-position', [
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'classes' => $report['classes'],
            'classFilter' => $classFilter,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function feePositionPdf(Request $request)
    {
        $classFilter = trim((string) $request->get('class_name', ''));
        $statusFilter = trim((string) $request->get('status', ''));

        $report = $this->feePositionService->build(
            $classFilter !== '' ? $classFilter : null,
            $statusFilter !== '' ? $statusFilter : null
        );

        $pdf = Pdf::loadView('reports.fee-position-pdf', [
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'setting' => $report['setting'],
            'classFilter' => $classFilter,
            'statusFilter' => $statusFilter,
        ]);

        return $pdf->download('school_fee_position_'.now()->format('Y_m_d_His').'.pdf');
    }

    public function receiptRegister(Request $request)
    {
        if ($request->isMethod('post') || $request->filled('date_range')) {
            $this->validateReportRequest($request);
            $report = $this->receiptRegisterService->build($request);

            return view('reports.receipt-register', [
                'rows' => $report['rows'],
                'summary' => $report['summary'],
                'setting' => $report['setting'],
                'request' => $request,
                'categories' => PaymentCategory::orderBy('name')->get(['id', 'name']),
                'generated' => true,
            ]);
        }

        return view('reports.receipt-register', [
            'rows' => collect(),
            'summary' => [],
            'setting' => null,
            'request' => $request,
            'categories' => PaymentCategory::orderBy('name')->get(['id', 'name']),
            'generated' => false,
        ]);
    }

    public function receiptRegisterPdf(Request $request)
    {
        $this->validateReportRequest($request);
        $report = $this->receiptRegisterService->build($request);

        $pdf = Pdf::loadView('reports.receipt-register-pdf', [
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'setting' => $report['setting'],
            'request' => $request,
        ]);

        return $pdf->download('receipt_register_'.now()->format('Y_m_d_His').'.pdf');
    }

    public function messageHistory(Request $request)
    {
        $this->validateMessageHistoryRequest($request);

        if ($request->isMethod('post') || $request->hasAny(['date_from', 'date_to', 'channel', 'status', 'student_id', 'event_type', 'q'])) {
            $report = $this->messageHistoryService->build($request);

            return view('reports.message-history', [
                'rows' => $report['rows'],
                'summary' => $report['summary'],
                'setting' => $report['setting'],
                'students' => $report['students'],
                'request' => $request,
                'generated' => true,
            ]);
        }

        return view('reports.message-history', [
            'rows' => collect(),
            'summary' => [],
            'setting' => null,
            'students' => $this->messageHistoryService->build($request)['students'],
            'request' => $request,
            'generated' => false,
        ]);
    }

    public function messageHistoryPdf(Request $request)
    {
        $this->validateMessageHistoryRequest($request);
        $report = $this->messageHistoryService->build($request);

        $pdf = Pdf::loadView('reports.message-history-pdf', [
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'setting' => $report['setting'],
            'request' => $request,
        ]);

        return $pdf->download('sms_email_history_report_'.now()->format('Y_m_d_His').'.pdf');
    }

    public function bankProofs(Request $request)
    {
        $this->validateBankProofRequest($request);

        if ($request->isMethod('post') || $request->hasAny(['date_from', 'date_to', 'status', 'bank'])) {
            $report = $this->bankPaymentReportService->build($request);

            return view('reports.bank-proofs', [
                'rows' => $report['rows'],
                'summary' => $report['summary'],
                'setting' => $report['setting'],
                'request' => $request,
                'generated' => true,
            ]);
        }

        return view('reports.bank-proofs', [
            'rows' => collect(),
            'summary' => [],
            'setting' => null,
            'request' => $request,
            'generated' => false,
        ]);
    }

    public function bankProofsPdf(Request $request)
    {
        $this->validateBankProofRequest($request);
        $report = $this->bankPaymentReportService->build($request);

        $pdf = Pdf::loadView('reports.bank-proofs-pdf', [
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'setting' => $report['setting'],
            'request' => $request,
        ]);

        return $pdf->download('bank_payment_proofs_report_'.now()->format('Y_m_d_His').'.pdf');
    }

    /** @return array{students: \Illuminate\Support\Collection, summary: array<string, int>, classes: \Illuminate\Support\Collection, classFilter: string, setting: \App\Models\Setting} */
    private function buildUnpaidReport(Request $request): array
    {
        $classFilter = trim((string) $request->get('class_name', ''));

        $students = Student::query()
            ->with(['feeStructures', 'parentUser', 'primaryParentLink'])
            ->withSum('receipts', 'amount')
            ->when($classFilter !== '', fn ($q) => $q->where('class_name', 'like', '%'.$classFilter.'%'))
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                $dueDate = $student->resolveFeeDueDate();
                $daysUntilDue = now()->startOfDay()->diffInDays($dueDate->copy()->startOfDay(), false);

                return [
                    'student' => $student,
                    'expected' => $student->expected_amount,
                    'paid' => $student->paid_amount,
                    'balance' => $student->balance,
                    'days_until_due' => $daysUntilDue,
                    'is_overdue' => $student->isFeeOverdue(),
                    'milestone' => $this->resolveMilestoneLabel($student, $daysUntilDue),
                ];
            })
            ->filter(fn ($row) => $row['balance'] > 0)
            ->sortBy('days_until_due')
            ->values();

        $summary = [
            'students_with_balance' => $students->count(),
            'total_outstanding' => $students->sum('balance'),
            'overdue_count' => $students->where('is_overdue', true)->count(),
            'due_in_14_days' => $students->filter(fn ($r) => $r['days_until_due'] === 14)->count(),
        ];

        $classes = Student::query()->whereNotNull('class_name')->distinct()->orderBy('class_name')->pluck('class_name');

        return [
            'students' => $students,
            'summary' => $summary,
            'classes' => $classes,
            'classFilter' => $classFilter,
            'setting' => \App\Models\Setting::current() ?? new \App\Models\Setting(['school_name' => config('app.name')]),
        ];
    }

    private function resolveMilestoneLabel(Student $student, ?int $daysUntilDue): string
    {
        if ($student->balance <= 0) {
            return 'Paid';
        }

        if ($daysUntilDue !== null && $daysUntilDue < 0) {
            return 'Overdue';
        }

        return match ($daysUntilDue) {
            14 => '2 weeks before due',
            7 => '1 week before due',
            3 => '3 days before due',
            0 => 'Due today',
            default => $daysUntilDue !== null ? "{$daysUntilDue} days left" : 'No due date',
        };
    }

    private function validateReportRequest(Request $request): void
    {
        $request->validate([
            'date_range' => 'required|in:today,yesterday,this_week,last_week,this_month,last_month,this_year,last_year,custom',
            'start_date' => 'nullable|required_if:date_range,custom|date',
            'end_date' => 'nullable|required_if:date_range,custom|date|after_or_equal:start_date',
            'class_name' => 'nullable|string|max:255',
            'payment_category_id' => 'nullable|exists:payment_categories,id',
            'payment_mode' => 'nullable|in:Cash,Bank,Mobile Money,Other',
            'min_amount' => 'nullable|integer|min:0',
            'max_amount' => 'nullable|integer|min:0',
        ]);
    }

    private function validateMessageHistoryRequest(Request $request): void
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'channel' => 'nullable|in:sms,email',
            'status' => 'nullable|in:sent,failed,skipped',
            'student_id' => 'nullable|exists:students,id',
            'event_type' => 'nullable|string|max:64',
            'q' => 'nullable|string|max:255',
        ]);
    }

    private function validateBankProofRequest(Request $request): void
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'status' => 'nullable|in:pending,verified,review,rejected',
            'bank' => 'nullable|in:nmb,crdb',
        ]);
    }
}
