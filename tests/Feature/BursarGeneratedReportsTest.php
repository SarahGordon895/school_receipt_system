<?php

namespace Tests\Feature;

use App\Models\BankPaymentSubmission;
use App\Models\FeeStructure;
use App\Models\NotificationLog;
use App\Models\Receipt;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Tests\Support\AdmitsStudents;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BursarGeneratedReportsTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    public function test_fee_position_report_reflects_receipts_and_fee_structures(): void
    {
        Setting::query()->create(['school_name' => 'Bursar School']);

        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent']);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'POS-001',
            'name' => 'Position Student',
            'expected_total_fee' => 500_000,
        ]);

        $structure = FeeStructure::create([
            'name' => 'Form I Fees',
            'class_name' => 'Form I',
            'amount' => 500_000,
            'due_date' => now()->addDays(14),
            'is_active' => true,
        ]);
        $student->feeStructures()->sync([$structure->id]);

        Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'amount' => 200_000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.fee-position'))
            ->assertOk()
            ->assertSee('Position Student')
            ->assertSee('500,000')
            ->assertSee('200,000')
            ->assertSee('300,000');
    }

    public function test_receipt_register_lists_recorded_receipts_for_period(): void
    {
        Setting::query()->create(['school_name' => 'Bursar School']);

        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent']);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'REG-001',
            'name' => 'Receipt Register Student',
            'expected_total_fee' => 100_000,
        ]);

        $receipt = Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'amount' => 75_000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Bank',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('reports.receipts'), ['date_range' => 'today'])
            ->assertOk()
            ->assertSee($receipt->receipt_no)
            ->assertSee('Receipt Register Student')
            ->assertSee('75,000');
    }

    public function test_unpaid_report_pdf_lists_outstanding_balances(): void
    {
        Setting::query()->create(['school_name' => 'Bursar School']);

        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent']);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'UNP-001',
            'name' => 'Unpaid Report Student',
            'expected_total_fee' => 300_000,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.unpaid'))
            ->assertOk()
            ->assertSee('Unpaid Report Student');

        $this->actingAs($admin)
            ->get(route('reports.unpaid.pdf'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_message_history_report_lists_notification_logs(): void
    {
        Setting::query()->create(['school_name' => 'Bursar School']);

        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent']);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'MSG-001',
            'name' => 'Message History Student',
        ]);

        NotificationLog::create([
            'student_id' => $student->id,
            'channel' => 'sms',
            'event_type' => 'fee_reminder',
            'status' => 'sent',
            'sent_on' => now()->toDateString(),
            'message' => 'Please pay school fees for Message History Student.',
        ]);

        $this->actingAs($admin)
            ->post(route('reports.messages'), ['date_from' => now()->subDay()->toDateString()])
            ->assertOk()
            ->assertSee('Message History Student')
            ->assertSee('Please pay school fees');
    }

    public function test_bank_proof_report_lists_submissions(): void
    {
        Setting::query()->create(['school_name' => 'Bursar School']);

        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent']);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'BNK-001',
            'name' => 'Bank Proof Student',
        ]);

        BankPaymentSubmission::create([
            'parent_user_id' => $parent->id,
            'student_id' => $student->id,
            'original_filename' => 'proof.pdf',
            'file_path' => 'bank-proofs/proof.pdf',
            'bank' => 'nmb',
            'extracted_amount' => 150_000,
            'extracted_reference' => 'REF-12345',
            'status' => 'verified',
        ]);

        $this->actingAs($admin)
            ->post(route('reports.bank-proofs'))
            ->assertOk()
            ->assertSee('Bank Proof Student')
            ->assertSee('REF-12345')
            ->assertSee('150,000');
    }

    public function test_paid_report_route_redirects_to_clearance(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);

        $this->actingAs($admin)
            ->get(route('reports.paid', ['class_name' => 'Form I']))
            ->assertRedirect(route('reports.clearance', ['class_name' => 'Form I']));
    }
}
