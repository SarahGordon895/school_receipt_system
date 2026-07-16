<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use App\Support\ParentStudentAdmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class StudentUpdateTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    public function test_student_update_persists_parent_phone_and_notification_email(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent@example.com',
            'phone' => '+255700000001',
        ]);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-500',
            'name' => 'Persist Student',
            'parent_name' => 'Original Guardian',
            'parent_email' => 'notify-old@example.com',
            'parent_phone' => '+255700000001',
        ]);

        $response = $this->actingAs($admin)->put(route('students.update', $student), [
            'admission_no' => 'ADM-500',
            'name' => 'Persist Student Updated',
            'class_name' => 'Form IV',
            'parent_mode' => 'existing',
            'parent_user_id' => $parent->id,
            'parent_relationship' => 'Guardian',
            'parent_name' => 'Updated Guardian',
            'parent_phone' => '+255711222333',
            'parent_email' => 'notify-new@example.com',
            'portal_login_email' => 'portal-new@example.com',
            'expected_total_fee' => 400000,
            'fee_structure_ids' => [],
        ]);

        $response->assertRedirect(route('students.edit', $student));
        $response->assertSessionHas('status');

        $student->refresh();
        $parent->refresh();

        $this->assertSame('Persist Student Updated', $student->name);
        $this->assertSame('Form IV', $student->class_name);
        $this->assertSame('Updated Guardian', $student->parent_name);
        $this->assertSame('+255711222333', $student->parent_phone);
        $this->assertSame('notify-new@example.com', $student->parent_email);
        $this->assertSame('portal-new@example.com', $parent->email);
        $this->assertSame('+255711222333', $parent->phone);
    }

    public function test_sync_does_not_overwrite_notification_email_with_portal_email(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create([
            'role' => 'parent',
            'email' => 'parent.portal@example.com',
            'phone' => '+255700000001',
        ]);

        $student = $this->admitStudentForParent($parent, [
            'admission_no' => 'ADM-502',
            'name' => 'Sync Student',
            'parent_email' => 'alerts-only@example.com',
            'parent_phone' => '+255700000001',
        ]);

        ParentStudentAdmission::linkGuardian($student, $parent->id, 'Guardian', true, '+255700000001', $admin->id);

        $student->refresh();

        $this->assertSame('alerts-only@example.com', $student->parent_email);
        $this->assertSame('parent.portal@example.com', $parent->fresh()->email);
    }

    public function test_student_edit_form_shows_saved_parent_contact(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent', 'email' => 'portal@example.com']);

        $student = Student::create([
            'admission_no' => 'ADM-501',
            'name' => 'Form Student',
            'parent_user_id' => $parent->id,
            'parent_name' => 'Guardian Name',
            'parent_phone' => '+255722333444',
            'parent_email' => 'real-notify@example.com',
            'registered_by_user_id' => $admin->id,
            'admitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('students.edit', $student))
            ->assertOk()
            ->assertSee('value="+255722333444"', false)
            ->assertSee('value="real-notify@example.com"', false);
    }
}
