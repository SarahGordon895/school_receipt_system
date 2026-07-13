<?php

namespace App\Http\Controllers;

use App\Models\BankPaymentSubmission;
use App\Services\BankPaymentVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BankPaymentSubmissionController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');

        $submissions = BankPaymentSubmission::query()
            ->with(['student:id,name,admission_no,class_name', 'parentUser:id,name,phone,email', 'receipt:id,receipt_no'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = BankPaymentSubmission::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('bank-payments.index', compact('submissions', 'status', 'counts'));
    }

    public function show(BankPaymentSubmission $bankPayment)
    {
        $bankPayment->load(['student.feeStructures', 'parentUser', 'receipt', 'reviewedBy']);
        $bankPayment->student?->loadSum('receipts', 'amount');

        return view('bank-payments.show', ['submission' => $bankPayment]);
    }

    public function approve(Request $request, BankPaymentSubmission $bankPayment, BankPaymentVerificationService $verification)
    {
        abort_if($bankPayment->status === 'verified', 422, 'This submission is already verified.');
        abort_if(! $bankPayment->extracted_amount || ! $bankPayment->extracted_reference, 422, 'Cannot approve without extracted amount and bank reference.');

        $verification->processSubmission($bankPayment, manualApproval: true, reviewer: $request->user());

        return redirect()
            ->route('bank-payments.show', $bankPayment)
            ->with('status', 'Bank payment approved and recorded as a school receipt.');
    }

    public function reject(Request $request, BankPaymentSubmission $bankPayment)
    {
        abort_if($bankPayment->status === 'verified', 422, 'Verified submissions cannot be rejected.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $bankPayment->update([
            'status' => 'rejected',
            'verification_message' => $data['reason'],
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()
            ->route('bank-payments.index')
            ->with('status', 'Bank payment submission rejected.');
    }

    public function download(BankPaymentSubmission $bankPayment)
    {
        abort_unless(Storage::disk('local')->exists($bankPayment->file_path), 404);

        return Storage::disk('local')->download($bankPayment->file_path, $bankPayment->original_filename);
    }
}
