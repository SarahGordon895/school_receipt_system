<?php

namespace App\Services;

use App\Data\BankReceiptData;
use App\Models\BankPaymentSubmission;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BankPaymentVerificationService
{
    public function __construct(
        private BankReceiptParser $parser,
        private ParentPaymentNotifier $paymentNotifier,
    ) {
    }

    public function processSubmission(BankPaymentSubmission $submission, bool $manualApproval = false, ?User $reviewer = null): BankPaymentSubmission
    {
        return DB::transaction(function () use ($submission, $manualApproval, $reviewer) {
            $submission = BankPaymentSubmission::query()->lockForUpdate()->findOrFail($submission->id);

            if ($submission->status === 'verified') {
                return $submission;
            }

            $student = $submission->student()->withSum('receipts', 'amount')->firstOrFail();
            $parsed = new BankReceiptData(
                bank: $submission->bank,
                amount: $submission->extracted_amount,
                reference: $submission->extracted_reference,
                accountNumber: $submission->extracted_account_number,
                paymentDate: $submission->extracted_payment_date,
                rawText: (string) $submission->extracted_raw_text,
            );

            if ($manualApproval) {
                return $this->finalizeVerified($submission, $student, $parsed, $reviewer, 'Approved manually by school staff.');
            }

            $result = $this->evaluate($student, $parsed);

            if ($result['status'] === 'verified') {
                return $this->finalizeVerified($submission, $student, $parsed, null, $result['message']);
            }

            $submission->update([
                'status' => $result['status'],
                'verification_message' => $result['message'],
                'reviewed_at' => $result['status'] === 'rejected' ? now() : null,
            ]);

            return $submission->fresh();
        });
    }

    /** @return array{status: string, message: string} */
    public function evaluate(Student $student, BankReceiptData $parsed): array
    {
        $setting = Setting::query()->first();
        $issues = [];
        $warnings = [];

        if (! $parsed->bank) {
            $issues[] = 'Could not detect NMB or CRDB bank on the uploaded receipt.';
        }

        if (! $parsed->amount || $parsed->amount <= 0) {
            $issues[] = 'Payment amount was not found on the bank receipt.';
        }

        if (! filled($parsed->reference)) {
            $issues[] = 'Bank transaction reference was not found.';
        } elseif ($this->referenceAlreadyUsed($parsed->reference, null)) {
            $issues[] = 'This bank reference has already been used for another payment.';
        }

        if (! filled($parsed->accountNumber)) {
            $issues[] = 'School beneficiary account number was not found on the receipt.';
        } else {
            $expectedAccount = $this->expectedSchoolAccount($parsed->bank, $setting);
            if (! filled($expectedAccount)) {
                $warnings[] = 'School bank account for '.$parsed->bank.' is not configured in Admin Settings.';
            } elseif (! $this->parser->accountsMatch($parsed->accountNumber, $expectedAccount)) {
                $issues[] = 'Payment was not made to the school\'s registered '.$parsed->bank.' account.';
            }
        }

        if (! $parsed->paymentDate) {
            $issues[] = 'Payment date was not found on the bank receipt.';
        } elseif ($parsed->paymentDate->isFuture()) {
            $issues[] = 'Payment date on the receipt is in the future.';
        } elseif ($parsed->paymentDate->lt(now()->subDays(120)->startOfDay())) {
            $issues[] = 'Payment date is older than 120 days.';
        }

        if ($parsed->amount && $student->balance <= 0) {
            $issues[] = 'This student has no outstanding balance.';
        }

        if ($parsed->amount && $student->balance > 0 && $parsed->amount > $student->balance) {
            $issues[] = 'Paid amount (Tsh '.number_format($parsed->amount).') exceeds outstanding balance (Tsh '.number_format($student->balance).').';
        }

        if (! empty($issues)) {
            $hardReject = collect($issues)->contains(fn (string $issue) => str_contains($issue, 'already been used')
                || str_contains($issue, 'not made to the school')
                || str_contains($issue, 'Could not detect')
                || str_contains($issue, 'was not found on the bank receipt')
                || str_contains($issue, 'no outstanding balance'));

            return [
                'status' => $hardReject ? 'rejected' : 'review',
                'message' => implode(' ', $issues),
            ];
        }

        if (! empty($warnings)) {
            return [
                'status' => 'review',
                'message' => implode(' ', $warnings).' Automatic checks passed otherwise; school staff should confirm.',
            ];
        }

        if (! $parsed->hasMinimumFields()) {
            return [
                'status' => 'review',
                'message' => 'Some receipt details need manual review before confirming payment.',
            ];
        }

        return [
            'status' => 'verified',
            'message' => 'Bank receipt verified automatically. Payment recorded against '.$student->name.'.',
        ];
    }

    private function finalizeVerified(
        BankPaymentSubmission $submission,
        Student $student,
        BankReceiptData $parsed,
        ?User $reviewer,
        string $message,
    ): BankPaymentSubmission {
        if ($submission->receipt_id) {
            $submission->update([
                'status' => 'verified',
                'verification_message' => $message,
                'reviewed_by_user_id' => $reviewer?->id,
                'reviewed_at' => now(),
            ]);

            return $submission->fresh();
        }

        $receipt = $this->createReceipt($student, $parsed, $reviewer, $submission->parent_user_id);

        $submission->update([
            'status' => 'verified',
            'verification_message' => $message,
            'receipt_id' => $receipt->id,
            'reviewed_by_user_id' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);

        $this->paymentNotifier->notify($receipt);

        return $submission->fresh(['receipt', 'student']);
    }

    private function createReceipt(Student $student, BankReceiptData $parsed, ?User $reviewer, ?int $parentUserId = null): Receipt
    {
        $recordedBy = $reviewer?->id
            ?? $student->registered_by_user_id
            ?? User::query()->whereIn('role', ['school_admin', 'super_admin'])->value('id')
            ?? $parentUserId;

        $receipt = Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'class_name' => $student->class_name,
            'amount' => $parsed->amount,
            'payment_date' => $parsed->paymentDate?->toDateString() ?? now()->toDateString(),
            'payment_mode' => 'Bank',
            'reference' => $parsed->reference,
            'note' => 'Verified from parent bank receipt upload ('.strtoupper((string) $parsed->bank).').',
            'user_id' => $recordedBy,
        ]);

        $tuition = PaymentCategory::query()->where('name', 'Tuition')->first();
        if ($tuition) {
            $receipt->syncPaymentCategories([$tuition->id => $parsed->amount]);
        }

        return $receipt->load('paymentCategories');
    }

    private function expectedSchoolAccount(?string $bank, ?Setting $setting): ?string
    {
        if (! $setting || ! $bank) {
            return null;
        }

        return match ($bank) {
            'nmb' => $setting->bank_nmb_account_number,
            'crdb' => $setting->bank_crdb_account_number,
            default => null,
        };
    }

    public function referenceAlreadyUsed(string $reference, ?int $ignoreSubmissionId = null): bool
    {
        $reference = strtoupper(trim($reference));

        $receiptExists = Receipt::query()->where('reference', $reference)->exists();
        if ($receiptExists) {
            return true;
        }

        return BankPaymentSubmission::query()
            ->where('extracted_reference', $reference)
            ->where('status', 'verified')
            ->when($ignoreSubmissionId, fn ($q) => $q->where('id', '!=', $ignoreSubmissionId))
            ->exists();
    }
}
