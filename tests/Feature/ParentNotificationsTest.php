<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Notifications\PaymentReceivedNotification;
use App\Services\ParentPaymentNotifier;
use App\Services\SmsSendResult;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class ParentNotificationsTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    public function test_parent_notifications_reject_foreign_student_filter(): void
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent1@example.com',
        ]);

        $ownStudent = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-001',
            'name' => 'Owned Student',
            'parent_name' => 'Parent One',
            'parent_email' => $parent->email,
        ]);

        $otherParent = User::factory()->create(['role' => 'parent', 'email' => 'parent2@example.com']);

        $otherStudent = $this->admitStudentForParent($otherParent, [
            'admission_no' => 'ADM-002',
            'name' => 'Other Student',
            'parent_name' => 'Parent Two',
            'parent_email' => $otherParent->email,
        ]);

        NotificationLog::create([
            'student_id' => $ownStudent->id,
            'channel' => 'email',
            'status' => 'sent',
            'sent_on' => now()->toDateString(),
            'message' => 'Own student notification',
        ]);

        NotificationLog::create([
            'student_id' => $otherStudent->id,
            'channel' => 'email',
            'status' => 'sent',
            'sent_on' => now()->toDateString(),
            'message' => 'Foreign student notification',
        ]);

        $response = $this->actingAs($parent)
            ->from(route('parent.notifications'))
            ->get(route('parent.notifications', ['student_id' => $otherStudent->id]));

        $response->assertRedirect(route('parent.notifications'));
        $response->assertSessionHasErrors(['student_id']);
    }

    public function test_payment_notifier_does_not_duplicate_logs_for_same_receipt(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'school_admin',
            'email' => 'admin@example.com',
        ]);

        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'notify-parent@example.com',
        ]);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-003',
            'name' => 'Notify Student',
            'parent_name' => 'Notify Parent',
            'parent_email' => $parent->email,
        ]);

        $receipt = Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'class_name' => $student->class_name,
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
            'user_id' => $admin->id,
        ]);

        $sms = Mockery::mock(SmsService::class);
        $sms->shouldReceive('send')->andReturn(SmsSendResult::sent());
        $this->app->instance(SmsService::class, $sms);

        $notifier = app(ParentPaymentNotifier::class);
        $notifier->notify($receipt);
        $notifier->notify($receipt);

        Notification::assertSentOnDemand(PaymentReceivedNotification::class);

        $this->assertEquals(
            1,
            NotificationLog::where('student_id', $student->id)->where('channel', 'email')->count()
        );
    }
}
