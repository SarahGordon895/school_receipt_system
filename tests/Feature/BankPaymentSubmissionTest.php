<?php

namespace Tests\Feature;

use App\Models\BankPaymentSubmission;
use App\Models\PaymentCategory;
use App\Models\Receipt;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\BankPaymentVerificationService;
use App\Services\BankReceiptParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class BankPaymentSubmissionTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

  protected function setUp(): void
    {
        parent::setUp();

        Setting::query()->create([
            'school_name' => 'Mbonea Secondary School',
            'bank_nmb_account_name' => 'Mbonea Secondary School',
            'bank_nmb_account_number' => '20110012345',
            'bank_crdb_account_name' => 'Mbonea Secondary School',
            'bank_crdb_account_number' => '0150123456789',
        ]);

        PaymentCategory::query()->create(['name' => 'Tuition', 'default_amount' => 400_000]);
        User::factory()->create(['role' => 'school_admin', 'email' => 'admin@mbonea.sc.tz']);
    }

    public function test_parser_extracts_nmb_receipt_fields(): void
    {
        $text = <<<'TXT'
        NMB Bank Plc
        Payment Receipt
        Transaction Reference: NMB20250618001
        Beneficiary Account Number: 20110012345
        Amount: TZS 150,000.00
        Payment Date: 18/06/2025
        TXT;

        $parsed = app(BankReceiptParser::class)->parseFromText($text);

        $this->assertSame('nmb', $parsed->bank);
        $this->assertSame(150_000, $parsed->amount);
        $this->assertSame('NMB20250618001', $parsed->reference);
        $this->assertSame('20110012345', app(BankReceiptParser::class)->normalizeAccount($parsed->accountNumber));
    }

    public function test_parser_extracts_crdb_receipt_fields(): void
    {
        $text = <<<'TXT'
        CRDB Bank Plc
        Transfer Confirmation
        Ref No: CRDB789456123
        Credit Account Number: 0150123456789
        Transaction Amount TSH 200,000
        Date: 18-Jun-2025
        TXT;

        $parsed = app(BankReceiptParser::class)->parseFromText($text);

        $this->assertSame('crdb', $parsed->bank);
        $this->assertSame(200_000, $parsed->amount);
        $this->assertSame('CRDB789456123', $parsed->reference);
    }

    public function test_verification_auto_passes_valid_nmb_receipt(): void
    {
        $parent = User::factory()->create(['role' => 'parent', 'phone' => '+255712000111']);
        $student = $this->admitStudentForParent($parent, [
            'expected_total_fee' => 300_000,
            'fee_due_date' => now()->addDays(14)->toDateString(),
        ]);

        $parsed = app(BankReceiptParser::class)->parseFromText(<<<'TXT'
        NMB Bank Payment Receipt
        Transaction Reference: NMBVERIFY001
        Beneficiary Account Number: 20110012345
        Amount: TZS 100,000.00
        Payment Date: 10/07/2026
        TXT);

        $submission = BankPaymentSubmission::create([
            'parent_user_id' => $parent->id,
            'student_id' => $student->id,
            'original_filename' => 'nmb.pdf',
            'file_path' => 'bank-receipts/test.pdf',
            'bank' => $parsed->bank,
            'extracted_amount' => $parsed->amount,
            'extracted_reference' => $parsed->reference,
            'extracted_payment_date' => $parsed->paymentDate,
            'extracted_account_number' => app(BankReceiptParser::class)->normalizeAccount($parsed->accountNumber),
            'status' => 'pending',
        ]);

        $result = app(BankPaymentVerificationService::class)->processSubmission($submission);

        $this->assertSame('verified', $result->status);
        $this->assertNotNull($result->receipt_id);
        $this->assertDatabaseHas('receipts', [
            'student_id' => $student->id,
            'amount' => 100_000,
            'payment_mode' => 'Bank',
            'reference' => 'NMBVERIFY001',
        ]);
    }

    public function test_parent_can_upload_bank_receipt_pdf(): void
    {
        Storage::fake('local');

        $parent = User::factory()->create(['role' => 'parent', 'phone' => '+255712000222']);
        $student = $this->admitStudentForParent($parent, ['expected_total_fee' => 250_000]);

        $pdfContent = "%PDF-1.4\nNMB Bank Payment Receipt Transaction Reference: NMBUPLOAD001 Beneficiary Account Number: 20110012345 Amount: TZS 50,000.00 Payment Date: 10/07/2026";
        $file = UploadedFile::fake()->createWithContent('receipt.pdf', $pdfContent);

        $response = $this->actingAs($parent)->post(route('parent.bank-payments.store'), [
            'student_id' => $student->id,
            'receipt_pdf' => $file,
        ]);

        $response->assertRedirect(route('parent.bank-payments.index'));
        $this->assertDatabaseHas('bank_payment_submissions', [
            'parent_user_id' => $parent->id,
            'student_id' => $student->id,
            'extracted_reference' => 'NMBUPLOAD001',
        ]);
    }

    public function test_parent_cannot_upload_for_another_parents_student(): void
    {
        Storage::fake('local');

        $parent = User::factory()->create(['role' => 'parent']);
        $otherParent = User::factory()->create(['role' => 'parent', 'phone' => '+255712000333']);
        $student = $this->admitStudentForParent($otherParent, ['expected_total_fee' => 100_000]);

        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $this->actingAs($parent)->post(route('parent.bank-payments.store'), [
            'student_id' => $student->id,
            'receipt_pdf' => $file,
        ])->assertSessionHasErrors('student_id');
    }

    public function test_school_admin_can_review_bank_submissions(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);

        $this->actingAs($admin)
            ->get(route('bank-payments.index'))
            ->assertOk()
            ->assertSee('Bank Payment Receipts');
    }
}
