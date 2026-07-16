<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\Student;
use App\Models\User;
use App\Services\SmsSendResult;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'listed.parent@example.com',
            'phone' => '+255700000010',
            'name' => 'Listed Parent',
        ]);
        $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-LIST',
            'name' => 'Listed Child',
            'parent_email' => $parent->email,
            'parent_phone' => $parent->phone,
            'expected_total_fee' => 200000,
        ]);

        $this->actingAs($admin)
            ->get(route('notification-logs.send.create'))
            ->assertOk()
            ->assertSee('Send to parents using template')
            ->assertSee('Listed Parent')
            ->assertSee('Auto — match each parent');
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
            'expected_total_fee' => 300000,
        ]);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->once()->andReturn(SmsSendResult::sent());
        $this->app->instance(SmsService::class, $sms);

        $response = $this->actingAs($admin)->post(route('notification-logs.send.store'), [
            'parent_user_ids' => [$parent->id],
            'message_type' => 'fee_reminder_14',
            'send_sms' => '1',
            'send_email' => '0',
        ]);

        $response->assertRedirect(route('notification-logs.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('notification_logs', [
            'student_id' => $student->id,
            'channel' => 'sms',
            'status' => 'sent',
            'event_type' => 'fee_reminder_14',
        ]);
    }

    public function test_manual_send_rejects_more_than_five_parents(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $ids = [];

        for ($i = 1; $i <= 6; $i++) {
            $parent = User::factory()->create([
                'role' => 'parent',
                'email' => "parent{$i}@example.com",
                'phone' => '+2557000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            ]);

            $this->admitStudentForParent($parent, [
                'admission_no' => 'ADM-BATCH-'.$i,
                'name' => 'Batch Student '.$i,
                'parent_email' => $parent->email,
                'parent_phone' => $parent->phone,
                'expected_total_fee' => 100000,
            ]);

            $ids[] = $parent->id;
        }

        $this->actingAs($admin)
            ->from(route('notification-logs.send.create'))
            ->post(route('notification-logs.send.store'), [
                'parent_user_ids' => $ids,
                'message_type' => 'fee_reminder_14',
                'send_sms' => '1',
                'send_email' => '0',
            ])
            ->assertRedirect(route('notification-logs.send.create'))
            ->assertSessionHasErrors(['parent_user_ids']);
    }

    public function test_send_requires_at_least_one_channel(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'parent2@example.com', 'phone' => '+255700000099']);
        $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-201',
            'name' => 'Channel Test Student',
            'parent_email' => $parent->email,
            'expected_total_fee' => 100000,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('notification-logs.send.create'))
            ->post(route('notification-logs.send.store'), [
                'parent_user_ids' => [$parent->id],
                'message_type' => 'fee_reminder_14',
            ]);

        $response->assertRedirect(route('notification-logs.send.create'));
        $response->assertSessionHasErrors(['send_sms']);
    }

    public function test_unpaid_report_batch_send_to_selected_parents(): void
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
            ->post(route('reports.unpaid.send-reminders'), [
                'student_ids' => [$student->id],
                'message_type' => 'fee_reminder_14',
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

    public function test_auto_message_type_uses_fee_status_template(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'auto.parent@example.com',
            'phone' => '+255700000033',
        ]);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-AUTO',
            'name' => 'Auto Status Student',
            'parent_email' => $parent->email,
            'parent_phone' => $parent->phone,
            'expected_total_fee' => 300000,
        ]);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->once()->andReturn(SmsSendResult::sent());
        $this->app->instance(SmsService::class, $sms);

        $this->actingAs($admin)->post(route('notification-logs.send.store'), [
            'parent_user_ids' => [$parent->id],
            'message_type' => 'auto',
            'send_sms' => '1',
            'send_email' => '0',
        ])->assertRedirect(route('notification-logs.index'));

        $log = NotificationLog::query()->where('student_id', $student->id)->where('channel', 'sms')->first();
        $this->assertNotNull($log);
        $this->assertNotSame('', (string) $log->event_type);
    }
}
