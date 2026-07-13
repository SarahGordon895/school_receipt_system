<?php

namespace Tests\Feature;

use App\Models\Receipt;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class FeeCollectionReportTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    public function test_fee_collection_report_lists_students_sorted_lowest_to_highest(): void
    {
        Setting::query()->create(['school_name' => 'Test School']);

        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent']);

        $lowPayer = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-LOW',
            'name' => 'Low Payer',
            'expected_total_fee' => 500000,
        ]);

        $highPayer = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-HIGH',
            'name' => 'High Payer',
            'expected_total_fee' => 500000,
        ]);

        Receipt::create([
            'student_id' => $lowPayer->id,
            'student_name' => $lowPayer->name,
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
            'user_id' => $admin->id,
        ]);

        Receipt::create([
            'student_id' => $highPayer->id,
            'student_name' => $highPayer->name,
            'amount' => 250000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->post(route('reports.generate'), [
            'date_range' => 'today',
        ]);

        $response->assertOk();
        $response->assertSeeInOrder(['Low Payer', 'High Payer']);
        $response->assertSee('50,000');
        $response->assertSee('250,000');
        $response->assertDontSee('50,000.00');
    }

    public function test_payment_sms_uses_template_from_settings(): void
    {
        Setting::query()->create([
            'school_name' => 'Template School',
            'sms_template_payment_received' => 'Paid {amount} for {student_name} at {school_name}',
        ]);

        $parent = User::factory()->create(['role' => 'parent']);

        $student = $this->admitStudentForParent($parent, [
            'name' => 'Template Student',
            'expected_total_fee' => 100000,
        ]);

        $text = app(NotificationTemplateService::class)->render(
            NotificationTemplateService::PAYMENT_RECEIVED,
            $student->loadSum('receipts', 'amount'),
            Receipt::make(['receipt_no' => 'R-001', 'amount' => 75000])
        );

        $this->assertStringContainsString('75,000', $text);
        $this->assertStringContainsString('Template Student', $text);
        $this->assertStringContainsString('Template School', $text);
    }
}
