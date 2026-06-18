<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\User;
use App\Services\SmsSendResult;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class ManualParentReminderTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    public function test_school_admin_can_open_send_reminder_form(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);

        $this->actingAs($admin)
            ->get(route('notification-logs.send.create'))
            ->assertOk()
            ->assertSee('Send fee reminder to parent');
    }

    public function test_parent_cannot_access_send_reminder_form(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parent)
            ->get(route('notification-logs.send.create'))
            ->assertRedirect(route('parent.dashboard'));
    }

    public function test_school_admin_can_send_manual_sms_reminder(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent@example.com',
            'phone' => '+255700000001',
        ]);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-200',
            'name' => 'Manual Reminder Student',
            'parent_email' => $parent->email,
            'parent_phone' => '+255700000001',
            'fee_due_date' => now()->addDays(3)->toDateString(),
        ]);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->once()->andReturn(SmsSendResult::sent());
        $this->app->instance(SmsService::class, $sms);

        $response = $this->actingAs($admin)->post(route('notification-logs.send.store'), [
            'student_id' => $student->id,
            'send_sms' => '1',
            'send_email' => '0',
        ]);

        $response->assertRedirect(route('notification-logs.index', ['student_id' => $student->id]));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('notification_logs', [
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'sent',
        ]);
    }

    public function test_send_requires_at_least_one_channel(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent2@example.com']);
        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-201',
            'name' => 'Channel Test Student',
            'parent_email' => $parent->email,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('notification-logs.send.create'))
            ->post(route('notification-logs.send.store'), [
                'student_id' => $student->id,
            ]);

        $response->assertRedirect(route('notification-logs.send.create'));
        $response->assertSessionHasErrors(['send_sms']);
    }

    public function test_quick_send_from_unpaid_report_creates_logs(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent3@example.com',
            'phone' => '+255700000002',
        ]);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-202',
            'name' => 'Unpaid Report Student',
            'parent_email' => $parent->email,
            'parent_phone' => '+255700000002',
            'expected_total_fee' => 500000,
        ]);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->once()->andReturn(SmsSendResult::sent());
        $this->app->instance(SmsService::class, $sms);

        $response = $this->actingAs($admin)
            ->from(route('reports.unpaid'))
            ->post(route('students.send-reminder', $student), [
                'send_sms' => '1',
                'send_email' => '1',
            ]);

        $response->assertRedirect(route('reports.unpaid'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('notification_logs', [
            'student_id' => $student->id,
            'channel' => 'sms',
        ]);
        $this->assertDatabaseHas('notification_logs', [
            'student_id' => $student->id,
            'channel' => 'email',
        ]);
    }
}
