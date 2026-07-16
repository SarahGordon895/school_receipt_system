<?php

namespace App\Console\Commands;

use App\Models\Receipt;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareBankDemo extends Command
{
    protected $signature = 'ftrs:prepare-bank-demo
                            {--amount=150000 : Demo payment amount in TZS}
                            {--bank=nmb : Bank for sample receipt (nmb or crdb)}
                            {--offline : Generate PDF only (no DB balance check)}';

    protected $description = 'Prepare Gordon parent bank-upload demo: ensure outstanding balance and generate a parseable receipt PDF';

    public function handle(): int
    {
        $amount = max(1, (int) $this->option('amount'));
        $bank = strtolower((string) $this->option('bank'));
        if (! in_array($bank, ['nmb', 'crdb'], true)) {
            $this->error('Bank must be nmb or crdb.');

            return self::FAILURE;
        }

        if ($this->option('offline')) {
            return $this->generateOfflinePdf($bank, $amount);
        }

        try {
            return $this->prepareWithDatabase($bank, $amount);
        } catch (\Throwable $e) {
            $this->warn('Database unavailable: '.$e->getMessage());
            $this->warn('Falling back to offline PDF generation. Start MySQL and re-run without --offline before the live demo so balance is ready.');

            return $this->generateOfflinePdf($bank, $amount);
        }
    }

    private function prepareWithDatabase(string $bank, int $amount): int
    {
        $parent = User::query()
            ->where('role', 'parent')
            ->where(function ($q) {
                $q->where('phone', '+255755666899')
                    ->orWhere('email', 'parent.gordon@mbonea.sc.tz');
            })
            ->first();

        if (! $parent) {
            $this->error('Gordon parent not found. Run: php artisan ftrs:install --fresh');

            return self::FAILURE;
        }

        $student = $parent->parentStudents()
            ->with(['feeStructures'])
            ->withSum('receipts', 'amount')
            ->where('admission_no', 'MBN-2024-002')
            ->first()
            ?? $parent->parentStudents()
                ->with(['feeStructures'])
                ->withSum('receipts', 'amount')
                ->orderBy('name')
                ->first();

        if (! $student) {
            $this->error('No linked student found for Gordon parent.');

            return self::FAILURE;
        }

        $this->ensureOutstandingBalance($student, $amount);
        $student = $student->fresh(['feeStructures'])->loadSum('receipts', 'amount');

        if ($student->balance < $amount) {
            $this->error("Could not free enough balance. Current balance: {$student->balance}, need: {$amount}");

            return self::FAILURE;
        }

        $setting = Setting::current();
        $accountNumber = $bank === 'crdb'
            ? ($setting->bank_crdb_account_number ?: '0150123456789')
            : ($setting->bank_nmb_account_number ?: '20110012345');
        $accountName = $bank === 'crdb'
            ? ($setting->bank_crdb_account_name ?: 'Mbonea Secondary School')
            : ($setting->bank_nmb_account_name ?: 'Mbonea Secondary School');

        $reference = strtoupper($bank).'DEMO'.now()->format('YmdHis').random_int(100, 999);

        $path = $this->writePdf(
            bank: $bank,
            amount: $amount,
            reference: $reference,
            accountNumber: $accountNumber,
            accountName: $accountName,
            payerName: $parent->name,
            studentName: $student->name,
            admissionNo: $student->admission_no,
        );

        $this->printReadyTable(
            studentLabel: $student->name.' ('.$student->admission_no.')',
            balance: $student->balance,
            amount: $amount,
            reference: $reference,
            accountNumber: $accountNumber,
            path: $path,
            studentName: $student->name,
        );

        return self::SUCCESS;
    }

    private function generateOfflinePdf(string $bank, int $amount): int
    {
        $accountNumber = $bank === 'crdb' ? '0150123456789' : '20110012345';
        $reference = strtoupper($bank).'DEMO'.now()->format('YmdHis').random_int(100, 999);

        $path = $this->writePdf(
            bank: $bank,
            amount: $amount,
            reference: $reference,
            accountNumber: $accountNumber,
            accountName: 'Mbonea Secondary School',
            payerName: 'Gordon Guardian',
            studentName: 'Sarah George Gordon',
            admissionNo: 'MBN-2024-002',
        );

        $this->newLine();
        $this->info('Offline demo PDF generated (DB not used).');
        $this->table(
            ['Item', 'Value'],
            [
                ['Parent login', '+255755666899'],
                ['Parent password', 'Gordon@2025'],
                ['Student', 'Sarah George Gordon (MBN-2024-002)'],
                ['PDF amount', 'Tsh '.number_format($amount)],
                ['Bank reference', $reference],
                ['School account', $accountNumber],
                ['PDF path', $path],
            ]
        );
        $this->warn('Before presenting: start MySQL, then run: php artisan ftrs:prepare-bank-demo');
        $this->warn('That ensures Gordon\'s child has outstanding balance so upload is not rejected.');

        return self::SUCCESS;
    }

    private function writePdf(
        string $bank,
        int $amount,
        string $reference,
        string $accountNumber,
        string $accountName,
        string $payerName,
        string $studentName,
        string $admissionNo,
    ): string {
        $paymentDate = $bank === 'crdb'
            ? now()->subDay()->format('d-M-Y')
            : now()->subDay()->format('d/m/Y');

        $dir = base_path('docs/demo-receipts');
        File::ensureDirectoryExists($dir);
        $filename = $bank === 'crdb'
            ? 'Gordon_CRDB_Fee_Payment.pdf'
            : 'Gordon_NMB_Fee_Payment.pdf';
        $path = $dir.'/'.$filename;

        Pdf::loadView('demo.bank-nmb-receipt', [
            'bank' => $bank,
            'reference' => $reference,
            'accountNumber' => $accountNumber,
            'accountName' => $accountName,
            'payerName' => $payerName,
            'studentName' => $studentName,
            'admissionNo' => $admissionNo,
            'amount' => $amount,
            'paymentDate' => $paymentDate,
        ])->save($path);

        return $path;
    }

    private function printReadyTable(
        string $studentLabel,
        int $balance,
        int $amount,
        string $reference,
        string $accountNumber,
        string $path,
        string $studentName,
    ): void {
        $this->newLine();
        $this->info('Bank payment demo is ready.');
        $this->table(
            ['Item', 'Value'],
            [
                ['Parent login', '+255755666899'],
                ['Parent password', 'Gordon@2025'],
                ['Student', $studentLabel],
                ['Outstanding balance', 'Tsh '.number_format($balance)],
                ['PDF amount', 'Tsh '.number_format($amount)],
                ['Bank reference', $reference],
                ['School account', $accountNumber],
                ['PDF path', $path],
            ]
        );

        $this->newLine();
        $this->line('Presentation steps:');
        $this->line('  1. Login as Gordon parent → Bank Payments');
        $this->line('  2. Select '.$studentName);
        $this->line('  3. Upload '.basename($path));
        $this->line('  4. Expect auto-verified + school receipt created');
        $this->line('  5. Optional: login as bursar and show Bank Payments review / receipt register');
        $this->warn('Re-run this command before each live demo so the PDF gets a fresh unique reference.');
    }

    private function ensureOutstandingBalance(Student $student, int $needed): void
    {
        $student->load(['feeStructures'])->loadSum('receipts', 'amount');

        if ($student->balance >= $needed) {
            $this->line('Student already has outstanding balance of Tsh '.number_format($student->balance).'.');

            return;
        }

        $this->warn('Balance too low (Tsh '.number_format($student->balance).'). Removing newest receipts until enough balance exists…');

        while ($student->balance < $needed) {
            $receipt = Receipt::query()
                ->where('student_id', $student->id)
                ->latest('id')
                ->first();

            if (! $receipt) {
                break;
            }

            $this->line('  Removed receipt '.$receipt->receipt_no.' (Tsh '.number_format($receipt->amount).')');
            $receipt->paymentCategories()->detach();
            $receipt->delete();

            $student = $student->fresh(['feeStructures'])->loadSum('receipts', 'amount');
        }
    }
}
