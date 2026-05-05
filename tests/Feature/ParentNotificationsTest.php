<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Notifications\PaymentReceivedNotification;
use App\Services\ParentPaymentNotifier;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class ParentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_notifications_reject_foreign_student_filter(): void
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent1@example.com',
        ]);

        $ownStudent = Student::create([
            'admission_no' => 'ADM-001',
            'name' => 'Owned Student',
            'class_name' => 'Form I',
            'parent_name' => 'Parent One',
            'parent_phone' => '+255700000100',
            'parent_email' => $parent->email,
            'expected_total_fee' => 100000,
        ]);

        $otherStudent = Student::create([
            'admission_no' => 'ADM-002',
            'name' => 'Other Student',
            'class_name' => 'Form II',
            'parent_name' => 'Parent Two',
            'parent_phone' => '+255700000200',
            'parent_email' => 'parent2@example.com',
            'expected_total_fee' => 120000,
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

        $student = Student::create([
            'admission_no' => 'ADM-003',
            'name' => 'Notify Student',
            'class_name' => 'Form III',
            'parent_name' => 'Notify Parent',
            'parent_phone' => '+255700000300',
            'parent_email' => $parent->email,
            'expected_total_fee' => 200000,
        ]);

        $receipt = Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'class_name' => $student->class_name,
            'amount' => 50000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
            'reference' => 'TEST-REF-1',
            'note' => 'Feature test payment',
            'user_id' => $admin->id,
        ]);

        $smsService = Mockery::mock(SmsService::class);
        $smsService->shouldReceive('send')->twice()->andReturn(true);

        $notifier = new ParentPaymentNotifier($smsService);
        $notifier->notify($receipt);
        $notifier->notify($receipt);

        Notification::assertSentTo($parent, PaymentReceivedNotification::class);
        $this->assertSame(1, NotificationLog::query()
            ->where('student_id', $student->id)
            ->where('channel', 'email')
            ->where('message', 'Payment confirmation email for receipt ' . $receipt->receipt_no)
            ->count());
        $this->assertSame(1, NotificationLog::query()
            ->where('student_id', $student->id)
            ->where('channel', 'sms')
            ->where('message', 'Payment confirmation SMS for receipt ' . $receipt->receipt_no)
            ->count());
    }
}
