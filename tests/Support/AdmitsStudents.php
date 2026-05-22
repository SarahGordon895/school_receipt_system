<?php

namespace Tests\Support;

use App\Models\Student;
use App\Models\User;
use App\Support\ParentStudentAdmission;

trait AdmitsStudents
{
    protected function admitStudentForParent(User $parent, array $attributes = [], string $relationship = 'Guardian'): Student
    {
        $student = Student::create(array_merge([
            'name' => 'Test Student',
            'class_name' => 'Form I',
            'expected_total_fee' => 100000,
            'parent_phone' => '+255700000000',
        ], $attributes));

        ParentStudentAdmission::linkGuardian(
            $student,
            $parent->id,
            $relationship,
            true,
            $attributes['parent_phone'] ?? '+255700000000',
        );

        return $student->fresh();
    }
}
