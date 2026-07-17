<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_admin_can_import_students_and_see_results_list(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);

        $csv = "Student Name,Admission No,Class,Parent Name,Parent Phone,Parent Email\n";
        $csv .= "Asha Neema Mrosso,MBN-2026-900,Form I,Juma Mrosso,+255712000900,asha.parent@example.com\n";
        $csv .= "Baraka Kimaro,MBN-2026-901,Form II,Anna Kimaro,+255712000901,baraka.parent@example.com\n";

        $file = UploadedFile::fake()->createWithContent('students.csv', $csv);

        $response = $this->actingAs($admin)->post(route('students.import.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('students.import.result'));

        $this->actingAs($admin)
            ->get(route('students.import.result'))
            ->assertOk()
            ->assertSee('Students imported as a list')
            ->assertSee('2')
            ->assertSee('Asha Neema Mrosso')
            ->assertSee('Baraka Kimaro')
            ->assertSee('MBN-2026-900')
            ->assertSee('Form I');

        $this->assertDatabaseCount('students', 2);
        $this->assertDatabaseHas('students', [
            'name' => 'Asha Neema Mrosso',
            'admission_no' => 'MBN-2026-900',
            'class_name' => 'Form I',
        ]);
    }

    public function test_school_admin_can_import_students_from_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'school_admin']);

        $pdfPath = base_path('docs/test-students/student-import-test-list.pdf');
        $this->assertFileExists($pdfPath);

        $file = new UploadedFile(
            $pdfPath,
            'student-import-test-list.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->actingAs($admin)->post(route('students.import.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('students.import.result'));

        $this->actingAs($admin)
            ->get(route('students.import.result'))
            ->assertOk()
            ->assertSee('Students imported as a list')
            ->assertSee('JACKSON SAID ISSA')
            ->assertSee('BIDAUS KIMOTO BISENDO')
            ->assertSee('2022-02-00058');

        $this->assertGreaterThanOrEqual(15, Student::count());
        $this->assertDatabaseHas('students', [
            'name' => 'BIDAUS KIMOTO BISENDO',
            'admission_no' => '2022-02-00058',
            'class_name' => 'Group 02',
        ]);
        $this->assertDatabaseHas('students', [
            'name' => 'JACKSON SAID ISSA',
            'parent_email' => 'jacksonsaid239@gmail.com',
        ]);
        // Shared registration 2024-02-00261 in the sample PDF updates one student instead of creating two.
        $this->assertDatabaseHas('students', [
            'admission_no' => '2024-02-00261',
        ]);
    }

    public function test_super_admin_can_import_students_from_pdf(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);

        $pdfPath = base_path('docs/test-students/student-import-test-list.pdf');
        $file = new UploadedFile(
            $pdfPath,
            'student-import-test-list.pdf',
            'application/pdf',
            null,
            true
        );

        $this->actingAs($super)
            ->post(route('students.import.store'), ['file' => $file])
            ->assertRedirect(route('students.import.result'));

        $this->assertDatabaseHas('students', [
            'name' => 'NEEMA BRYSON DANDA',
            'admission_no' => '2024-02-00092',
        ]);
    }

    public function test_super_admin_can_open_student_import(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)
            ->get(route('students.import.form'))
            ->assertOk();
    }

    public function test_parent_cannot_access_student_import(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parent)
            ->get(route('students.import.form'))
            ->assertRedirect(route('parent.dashboard'));
    }
}
