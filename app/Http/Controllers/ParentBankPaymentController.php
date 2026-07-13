<?php

namespace App\Http\Controllers;

use App\Models\BankPaymentSubmission;
use App\Models\Setting;
use App\Models\Student;
use App\Services\BankPaymentVerificationService;
use App\Services\BankReceiptParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ParentBankPaymentController extends Controller
{
    public function index(Request $request)
    {
        $parent = $request->user();
        $students = $parent->parentStudents()
            ->with(['feeStructures'])
            ->withSum('receipts', 'amount')
            ->orderBy('name')
            ->get();

        $submissions = BankPaymentSubmission::query()
            ->with(['student:id,name,admission_no,class_name', 'receipt:id,receipt_no'])
            ->where('parent_user_id', $parent->id)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $setting = Setting::current();
        $selectedStudentId = old('student_id', $request->integer('student_id') ?: null);

        return view('parents.bank-payments', compact('parent', 'students', 'submissions', 'setting', 'selectedStudentId'));
    }

    public function store(Request $request, BankReceiptParser $parser, BankPaymentVerificationService $verification)
    {
        $parent = $request->user();
        $studentIds = $parent->parentStudents()->pluck('students.id');

        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::in($studentIds->all())],
            'receipt_pdf' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $student = Student::query()
            ->with(['feeStructures'])
            ->withSum('receipts', 'amount')
            ->findOrFail($data['student_id']);
        abort_unless($student->belongsToParent($parent), 403);

        $path = $request->file('receipt_pdf')->store('bank-receipts/'.$parent->id, 'local');
        $absolutePath = Storage::disk('local')->path($path);
        $parsed = $parser->parseFromPdf($absolutePath);

        $submission = BankPaymentSubmission::create([
            'parent_user_id' => $parent->id,
            'student_id' => $student->id,
            'original_filename' => $request->file('receipt_pdf')->getClientOriginalName(),
            'file_path' => $path,
            'bank' => $parsed->bank,
            'extracted_amount' => $parsed->amount,
            'extracted_reference' => $parsed->reference,
            'extracted_payment_date' => $parsed->paymentDate,
            'extracted_account_number' => $parser->normalizeAccount($parsed->accountNumber),
            'extracted_raw_text' => Str($parsed->rawText)->limit(8000)->value(),
            'status' => 'pending',
        ]);

        $submission = $verification->processSubmission($submission);

        $message = match ($submission->status) {
            'verified' => 'Payment verified automatically. Your child\'s balance has been updated and a school receipt was created.',
            'review' => 'Receipt uploaded. Some details need school review — you will be notified once confirmed.',
            'rejected' => 'Receipt could not be verified: '.$submission->verification_message,
            default => 'Receipt uploaded and is being processed.',
        };

        return redirect()
            ->route('parent.bank-payments.index')
            ->with($submission->status === 'rejected' ? 'error' : 'status', $message);
    }
}
