<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\User;
use App\Services\NotificationTemplateService;
use App\Services\ParentReminderService;
use App\Services\SmsSendResult;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class AutomatedReminderTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    public function test_automation_sends_14_day_reminder_on_exact_due_date_match(): void
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'phone' => '+255700000099',
            'email' => 'auto-parent@example.com',
        ]);

        $student = $this->admitStudentForParent($parent, [
            'name' => 'Two Week Student',
            'admission_no' => 'ADM-14D',
            'fee_due_date' => now()->addDays(14)->toDateString(),
            'expected_total_fee' => 400_000,
            'parent_phone' => '+255700000099',
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

        $parent = User::factory()->create(['role' => 'parent', 'phone' => '+255700000088']);

        $student = $this->admitStudentForParent($parent, [
            'name' => 'Dedup Student',
            'fee_due_date' => now()->addDays(14)->toDateString(),
            'expected_total_fee' => 200_000,
            'parent_phone' => '+255700000088',
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
