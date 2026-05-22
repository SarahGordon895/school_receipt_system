<?php

namespace App\Support;

use App\Models\Student;
use App\Models\StudentParentLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ParentStudentAdmission
{
    /**
     * Official school admission link between a registered student and parent portal account.
     * Aligns with project report: student registration includes parent information for direct communication.
     */
    public static function linkGuardian(
        Student $student,
        int $parentUserId,
        string $relationship = 'Guardian',
        bool $isPrimary = true,
        ?string $parentPhone = null,
        ?int $linkedByUserId = null,
    ): StudentParentLink {
        $parent = User::query()->where('role', 'parent')->findOrFail($parentUserId);

        return DB::transaction(function () use ($student, $parent, $relationship, $isPrimary, $parentPhone, $linkedByUserId) {
            if ($isPrimary) {
                StudentParentLink::query()
                    ->where('student_id', $student->id)
                    ->update(['is_primary' => false]);
            }

            $link = StudentParentLink::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'parent_user_id' => $parent->id,
                ],
                [
                    'relationship' => in_array($relationship, StudentParentLink::RELATIONSHIPS, true)
                        ? $relationship
                        : 'Guardian',
                    'is_primary' => $isPrimary,
                    'parent_phone' => $parentPhone ?: $student->parent_phone,
                    'linked_by_user_id' => $linkedByUserId,
                    'linked_at' => $student->admitted_at ?? now(),
                ]
            );

            self::syncStudentPrimaryGuardian($student->fresh());

            return $link->fresh(['parentUser', 'linkedBy']);
        });
    }

    public static function syncStudentPrimaryGuardian(Student $student): Student
    {
        $primary = StudentParentLink::query()
            ->with('parentUser')
            ->where('student_id', $student->id)
            ->where('is_primary', true)
            ->first();

        if (! $primary?->parentUser) {
            $student->forceFill(['parent_user_id' => null]);
            $student->save();

            return $student;
        }

        $student->forceFill([
            'parent_user_id' => $primary->parent_user_id,
            'parent_email' => $primary->parentUser->email,
            'parent_name' => $student->parent_name ?: $primary->parentUser->name,
            'parent_phone' => $primary->parent_phone ?: $student->parent_phone,
        ]);
        $student->save();

        return $student;
    }

    public static function ensurePrimaryLinkExists(Student $student): void
    {
        $hasPrimary = StudentParentLink::query()
            ->where('student_id', $student->id)
            ->where('is_primary', true)
            ->exists();

        if (! $hasPrimary) {
            throw new InvalidArgumentException(
                'Each admitted student must have a primary parent/guardian linked to a parent portal account.'
            );
        }
    }

    public static function parentOwnsStudent(User $parent, Student $student): bool
    {
        if (! $parent->isParent()) {
            return false;
        }

        return StudentParentLink::query()
            ->where('student_id', $student->id)
            ->where('parent_user_id', $parent->id)
            ->exists();
    }
}
