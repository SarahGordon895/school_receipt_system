<?php

namespace Tests\Feature;

use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Services\ParentReminderService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class FullyPaidStudentTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    public function test_fully_paid_student_is_not_included_in_automated_reminders(): void
    {
        $parent = User::factory()->create(['role' => 'parent', 'phone' => '+255700000077']);

        $this->admitStudentForParent($parent, [
            'name' => 'Paid Up Student',
            'fee_due_date' => now()->addDays(14)->toDateString(),
            'expected_total_fee' => 100_000,
            'parent_phone' => '+255700000077',
        ]);

        $student = Student::first();
        $admin = User::factory()->create(['role' => 'school_admin']);

        Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'amount' => 100_000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
            'user_id' => $admin->id,
        ]);

        $student->loadSum('receipts', 'amount');
        $this->assertTrue($student->isFullyPaid());
        $this->assertSame(0, $student->balance);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->never();
        $this->app->instance(SmsService::class, $sms);

        app(ParentReminderService::class)->runAutomatedReminders();
    }

    public function test_parent_sees_fully_paid_status_on_dashboard(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $this->admitStudentForParent($parent, [
            'name' => 'Cleared Fees Child',
            'expected_total_fee' => 50_000,
        ]);

        $student = Student::first();
        $admin = User::factory()->create(['role' => 'school_admin']);

        Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'amount' => 50_000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.dashboard'))
            ->assertOk()
            ->assertSee('Fully paid')
            ->assertSee('All school fees are paid in full');
    }
}
