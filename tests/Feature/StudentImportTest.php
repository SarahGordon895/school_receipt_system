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
            ->assertSee('Import complete')
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
