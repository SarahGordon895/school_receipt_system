<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\Receipt;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationTemplateService;
use App\Services\ParentReminderService;
use App\Services\SmsSendResult;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class AutomatedReminderTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_automation_sends_14_day_reminder_on_school_schedule(): void
    {
        Setting::query()->create([
            'school_name' => 'Test School',
            'fee_installment_day' => 15,
            'fee_installment_months' => [1, 6, 9],
        ]);
        Setting::forgetCache();

        // 14 days before 15 June → 1 June
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 8, 0, 0));

        $parent = User::factory()->create([
            'role' => 'parent',
            'phone' => '+255700000099',
            'email' => 'auto-parent@example.com',
        ]);

        $student = $this->admitStudentForParent($parent, [
            'name' => 'Two Week Student',
            'admission_no' => 'ADM-14D',
            'expected_total_fee' => 300_000,
            'parent_phone' => '+255700000099',
        ]);

        // Already paid the January third so only the June installment is outstanding.
        \App\Models\Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'amount' => 100_000,
            'payment_date' => '2026-01-20',
            'payment_mode' => 'Cash',
            'user_id' => User::factory()->create(['role' => 'school_admin'])->id,
        ]);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->once()->andReturn(SmsSendResult::sent());
        $this->app->instance(SmsService::class, $sms);

        $counts = app(ParentReminderService::class)->runAutomatedReminders();

        $this->assertGreaterThanOrEqual(1, $counts['sms']);

        $this->assertDatabaseHas('notification_logs', [
            'student_id' => $student->id,
            'channel' => 'sms',
            'event_type' => NotificationTemplateService::FEE_REMINDER_14,
            'status' => 'sent',
        ]);
    }

    public function test_milestone_reminder_is_not_sent_twice(): void
    {
        Mail::fake();

        Setting::query()->create([
            'school_name' => 'Test School',
            'fee_installment_day' => 15,
            'fee_installment_months' => [1, 6, 9],
        ]);
        Setting::forgetCache();

        Carbon::setTestNow(Carbon::create(2026, 6, 1, 8, 0, 0));

        $parent = User::factory()->create(['role' => 'parent', 'phone' => '+255700000088']);

        $student = $this->admitStudentForParent($parent, [
            'name' => 'Dedup Student',
            'expected_total_fee' => 300_000,
            'parent_phone' => '+255700000088',
        ]);

        \App\Models\Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'amount' => 100_000,
            'payment_date' => '2026-01-20',
            'payment_mode' => 'Cash',
            'user_id' => User::factory()->create(['role' => 'school_admin'])->id,
        ]);

        NotificationLog::create([
            'student_id' => $student->id,
            'channel' => 'sms',
            'event_type' => NotificationTemplateService::FEE_REMINDER_14,
            'status' => 'sent',
            'sent_on' => now()->subDay()->toDateString(),
            'message' => 'Already sent',
        ]);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->never();
        $this->app->instance(SmsService::class, $sms);

        app(ParentReminderService::class)->runAutomatedReminders();

        $this->assertEquals(1, NotificationLog::where('student_id', $student->id)
            ->where('channel', 'sms')
            ->where('event_type', NotificationTemplateService::FEE_REMINDER_14)
            ->count());
    }

    public function test_school_admin_cannot_access_fee_structures(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);

        $this->actingAs($admin)
            ->get(route('fee-structures.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_super_admin_can_access_school_reports(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)
            ->get(route('reports.index'))
            ->assertOk();
    }

    public function test_super_admin_can_access_fee_structures(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)
            ->get(route('fee-structures.index'))
            ->assertOk();
    }
}
