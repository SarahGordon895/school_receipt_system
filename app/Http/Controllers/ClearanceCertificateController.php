<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Student;
use App\Services\TermClearanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ClearanceCertificateController extends Controller
{
    public function __construct(private TermClearanceReportService $clearanceService)
    {
    }

    public function __invoke(Request $request, Student $student)
    {
        if ($request->user()?->isParent() && ! $student->belongsToParent($request->user())) {
            abort(403, 'You can only access records for your own child.');
        }

        $student->load(['feeStructures', 'parentUser', 'primaryParentLink']);
        $student->loadSum('receipts', 'amount');
        $student->load(['receipts' => fn ($q) => $q->with('paymentCategories')->latest('payment_date')]);

        if (! $student->isFullyPaid()) {
            return back()->withErrors([
                'certificate' => 'Clearance certificate is only available when all school fees are paid in full (balance Tsh 0).',
            ]);
        }

        $setting = Setting::current() ?? new Setting(['school_name' => config('app.name')]);
        $clearanceRef = $this->clearanceService->clearanceReference($student);
        $lastPayment = $student->receipts->first();

        $pdf = Pdf::loadView('certificates.paid-in-full', compact('student', 'setting', 'clearanceRef', 'lastPayment'));
        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $student->name);
        $filename = 'fee_clearance_'.$safeName.'_'.now()->format('Y_m_d').'.pdf';

        return $pdf->download($filename);
    }
}
