<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class NotificationLogControllerTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    public function test_school_admin_can_view_notification_logs_index(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);

        $response = $this->actingAs($admin)->get(route('notification-logs.index'));

        $response->assertOk();
        $response->assertSee('Reminder logs');
    }

    public function test_parent_cannot_access_notification_logs(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parent)
            ->get(route('notification-logs.index'))
            ->assertRedirect(route('parent.dashboard'));
    }

    public function test_school_admin_can_create_notification_log(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent@example.com']);
        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-100',
            'name' => 'Test Student',
            'parent_email' => $parent->email,
        ]);

        $response = $this->actingAs($admin)->post(route('notification-logs.store'), [
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'sent',
            'sent_on' => now()->toDateString(),
            'message' => 'Manual fee reminder via phone call.',
        ]);

        $response->assertRedirect(route('notification-logs.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('notification_logs', [
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'sent',
            'message' => 'Manual fee reminder via phone call.',
        ]);
    }

    public function test_school_admin_can_update_notification_log(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent2@example.com']);
        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-101',
            'name' => 'Update Student',
            'parent_email' => $parent->email,
        ]);

        $log = NotificationLog::create([
            'student_id' => $student->id,
            'channel' => 'email',
            'status' => 'failed',
            'sent_on' => now()->toDateString(),
            'message' => 'Fee reminder email sent.',
        ]);

        $response = $this->actingAs($admin)->put(route('notification-logs.update', $log), [
            'student_id' => $student->id,
            'channel' => 'email',
            'status' => 'sent',
            'sent_on' => now()->toDateString(),
            'message' => 'Fee reminder email sent successfully.',
        ]);

        $response->assertRedirect(route('notification-logs.show', $log));

        $this->assertDatabaseHas('notification_logs', [
            'id' => $log->id,
            'status' => 'sent',
            'message' => 'Fee reminder email sent successfully.',
        ]);
    }

    public function test_school_admin_can_delete_notification_log(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent3@example.com']);
        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-102',
            'name' => 'Delete Student',
            'parent_email' => $parent->email,
        ]);

        $log = NotificationLog::create([
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'sent',
            'sent_on' => now()->toDateString(),
            'message' => 'Test log to delete.',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('notification-logs.index'))
            ->delete(route('notification-logs.destroy', $log));

        $response->assertRedirect(route('notification-logs.index'));
        $this->assertDatabaseMissing('notification_logs', ['id' => $log->id]);
    }

    public function test_store_validation_rejects_invalid_status(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent4@example.com']);
        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-103',
            'name' => 'Validate Student',
            'parent_email' => $parent->email,
        ]);

        $response = $this->actingAs($admin)->from(route('notification-logs.create'))->post(route('notification-logs.store'), [
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'invalid',
            'sent_on' => now()->toDateString(),
            'message' => 'Bad status',
        ]);

        $response->assertRedirect(route('notification-logs.create'));
        $response->assertSessionHasErrors(['status']);
    }

    public function test_school_admin_can_resend_failed_sms_reminder(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent5@example.com',
            'phone' => '+255700000005',
        ]);
        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-104',
            'name' => 'Resend Student',
            'parent_email' => $parent->email,
            'parent_phone' => '+255700000005',
            'fee_due_date' => now()->addDays(5)->toDateString(),
        ]);

        $failedLog = NotificationLog::create([
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'failed',
            'sent_on' => now()->toDateString(),
            'message' => 'Scheduled fee reminder SMS failed.',
        ]);

        $sms = \Mockery::mock(\App\Services\SmsService::class);
        $sms->shouldReceive('send')->once()->andReturn(\App\Services\SmsSendResult::sent());
        $this->app->instance(\App\Services\SmsService::class, $sms);

        $response = $this->actingAs($admin)
            ->from(route('notification-logs.index'))
            ->post(route('notification-logs.resend', $failedLog));

        $response->assertRedirect(route('notification-logs.index'));
        $response->assertSessionHas('status');

        $failedLog->refresh();
        $this->assertSame('sent', $failedLog->status);
        $this->assertStringContainsString('Resent Fee reminder', (string) $failedLog->message);
    }

    public function test_resend_rejects_already_sent_logs(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent6@example.com']);
        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-105',
            'name' => 'Sent Student',
            'parent_email' => $parent->email,
        ]);

        $sentLog = NotificationLog::create([
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'sent',
            'sent_on' => now()->toDateString(),
            'message' => 'SMS sent successfully.',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('notification-logs.show', $sentLog))
            ->post(route('notification-logs.resend', $sentLog));

        $response->assertRedirect(route('notification-logs.show', $sentLog));
        $response->assertSessionHasErrors(['resend']);
    }

    public function test_failed_sms_log_shows_resend_button_on_index(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent7@example.com']);
        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-106',
            'name' => 'Button Student',
            'parent_email' => $parent->email,
        ]);

        NotificationLog::create([
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'failed',
            'sent_on' => now()->toDateString(),
            'message' => 'SMS delivery failed.',
        ]);

        $this->actingAs($admin)
            ->get(route('notification-logs.index'))
            ->assertOk()
            ->assertSee('Resend SMS', false)
            ->assertSee('Delivered', false)
            ->assertSee('Failed', false);
    }

    public function test_school_admin_can_view_notification_log_details(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent9@example.com']);
        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-108',
            'name' => 'Show Student',
            'parent_email' => $parent->email,
        ]);

        $log = NotificationLog::create([
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'sent',
            'sent_on' => now()->toDateString(),
            'message' => 'Manual fee reminder SMS sent.',
        ]);

        $this->actingAs($admin)
            ->get(route('notification-logs.show', $log))
            ->assertOk()
            ->assertSee('Reminder details', false)
            ->assertSee('Show Student', false);
    }

    public function test_missing_notification_log_redirects_with_warning(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);

        $response = $this->actingAs($admin)->get('/notification-logs/999999');

        $response->assertRedirect(route('notification-logs.index'));
        $response->assertSessionHas('warning');
        $response->assertSessionMissing('errors');
    }

    public function test_admin_can_mark_failed_log_as_delivered(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent8@example.com']);
        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-107',
            'name' => 'Mark Student',
            'parent_email' => $parent->email,
        ]);

        $log = NotificationLog::create([
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'failed',
            'sent_on' => now()->toDateString(),
            'message' => 'SMS delivery failed.',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('notification-logs.show', $log))
            ->post(route('notification-logs.mark-delivered', $log));

        $response->assertRedirect(route('notification-logs.show', $log));
        $response->assertSessionHas('status');

        $log->refresh();
        $this->assertSame('sent', $log->status);
        $this->assertSame('confirmed_by_admin', $log->delivery_status);
    }
}
