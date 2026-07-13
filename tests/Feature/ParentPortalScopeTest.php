<?php

namespace Tests\Feature;

use App\Models\NotificationLog;
use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use App\Support\ParentStudentAdmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class ParentPortalScopeTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    public function test_parent_dashboard_shows_only_officially_linked_children(): void
    {
        $parentA = User::factory()->create(['role' => 'parent', 'email' => 'parenta@test.com']);
        $parentB = User::factory()->create(['role' => 'parent', 'email' => 'parentb@test.com']);

        $this->admitStudentForParent($parentA, [
            'admission_no' => 'A-001',
            'name' => 'Child A',
            'parent_email' => $parentA->email,
        ]);

        $this->admitStudentForParent($parentB, [
            'admission_no' => 'B-001',
            'name' => 'Child B',
            'parent_email' => $parentB->email,
        ]);

        $response = $this->actingAs($parentA)->get(route('parent.dashboard'));

        $response->assertOk();
        $response->assertSee('Child A');
        $response->assertDontSee('Child B');
    }

    public function test_parent_cannot_open_student_without_admission_link_even_if_email_matches(): void
    {
        $parentA = User::factory()->create(['role' => 'parent', 'email' => 'parenta@test.com']);
        $parentB = User::factory()->create(['role' => 'parent', 'email' => 'parentb@test.com']);

        $childB = Student::create([
            'admission_no' => 'B-002',
            'name' => 'Child B',
            'class_name' => 'Form II',
            'parent_user_id' => $parentB->id,
            'parent_email' => $parentA->email,
            'parent_phone' => '+255700000099',
            'expected_total_fee' => 120000,
        ]);

        $this->actingAs($parentA)
            ->get(route('parent.students.show', $childB))
            ->assertForbidden();
    }

    public function test_parent_cannot_open_another_parents_linked_student(): void
    {
        $parentA = User::factory()->create(['role' => 'parent', 'email' => 'parenta@test.com']);
        $parentB = User::factory()->create(['role' => 'parent', 'email' => 'parentb@test.com']);

        $childB = $this->admitStudentForParent($parentB, [
            'admission_no' => 'B-003',
            'name' => 'Child B Linked',
            'parent_email' => $parentB->email,
        ]);

        $this->actingAs($parentA)
            ->get(route('parent.students.show', $childB))
            ->assertForbidden();
    }

    public function test_student_store_creates_official_parent_link(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'newparent@test.com']);

        $this->actingAs($admin)->post(route('students.store'), [
            'name' => 'New Admit',
            'admission_no' => 'NEW-001',
            'class_name' => 'Form I',
            'parent_user_id' => $parent->id,
            'parent_relationship' => 'Mother',
            'parent_phone' => '+255711111111',
            'parent_email' => $parent->email,
            'expected_total_fee' => 50000,
        ])->assertRedirect(route('students.index'));

        $student = Student::where('admission_no', 'NEW-001')->first();
        $this->assertNotNull($student);
        $this->assertTrue(ParentStudentAdmission::parentOwnsStudent($parent, $student));
        $this->assertDatabaseHas('student_parent_links', [
            'student_id' => $student->id,
            'parent_user_id' => $parent->id,
            'relationship' => 'Mother',
            'is_primary' => true,
        ]);
    }

    public function test_parent_cannot_download_clearance_for_another_parents_child(): void
    {
        $parentA = User::factory()->create(['role' => 'parent', 'email' => 'parenta@test.com']);
        $parentB = User::factory()->create(['role' => 'parent', 'email' => 'parentb@test.com']);
        $admin = User::factory()->create(['role' => 'school_admin']);

        $childB = $this->admitStudentForParent($parentB, [
            'admission_no' => 'B-CERT',
            'name' => 'Child B Cert',
            'expected_total_fee' => 50_000,
        ]);

        Receipt::create([
            'student_id' => $childB->id,
            'student_name' => $childB->name,
            'amount' => 50_000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($parentA)
            ->get(route('parent.students.clearance-certificate', $childB))
            ->assertForbidden();
    }

    public function test_parent_cannot_mark_another_familys_notification_as_read(): void
    {
        $parentA = User::factory()->create(['role' => 'parent', 'email' => 'parenta@test.com']);
        $parentB = User::factory()->create(['role' => 'parent', 'email' => 'parentb@test.com']);

        $childB = $this->admitStudentForParent($parentB, [
            'admission_no' => 'B-NOTIF',
            'name' => 'Child B Notify',
        ]);

        $log = NotificationLog::create([
            'student_id' => $childB->id,
            'channel' => 'sms',
            'status' => 'sent',
            'message' => 'Fee reminder for Child B',
            'sent_on' => now()->toDateString(),
        ]);

        $this->actingAs($parentA)
            ->post(route('parent.notifications.read', $log))
            ->assertForbidden();
    }

    public function test_parent_notifications_list_excludes_other_families(): void
    {
        $parentA = User::factory()->create(['role' => 'parent', 'email' => 'parenta@test.com']);
        $parentB = User::factory()->create(['role' => 'parent', 'email' => 'parentb@test.com']);

        $childA = $this->admitStudentForParent($parentA, [
            'admission_no' => 'A-NOTIF',
            'name' => 'Child A Notify',
        ]);

        $childB = $this->admitStudentForParent($parentB, [
            'admission_no' => 'B-NOTIF2',
            'name' => 'Child B Notify',
        ]);

        NotificationLog::create([
            'student_id' => $childA->id,
            'channel' => 'sms',
            'status' => 'sent',
            'message' => 'Reminder for Child A',
            'sent_on' => now()->toDateString(),
        ]);

        NotificationLog::create([
            'student_id' => $childB->id,
            'channel' => 'email',
            'status' => 'sent',
            'message' => 'Reminder for Child B',
            'sent_on' => now()->toDateString(),
        ]);

        $this->actingAs($parentA)
            ->get(route('parent.notifications'))
            ->assertOk()
            ->assertSee('Reminder for Child A')
            ->assertDontSee('Reminder for Child B');
    }
}
