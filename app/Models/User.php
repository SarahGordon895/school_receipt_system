<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->normalized_role, $roles, true);
    }

    public function isParent(): bool
    {
        return $this->normalized_role === 'parent';
    }

    public function isSchoolAdmin(): bool
    {
        return $this->normalized_role === 'school_admin';
    }

    public function isSuperAdmin(): bool
    {
        return $this->normalized_role === 'super_admin';
    }

    public function canManageSchool(): bool
    {
        return $this->isSchoolAdmin() || $this->isSuperAdmin();
    }

    public function getNormalizedRoleAttribute(): string
    {
        return $this->role ?: 'school_admin';
    }

    public function getHomeRouteAttribute(): string
    {
        return match ($this->normalized_role) {
            'parent' => 'parent.dashboard',
            default => 'dashboard',
        };
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return $phone;
        }

        if (str_starts_with($digits, '255')) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+255'.substr($digits, 1);
        }

        return '+'.$digits;
    }

    public function getLoginIdentifierAttribute(): string
    {
        return $this->isParent()
            ? ($this->phone ?? '—')
            : ($this->email ?? '—');
    }

    /**
     * Students officially admitted under this parent/guardian (via student_parent_links).
     */
    public function admittedStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_parent_links', 'parent_user_id', 'student_id')
            ->withPivot(['relationship', 'is_primary', 'parent_phone', 'linked_by_user_id', 'linked_at'])
            ->withTimestamps()
            ->select('students.*');
    }

    /** @deprecated Use admittedStudents() — kept for backward compatibility in codebase */
    public function parentStudents(): BelongsToMany
    {
        return $this->admittedStudents();
    }
}
