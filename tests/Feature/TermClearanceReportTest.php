<?php

namespace Tests\Feature;

use App\Models\Receipt;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AdmitsStudents;
use Tests\TestCase;

class TermClearanceReportTest extends TestCase
{
    use AdmitsStudents;
    use RefreshDatabase;

    public function test_admin_can_view_term_clearance_report(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);
        $parent = User::factory()->create(['role' => 'parent']);

        $this->admitStudentForParent($parent, [
            'name' => 'Cleared Student',
            'admission_no' => 'CLR-001',
            'expected_total_fee' => 100_000,
        ]);

        $student = Student::first();
        Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'amount' => 100_000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('reports.clearance'))
            ->assertOk()
            ->assertSee('Cleared Student')
            ->assertSee('Term Fee Clearance Report');
    }

    public function test_parent_can_download_clearance_certificate_when_fully_paid(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $admin = User::factory()->create(['role' => 'school_admin']);

        $student = $this->admitStudentForParent($parent, [
            'name' => 'Certificate Child',
            'admission_no' => 'CLR-002',
            'expected_total_fee' => 80_000,
        ]);

        Receipt::create([
            'student_id' => $student->id,
            'student_name' => $student->name,
            'amount' => 80_000,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Cash',
            'user_id' => $admin->id,
        ]);

        $this->actingAs($parent)
            ->get(route('parent.students.clearance-certificate', $student))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_certificate_denied_when_balance_remains(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $student = $this->admitStudentForParent($parent, [
            'name' => 'Still Owes',
            'expected_total_fee' => 200_000,
        ]);

        $this->actingAs($parent)
            ->from(route('parent.students.show', $student))
            ->get(route('parent.students.clearance-certificate', $student))
            ->assertRedirect(route('parent.students.show', $student))
            ->assertSessionHasErrors('certificate');
    }
}
